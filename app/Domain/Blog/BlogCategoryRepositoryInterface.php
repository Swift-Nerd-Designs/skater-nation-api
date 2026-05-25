<?php

namespace App\Domain\Blog;

interface BlogCategoryRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?BlogCategory;
    public function findBySlug(string $slug): ?BlogCategory;
    public function slugExists(string $slug, ?int $excludeId = null): bool;
    public function save(BlogCategory $category): BlogCategory;
    public function delete(int $id): void;
}
