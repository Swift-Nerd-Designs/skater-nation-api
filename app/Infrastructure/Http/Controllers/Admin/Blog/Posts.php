<?php

namespace App\Infrastructure\Http\Controllers\Admin\Blog;

use App\Application\Blog\Commands\CreateBlogPostCommand;
use App\Application\Blog\Commands\UpdateBlogPostCommand;
use App\Application\Blog\Commands\DeleteBlogPostCommand;
use App\Infrastructure\Http\Controllers\BaseController;

class Posts extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $page    = (int) ($this->request->getGet('page')     ?: 1);
        $perPage = (int) ($this->request->getGet('per_page') ?: 20);
        $status  = trim($this->request->getGet('status') ?? '');

        $result = service('blogPostRepository')->findAll($page, $perPage, $status);

        return $this->ok([
            'posts'      => array_map(fn($p) => $p->toArray(), $result['posts']),
            'pagination' => ['total' => $result['total'], 'pages' => $result['pages']],
        ]);
    }

    public function show(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $post = service('blogPostRepository')->findById($id);
        if ($post === null) return $this->notFound('Post not found.');
        return $this->ok(['post' => $post->toArray()]);
    }

    public function create(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();
        $title = trim($body['title'] ?? '');
        if ($title === '') return $this->error('title is required.', 400);

        try {
            $post = service('createBlogPostHandler')->handle(new CreateBlogPostCommand(
                title:      $title,
                excerpt:    $body['excerpt']    ?? null,
                body:       $body['body']       ?? null,
                coverImage: $body['cover_image'] ?? null,
                authorName: $body['author_name'] ?? null,
                categoryId: isset($body['category_id']) ? (int) $body['category_id'] : null,
                status:     $body['status']     ?? 'draft',
            ));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->json(['post' => $post->toArray()], 201);
    }

    public function update(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();

        try {
            $post = service('updateBlogPostHandler')->handle(new UpdateBlogPostCommand(
                id:          $id,
                title:       $body['title']       ?? null,
                excerpt:     $body['excerpt']      ?? null,
                body:        $body['body']         ?? null,
                coverImage:  $body['cover_image']  ?? null,
                setCover:    array_key_exists('cover_image', $body),
                authorName:  $body['author_name']  ?? null,
                categoryId:  array_key_exists('category_id', $body) && $body['category_id'] !== null
                                 ? (int) $body['category_id'] : null,
                setCategory: array_key_exists('category_id', $body),
                status:      $body['status']       ?? null,
            ));
        } catch (\DomainException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->ok(['post' => $post->toArray()]);
    }

    public function publish(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $post = service('blogPostRepository')->findById($id);
        if ($post === null) return $this->notFound('Post not found.');

        $newStatus = $post->isPublished() ? 'draft' : 'published';

        try {
            $post = service('updateBlogPostHandler')->handle(new UpdateBlogPostCommand(
                id:     $id,
                status: $newStatus,
            ));
        } catch (\DomainException $e) {
            return $this->notFound($e->getMessage());
        }

        return $this->ok(['post' => $post->toArray()]);
    }

    public function delete(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            service('deleteBlogPostHandler')->handle(new DeleteBlogPostCommand($id));
        } catch (\DomainException $e) {
            return $this->notFound($e->getMessage());
        }
        return $this->ok();
    }
}
