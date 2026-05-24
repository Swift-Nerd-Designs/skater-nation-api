<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\DeleteBlogCategoryCommand;
use App\Domain\Blog\BlogCategoryRepositoryInterface;

final class DeleteBlogCategoryHandler
{
    public function __construct(
        private readonly BlogCategoryRepositoryInterface $categories,
    ) {}

    public function handle(DeleteBlogCategoryCommand $cmd): void
    {
        if ($this->categories->findById($cmd->id) === null) {
            throw new \DomainException('Category not found.');
        }
        $this->categories->delete($cmd->id);
    }
}
