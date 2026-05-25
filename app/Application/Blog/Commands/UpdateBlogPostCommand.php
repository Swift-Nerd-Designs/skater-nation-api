<?php

namespace App\Application\Blog\Commands;

final class UpdateBlogPostCommand
{
    public function __construct(
        public readonly int     $id,
        public readonly ?string $title       = null,
        public readonly ?string $excerpt     = null,
        public readonly ?string $body        = null,
        public readonly ?string $coverImage  = null,
        public readonly bool    $setCover    = false,
        public readonly ?string $authorName  = null,
        public readonly ?int    $categoryId  = null,
        public readonly bool    $setCategory = false,
        public readonly ?string $status      = null,
    ) {}
}
