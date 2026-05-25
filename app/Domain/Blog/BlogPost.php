<?php

namespace App\Domain\Blog;

final class BlogPost
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $slug,
        public readonly string  $title,
        public readonly ?string $excerpt,
        public readonly ?string $body,
        public readonly ?string $coverImage,
        public readonly ?string $authorName,
        public readonly ?int    $categoryId,
        public readonly ?string $categoryName,
        public readonly ?string $categorySlug,
        public readonly string  $status,
        public readonly ?string $publishedAt,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id:           (int) $row['id'],
            slug:         $row['slug'],
            title:        $row['title'],
            excerpt:      $row['excerpt'] ?? null,
            body:         $row['body'] ?? null,
            coverImage:   $row['cover_image'] ?? null,
            authorName:   $row['author_name'] ?? null,
            categoryId:   isset($row['category_id']) ? (int) $row['category_id'] : null,
            categoryName: $row['category_name'] ?? null,
            categorySlug: $row['category_slug'] ?? null,
            status:       $row['status'] ?? 'draft',
            publishedAt:  $row['published_at'] ?? null,
            createdAt:    $row['created_at'] ?? '',
            updatedAt:    $row['updated_at'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'slug'          => $this->slug,
            'title'         => $this->title,
            'excerpt'       => $this->excerpt,
            'body'          => $this->body,
            'cover_image'   => $this->coverImage,
            'author_name'   => $this->authorName,
            'category_id'   => $this->categoryId,
            'category_name' => $this->categoryName,
            'category_slug' => $this->categorySlug,
            'status'        => $this->status,
            'published_at'  => $this->publishedAt,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
        ];
    }
}
