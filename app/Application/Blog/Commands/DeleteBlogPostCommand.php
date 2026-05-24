<?php

namespace App\Application\Blog\Commands;

final class DeleteBlogPostCommand
{
    public function __construct(
        public readonly int $id,
    ) {}
}
