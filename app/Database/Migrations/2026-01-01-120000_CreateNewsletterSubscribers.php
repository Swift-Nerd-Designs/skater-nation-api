<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsletterSubscribers extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE newsletter_subscribers (
                id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                email              VARCHAR(255)  NOT NULL,
                name               VARCHAR(255)  NULL,
                confirmation_token VARCHAR(100)  NULL,
                unsubscribe_token  VARCHAR(100)  NOT NULL,
                confirmed          TINYINT(1)    NOT NULL DEFAULT 0,
                confirmed_at       DATETIME      NULL,
                subscribed_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at    DATETIME      NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_email (email),
                UNIQUE KEY uq_confirmation_token (confirmation_token),
                UNIQUE KEY uq_unsubscribe_token (unsubscribe_token),
                KEY idx_confirmed (confirmed, unsubscribed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS newsletter_subscribers');
    }
}
