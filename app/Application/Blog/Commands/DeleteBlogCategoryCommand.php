<?php

namespace App\Application\Blog\Commands;

final class DeleteBlogCategoryCommand
{
    public function __construct(
        public readonly int $id,
    ) {}
}
