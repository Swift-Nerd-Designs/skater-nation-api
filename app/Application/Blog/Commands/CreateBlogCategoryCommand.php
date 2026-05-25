<?php

namespace App\Application\Blog\Commands;

final class CreateBlogCategoryCommand
{
    public function __construct(
        public readonly string $name,
    ) {}
}
