<?php

namespace App\Application\Blog\Commands;

final class CreateBlogPostCommand
{
    public function __construct(
        public readonly string  $title,
        public readonly ?string $excerpt     = null,
        public readonly ?string $body        = null,
        public readonly ?string $coverImage  = null,
        public readonly ?string $authorName  = null,
        public readonly ?int    $categoryId  = null,
        public readonly string  $status      = 'draft',
    ) {}
}
