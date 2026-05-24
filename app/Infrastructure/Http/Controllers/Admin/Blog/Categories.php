<?php

namespace App\Infrastructure\Http\Controllers\Admin\Blog;

use App\Application\Blog\Commands\CreateBlogCategoryCommand;
use App\Application\Blog\Commands\UpdateBlogCategoryCommand;
use App\Application\Blog\Commands\DeleteBlogCategoryCommand;
use App\Infrastructure\Http\Controllers\BaseController;

class Categories extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $categories = service('blogCategoryRepository')->findAll();
        return $this->ok(['categories' => array_map(fn($c) => $c->toArray(), $categories)]);
    }

    public function create(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();
        $name = trim($body['name'] ?? '');
        if ($name === '') return $this->error('name is required.', 400);

        try {
            $cat = service('createBlogCategoryHandler')->handle(new CreateBlogCategoryCommand($name));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->json(['category' => $cat->toArray()], 201);
    }

    public function update(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = $this->jsonBody();
        $name = trim($body['name'] ?? '');
        if ($name === '') return $this->error('name is required.', 400);

        try {
            $cat = service('updateBlogCategoryHandler')->handle(new UpdateBlogCategoryCommand($id, $name));
        } catch (\DomainException $e) {
            return $this->notFound($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->ok(['category' => $cat->toArray()]);
    }

    public function delete(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            service('deleteBlogCategoryHandler')->handle(new DeleteBlogCategoryCommand($id));
        } catch (\DomainException $e) {
            return $this->notFound($e->getMessage());
        }
        return $this->ok();
    }
}
