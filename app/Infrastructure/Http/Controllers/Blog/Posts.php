<?php

namespace App\Infrastructure\Http\Controllers\Blog;

use App\Infrastructure\Http\Controllers\BaseController;

class Posts extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface
    {
        $page       = (int) ($this->request->getGet('page')     ?: 1);
        $perPage    = (int) ($this->request->getGet('per_page') ?: 12);
        $categorySlug = trim($this->request->getGet('category') ?? '');

        $categoryId  = 0;
        $unknownCat  = false;
        if ($categorySlug !== '') {
            $cat = service('blogCategoryRepository')->findBySlug($categorySlug);
            if ($cat) {
                $categoryId = $cat->id;
            } else {
                $unknownCat = true;
            }
        }

        if ($unknownCat) {
            return $this->ok(['posts' => [], 'pagination' => ['total' => 0, 'pages' => 1]]);
        }

        $result = service('blogPostRepository')->findAll($page, $perPage, 'published', $categoryId);

        return $this->ok([
            'posts'      => array_map(fn($p) => $p->toArray(), $result['posts']),
            'pagination' => ['total' => $result['total'], 'pages' => $result['pages']],
        ]);
    }

    public function show(string $slug): \CodeIgniter\HTTP\ResponseInterface
    {
        $post = service('blogPostRepository')->findBySlug($slug);

        if ($post === null || !$post->isPublished()) {
            return $this->notFound('Post not found.');
        }

        $related = [];
        if ($post->categoryId) {
            $related = array_map(
                fn($p) => $p->toArray(),
                service('blogPostRepository')->findRelated($post->categoryId, $post->id)
            );
        }

        return $this->ok(['post' => $post->toArray(), 'related' => $related]);
    }
}
