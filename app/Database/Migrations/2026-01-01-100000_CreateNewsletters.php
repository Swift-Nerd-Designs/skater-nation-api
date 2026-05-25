<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsletters extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS newsletters (
                id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                issue          VARCHAR(100)  NOT NULL DEFAULT '',
                title          VARCHAR(255)  NOT NULL,
                description    TEXT          NULL,
                filename       VARCHAR(255)  NOT NULL DEFAULT '',
                file_url       VARCHAR(1000) NOT NULL DEFAULT '',
                file_size      INT UNSIGNED  NOT NULL DEFAULT 0,
                published      TINYINT(1)    NOT NULL DEFAULT 0,
                published_date DATE          NULL,
                created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_published (published, published_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS newsletters');
    }
}
