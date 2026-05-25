<?php

namespace App\Domain\Blog;

interface BlogPostRepositoryInterface
{
    /** @return array{posts: BlogPost[], total: int, pages: int} */
    public function findAll(int $page = 1, int $perPage = 12, string $status = '', int $categoryId = 0): array;

    public function findById(int $id): ?BlogPost;
    public function findBySlug(string $slug): ?BlogPost;

    /** @return BlogPost[] */
    public function findRelated(int $categoryId, int $excludeId, int $limit = 3): array;

    public function slugExists(string $slug, ?int $excludeId = null): bool;
    public function save(BlogPost $post): BlogPost;
    public function delete(int $id): void;
}
