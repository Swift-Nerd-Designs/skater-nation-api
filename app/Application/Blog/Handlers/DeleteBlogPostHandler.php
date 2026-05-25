<?php

namespace App\Application\Blog\Handlers;

use App\Application\Blog\Commands\DeleteBlogPostCommand;
use App\Domain\Blog\BlogPostRepositoryInterface;

final class DeleteBlogPostHandler
{
    public function __construct(
        private readonly BlogPostRepositoryInterface $posts,
    ) {}

    public function handle(DeleteBlogPostCommand $cmd): void
    {
        if ($this->posts->findById($cmd->id) === null) {
            throw new \DomainException('Post not found.');
        }
        $this->posts->delete($cmd->id);
    }
}
