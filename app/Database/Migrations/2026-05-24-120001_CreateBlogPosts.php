<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBlogPosts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'excerpt'     => ['type' => 'TEXT', 'null' => true],
            'body'        => ['type' => 'LONGTEXT', 'null' => true],
            'cover_image' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
            'published_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('category_id', 'blog_categories', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('blog_posts');
    }

    public function down(): void
    {
        $this->forge->dropTable('blog_posts', true);
    }
}
