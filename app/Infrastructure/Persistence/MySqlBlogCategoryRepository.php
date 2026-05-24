<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Blog\BlogCategory;
use App\Domain\Blog\BlogCategoryRepositoryInterface;

class MySqlBlogCategoryRepository extends AbstractMysqlRepository implements BlogCategoryRepositoryInterface
{
    public function findAll(): array
    {
        $rows = $this->db->query("
            SELECT c.id, c.slug, c.name,
                   COUNT(p.id) AS post_count
            FROM blog_categories c
            LEFT JOIN blog_posts p ON p.category_id = c.id AND p.status = 'published'
            GROUP BY c.id
            ORDER BY c.name ASC
        ")->getResultArray();

        return array_map(fn($r) => BlogCategory::fromArray($r), $rows);
    }

    public function findById(int $id): ?BlogCategory
    {
        $row = $this->db->table('blog_categories')->where('id', $id)->get()->getRowArray();
        return $row ? BlogCategory::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?BlogCategory
    {
        $row = $this->db->table('blog_categories')->where('slug', $slug)->get()->getRowArray();
        return $row ? BlogCategory::fromArray($row) : null;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->db->table('blog_categories')->where('slug', $slug);
        if ($excludeId !== null) $builder->where('id !=', $excludeId);
        return $builder->countAllResults() > 0;
    }

    public function save(BlogCategory $category): BlogCategory
    {
        $now = date('Y-m-d H:i:s');

        if ($category->id === 0) {
            $this->db->table('blog_categories')->insert([
                'slug'       => $category->slug,
                'name'       => $category->name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $this->db->insertID();
        } else {
            $this->db->table('blog_categories')->where('id', $category->id)->update([
                'slug'       => $category->slug,
                'name'       => $category->name,
                'updated_at' => $now,
            ]);
            $id = $category->id;
        }

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->db->table('blog_categories')->where('id', $id)->delete();
    }
}
