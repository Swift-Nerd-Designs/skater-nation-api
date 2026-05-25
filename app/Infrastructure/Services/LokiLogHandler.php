<?php

namespace App\Infrastructure\Services;

use CodeIgniter\Log\Handlers\BaseHandler;
use CodeIgniter\Log\Handlers\HandlerInterface;

/**
 * Grafana Loki log handler for CodeIgniter 4.
 *
 * Ships log entries to Grafana Cloud Loki via the HTTP push API.
 * Configured via .env:
 *
 *   LOKI_URL      = https://logs-prod-eu-west-0.grafana.net/loki/api/v1/push
 *   LOKI_USER     = <numeric Loki user ID from Grafana Cloud>
 *   LOKI_PASSWORD = <Grafana Cloud API key with MetricsPublisher role>
 *
 * No-ops gracefully when LOKI_URL is empty or in testing.
 */
class LokiLogHandler extends BaseHandler implements HandlerInterface
{
    private string $url;
    private string $user;
    private string $password;
    private string $app;
    private string $environment;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->url         = env('LOKI_URL', '');
        $this->user        = env('LOKI_USER', '');
        $this->password    = env('LOKI_PASSWORD', '');
        $this->app         = env('LOKI_APP_LABEL', 'skater-nation-api');
        $this->environment = ENVIRONMENT;
    }

    public function handle($level, $message): bool
    {
        if ($this->url === '' || ENVIRONMENT === 'testing') {
            return false;
        }

        $line      = $this->format($message);
        $timestamp = (string)(intval(microtime(true) * 1e9)); // nanoseconds

        $payload = json_encode([
            'streams' => [[
                'stream' => [
                    'app'         => $this->app,
                    'environment' => $this->environment,
                    'level'       => strtolower($level ?? 'info'),
                ],
                'values' => [[$timestamp, $line]],
            ]],
        ]);

        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        if ($this->user !== '' && $this->password !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->password}");
        }

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}
