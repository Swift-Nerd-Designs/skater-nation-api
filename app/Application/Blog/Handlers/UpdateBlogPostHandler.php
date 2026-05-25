<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\UpdateBlogPostCommand;
use App\Domain\Blog\BlogPost;
use App\Domain\Blog\BlogPostRepositoryInterface;

final class UpdateBlogPostHandler
{
    public function __construct(
        private readonly BlogPostRepositoryInterface $posts,
    ) {}

    public function handle(UpdateBlogPostCommand $cmd): BlogPost
    {
        $existing = $this->posts->findById($cmd->id);
        if ($existing === null) throw new \DomainException('Post not found.');

        $title = $cmd->title !== null ? trim($cmd->title) : $existing->title;
        if ($title === '') throw new \InvalidArgumentException('title cannot be empty.');

        $slug = ($cmd->title !== null && $title !== $existing->title)
            ? $this->uniqueSlug($this->slugify($title), $cmd->id)
            : $existing->slug;

        $status      = $cmd->status ?? $existing->status;
        $wasPublished = $existing->isPublished();
        $nowPublished = $status === 'published';
        $publishedAt  = ($nowPublished && !$wasPublished) ? date('Y-m-d H:i:s') : $existing->publishedAt;
        if (!$nowPublished) $publishedAt = null;

        return $this->posts->save(new BlogPost(
            id:           $cmd->id,
            slug:         $slug,
            title:        $title,
            excerpt:      $cmd->excerpt      ?? $existing->excerpt,
            body:         $cmd->body         ?? $existing->body,
            coverImage:   $cmd->setCover     ? $cmd->coverImage  : $existing->coverImage,
            authorName:   $cmd->authorName   ?? $existing->authorName,
            categoryId:   $cmd->setCategory  ? $cmd->categoryId  : $existing->categoryId,
            categoryName: null,
            categorySlug: null,
            status:       $status,
            publishedAt:  $publishedAt,
            createdAt:    $existing->createdAt,
            updatedAt:    '',
        ));
    }

    private function slugify(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
    }

    private function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base; $suffix = 2;
        while ($this->posts->slugExists($slug, $excludeId)) $slug = $base . '-' . $suffix++;
        return $slug;
    }
}
