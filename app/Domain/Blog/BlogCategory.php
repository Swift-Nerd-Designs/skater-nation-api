<?php

namespace App\Domain\Blog;

final class BlogCategory
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $slug,
        public readonly string  $name,
        public readonly int     $postCount = 0,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            slug:      $row['slug'],
            name:      $row['name'],
            postCount: (int) ($row['post_count'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'post_count' => $this->postCount,
        ];
    }
}
