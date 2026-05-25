<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\CreateBlogCategoryCommand;
use App\Domain\Blog\BlogCategory;
use App\Domain\Blog\BlogCategoryRepositoryInterface;

final class CreateBlogCategoryHandler
{
    public function __construct(
        private readonly BlogCategoryRepositoryInterface $categories,
    ) {}

    public function handle(CreateBlogCategoryCommand $cmd): BlogCategory
    {
        $name = trim($cmd->name);
        if ($name === '') throw new \InvalidArgumentException('name cannot be empty.');

        $slug = $this->uniqueSlug($this->slugify($name));

        return $this->categories->save(new BlogCategory(id: 0, slug: $slug, name: $name));
    }

    private function slugify(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base; $suffix = 2;
        while ($this->categories->slugExists($slug)) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
