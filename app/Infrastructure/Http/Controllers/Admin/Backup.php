<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Infrastructure\Http\Controllers\BaseController;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Admin backup / restore / factory-reset controller.
 *
 * POST /admin/backup/create        — stream zip download (SQL dump + manifest)
 * POST /admin/backup/restore       — upload zip, validate, restore DB
 * POST /admin/backup/factory-reset — wipe data tables, re-seed defaults
 */
class Backup extends BaseController
{
    private const MANIFEST_VERSION = '1';

    /** Tables excluded from all backup/restore operations. */
    private const SKIP_ALWAYS = ['migrations'];

    /** Tables excluded from factory-reset truncation (preserve admin access). */
    private const SKIP_RESET = ['migrations', 'admin_users', 'admin_sessions'];

    private ConnectionInterface $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // ── Create backup ─────────────────────────────────────────────────────────

    public function create(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $tables    = $this->tableNames();
            $sql       = $this->dumpSql($tables);

            $manifest = json_encode([
                'version'    => self::MANIFEST_VERSION,
                'created_at' => date('c'),
                'tables'     => $tables,
            ], JSON_PRETTY_PRINT);

            $tmpPath = WRITEPATH . 'uploads/backup_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.zip';

            $zip = new \ZipArchive();
            if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return $this->error('Could not create backup archive.', 500);
            }
            $zip->addFromString('manifest.json', $manifest);
            $zip->addFromString('data.sql', $sql);
            $zip->close();

            $content = file_get_contents($tmpPath);
            @unlink($tmpPath);

            return $this->response
                ->setStatusCode(200)
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', "attachment; filename=\"backup_{$timestamp}.zip\"")
                ->setHeader('Content-Length', (string) strlen($content))
                ->setBody($content);

        } catch (\Throwable $e) {
            log_message('error', 'Backup::create — ' . $e->getMessage());
            return $this->error('Backup failed: ' . $e->getMessage(), 500);
        }
    }

    // ── Restore backup ────────────────────────────────────────────────────────

    public function restore(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('backup');

        if (!$file || !$file->isValid()) {
            return $this->error('No valid file uploaded.', 400);
        }

        if (strtolower($file->getClientExtension()) !== 'zip') {
            return $this->error('Backup must be a .zip file.', 400);
        }

        $tmpName = 'restore_' . bin2hex(random_bytes(8)) . '.zip';
        $file->move(WRITEPATH . 'uploads/', $tmpName);
        $tmpPath = WRITEPATH . 'uploads/' . $tmpName;

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                return $this->error('Could not open backup archive.', 400);
            }

            $manifest = $zip->getFromName('manifest.json');
            $sql      = $zip->getFromName('data.sql');
            $zip->close();

            if ($manifest === false || $sql === false) {
                return $this->error('Invalid backup: missing manifest.json or data.sql.', 400);
            }

            $meta = json_decode($manifest, true);
            if (!is_array($meta) || empty($meta['version']) || empty($meta['tables'])) {
                return $this->error('Invalid backup: malformed manifest.json.', 400);
            }
            if ($meta['version'] !== self::MANIFEST_VERSION) {
                return $this->error("Incompatible backup version '{$meta['version']}'. Expected: " . self::MANIFEST_VERSION, 400);
            }
            if (!is_array($meta['tables']) || empty($meta['tables'])) {
                return $this->error('Invalid backup: no tables listed in manifest.', 400);
            }

            $this->executeSql($sql);

        } catch (\Throwable $e) {
            log_message('error', 'Backup::restore — ' . $e->getMessage());
            return $this->error('Restore failed: ' . $e->getMessage(), 500);
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        return $this->ok(['message' => 'Database restored successfully.']);
    }

    // ── Factory reset ─────────────────────────────────────────────────────────

    public function factoryReset(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();
        if (($body['confirm'] ?? '') !== 'RESET') {
            return $this->error('Confirmation required. Send { "confirm": "RESET" }.', 400);
        }

        try {
            $tables = array_filter(
                $this->tableNames(),
                fn($t) => !in_array($t, self::SKIP_RESET, true)
            );

            $this->db->query('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                $this->db->query("TRUNCATE TABLE `{$table}`");
            }
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');

            // Re-seed minimal defaults (suppress CLI echo)
            ob_start();
            (new \App\Database\Seeds\MainSeeder(new \Config\Database()))->run();
            ob_end_clean();

        } catch (\Throwable $e) {
            log_message('error', 'Backup::factoryReset — ' . $e->getMessage());
            return $this->error('Factory reset failed: ' . $e->getMessage(), 500);
        }

        return $this->ok(['message' => 'Factory reset complete. Default settings and pages have been restored.']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** Returns all table names in the current DB, excluding CI4 migration tracking. */
    private function tableNames(): array
    {
        $rows   = $this->db->query('SHOW TABLES')->getResultArray();
        if (empty($rows)) return [];
        $col    = array_key_first($rows[0]);
        $tables = array_column($rows, $col);
        return array_values(array_filter($tables, fn($t) => !in_array($t, self::SKIP_ALWAYS, true)));
    }

    /** Generates a complete SQL dump for the given tables. */
    private function dumpSql(array $tables): string
    {
        $lines = [
            '-- Site backup generated ' . date('c'),
            '-- Manifest version: ' . self::MANIFEST_VERSION,
            '-- Tables: ' . implode(', ', $tables),
            '',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($tables as $table) {
            $row = $this->db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            $ddl = $row['Create Table'] ?? $row[array_key_last($row)];

            $lines[] = "-- --------------------------------------------------------";
            $lines[] = "-- Table: `{$table}`";
            $lines[] = "-- --------------------------------------------------------";
            $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
            $lines[] = $ddl . ';';
            $lines[] = '';

            $rows = $this->db->query("SELECT * FROM `{$table}`")->getResultArray();
            if (!empty($rows)) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                foreach ($rows as $r) {
                    $vals = implode(', ', array_map(function ($v) {
                        if ($v === null) return 'NULL';
                        return "'" . str_replace(
                            ["\\", "'", "\n", "\r", "\0"],
                            ["\\\\", "\\'", "\\n", "\\r", "\\0"],
                            (string) $v
                        ) . "'";
                    }, $r));
                    $lines[] = "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});";
                }
            }
            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        return implode("\n", $lines);
    }

    /** Executes a multi-statement SQL dump against the current connection. */
    private function executeSql(string $sql): void
    {
        // MySQLi multi_query handles the full dump atomically
        $mysqli = $this->db->connID;
        if (!$mysqli instanceof \mysqli) {
            throw new \RuntimeException('Restore requires a MySQLi database connection.');
        }

        if (!$mysqli->multi_query($sql)) {
            throw new \RuntimeException('SQL execution failed: ' . $mysqli->error);
        }

        // Drain all result sets — required before further queries
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
            if ($mysqli->errno) {
                throw new \RuntimeException('SQL restore error: ' . $mysqli->error);
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
    }
}
