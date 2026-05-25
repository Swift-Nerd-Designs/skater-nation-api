<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\CreateBlogPostCommand;
use App\Domain\Blog\BlogPost;
use App\Domain\Blog\BlogPostRepositoryInterface;

final class CreateBlogPostHandler
{
    public function __construct(
        private readonly BlogPostRepositoryInterface $posts,
    ) {}

    public function handle(CreateBlogPostCommand $cmd): BlogPost
    {
        $title = trim($cmd->title);
        if ($title === '') throw new \InvalidArgumentException('title cannot be empty.');

        $slug        = $this->uniqueSlug($this->slugify($title));
        $publishedAt = $cmd->status === 'published' ? date('Y-m-d H:i:s') : null;

        return $this->posts->save(new BlogPost(
            id:           0,
            slug:         $slug,
            title:        $title,
            excerpt:      $cmd->excerpt,
            body:         $cmd->body,
            coverImage:   $cmd->coverImage,
            authorName:   $cmd->authorName,
            categoryId:   $cmd->categoryId,
            categoryName: null,
            categorySlug: null,
            status:       $cmd->status,
            publishedAt:  $publishedAt,
            createdAt:    '',
            updatedAt:    '',
        ));
    }

    private function slugify(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base; $suffix = 2;
        while ($this->posts->slugExists($slug)) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
