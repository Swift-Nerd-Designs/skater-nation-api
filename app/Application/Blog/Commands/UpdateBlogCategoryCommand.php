<?php

namespace App\Application\Blog\Commands;

final class UpdateBlogCategoryCommand
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
    ) {}
}
