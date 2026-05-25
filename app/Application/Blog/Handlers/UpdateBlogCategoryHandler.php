<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\UpdateBlogCategoryCommand;
use App\Domain\Blog\BlogCategory;
use App\Domain\Blog\BlogCategoryRepositoryInterface;

final class UpdateBlogCategoryHandler
{
    public function __construct(
        private readonly BlogCategoryRepositoryInterface $categories,
    ) {}

    public function handle(UpdateBlogCategoryCommand $cmd): BlogCategory
    {
        $existing = $this->categories->findById($cmd->id);
        if ($existing === null) throw new \DomainException('Category not found.');

        $name = trim($cmd->name);
        if ($name === '') throw new \InvalidArgumentException('name cannot be empty.');

        $slug = $name !== $existing->name
            ? $this->uniqueSlug($this->slugify($name), $cmd->id)
            : $existing->slug;

        return $this->categories->save(new BlogCategory(id: $cmd->id, slug: $slug, name: $name));
    }

    private function slugify(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
    }

    private function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base; $suffix = 2;
        while ($this->categories->slugExists($slug, $excludeId)) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
