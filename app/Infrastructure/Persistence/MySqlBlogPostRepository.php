<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Blog\BlogPost;
use App\Domain\Blog\BlogPostRepositoryInterface;

class MySqlBlogPostRepository extends AbstractMysqlRepository implements BlogPostRepositoryInterface
{
    private const COLS = "
        p.id, p.slug, p.title, p.excerpt, p.body, p.cover_image, p.author_name,
        p.category_id, p.status, p.published_at, p.created_at, p.updated_at,
        c.name AS category_name, c.slug AS category_slug
    ";

    public function findAll(int $page = 1, int $perPage = 12, string $status = '', int $categoryId = 0): array
    {
        $offset = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[]  = 'p.status = ?';
            $params[] = $status;
        }
        if ($categoryId > 0) {
            $where[]  = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->query(
            "SELECT COUNT(*) AS cnt FROM blog_posts p $whereClause",
            $params
        )->getRowArray()['cnt'];

        $rows = $this->db->query("
            SELECT " . self::COLS . "
            FROM blog_posts p
            LEFT JOIN blog_categories c ON c.id = p.category_id
            $whereClause
            ORDER BY p.published_at DESC, p.created_at DESC
            LIMIT $perPage OFFSET $offset
        ", $params)->getResultArray();

        return [
            'posts' => array_map(fn($r) => BlogPost::fromArray($r), $rows),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function findById(int $id): ?BlogPost
    {
        $row = $this->db->query("
            SELECT " . self::COLS . "
            FROM blog_posts p
            LEFT JOIN blog_categories c ON c.id = p.category_id
            WHERE p.id = ?
        ", [$id])->getRowArray();

        return $row ? BlogPost::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?BlogPost
    {
        $row = $this->db->query("
            SELECT " . self::COLS . "
            FROM blog_posts p
            LEFT JOIN blog_categories c ON c.id = p.category_id
            WHERE p.slug = ?
        ", [$slug])->getRowArray();

        return $row ? BlogPost::fromArray($row) : null;
    }

    public function findRelated(int $categoryId, int $excludeId, int $limit = 3): array
    {
        $rows = $this->db->query("
            SELECT " . self::COLS . "
            FROM blog_posts p
            LEFT JOIN blog_categories c ON c.id = p.category_id
            WHERE p.category_id = ? AND p.id != ? AND p.status = 'published'
            ORDER BY p.published_at DESC
            LIMIT $limit
        ", [$categoryId, $excludeId])->getResultArray();

        return array_map(fn($r) => BlogPost::fromArray($r), $rows);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->db->table('blog_posts')->where('slug', $slug);
        if ($excludeId !== null) $builder->where('id !=', $excludeId);
        return $builder->countAllResults() > 0;
    }

    public function save(BlogPost $post): BlogPost
    {
        $now  = date('Y-m-d H:i:s');
        $data = [
            'slug'        => $post->slug,
            'title'       => $post->title,
            'excerpt'     => $post->excerpt,
            'body'        => $post->body,
            'cover_image' => $post->coverImage,
            'author_name' => $post->authorName,
            'category_id' => $post->categoryId,
            'status'      => $post->status,
            'published_at'=> $post->publishedAt,
            'updated_at'  => $now,
        ];

        if ($post->id === 0) {
            $data['created_at'] = $now;
            $this->db->table('blog_posts')->insert($data);
            $id = (int) $this->db->insertID();
        } else {
            $this->db->table('blog_posts')->where('id', $post->id)->update($data);
            $id = $post->id;
        }

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->db->table('blog_posts')->where('id', $id)->delete();
    }
}
