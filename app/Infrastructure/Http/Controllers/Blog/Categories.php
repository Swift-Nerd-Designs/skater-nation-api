<?php

namespace App\Infrastructure\Http\Controllers\Blog;

use App\Infrastructure\Http\Controllers\BaseController;

class Categories extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $categories = service('blogCategoryRepository')->findAll();

        return $this->ok([
            'categories' => array_map(fn($c) => $c->toArray(), $categories),
        ]);
    }
}
