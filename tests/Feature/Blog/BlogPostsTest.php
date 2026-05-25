<?php

namespace Tests\Feature\Blog;

use Tests\Support\FeatureTestCase;

/**
 * Feature tests for public blog endpoints.
 *
 * Endpoints covered:
 *   GET /blog/posts          → paginated list of published posts
 *   GET /blog/posts/{slug}   → single post + related posts
 *   GET /blog/categories     → all categories with post counts
 */
class BlogPostsTest extends FeatureTestCase
{
    private function insertCategory(string $slug, string $name): int
    {
        $db = \Config\Database::connect($this->DBGroup);
        $db->table('blog_categories')->insert([
            'slug' => $slug, 'name' => $name,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    private function insertPost(array $fields): int
    {
        $db = \Config\Database::connect($this->DBGroup);
        $db->table('blog_posts')->insert(array_merge([
            'title'      => 'Test Post',
            'status'     => 'published',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $fields));
        return (int) $db->insertID();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect($this->DBGroup);
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        $db->query('TRUNCATE TABLE blog_posts');
        $db->query('TRUNCATE TABLE blog_categories');
        $db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    // -------------------------------------------------------------------------
    // GET /blog/posts
    // -------------------------------------------------------------------------

    public function test_index_returns_empty_list_when_no_posts(): void
    {
        $result = $this->get('blog/posts');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertSame([], $body['posts']);
        $this->assertSame(0, $body['pagination']['total']);
    }

    public function test_index_returns_only_published_posts(): void
    {
        $this->insertPost(['slug' => 'draft-post', 'title' => 'Draft', 'status' => 'draft']);
        $this->insertPost(['slug' => 'live-post',  'title' => 'Live',  'status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);

        $result = $this->get('blog/posts');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertCount(1, $body['posts']);
        $this->assertSame('live-post', $body['posts'][0]['slug']);
    }

    public function test_index_filters_by_category_slug(): void
    {
        $catId = $this->insertCategory('tricks', 'Tricks');
        $this->insertPost(['slug' => 'in-cat',  'published_at' => date('Y-m-d H:i:s'), 'category_id' => $catId]);
        $this->insertPost(['slug' => 'no-cat',  'published_at' => date('Y-m-d H:i:s')]);

        $result = $this->get('blog/posts?category=tricks');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertCount(1, $body['posts']);
        $this->assertSame('in-cat', $body['posts'][0]['slug']);
    }

    public function test_index_unknown_category_returns_empty(): void
    {
        $this->insertPost(['slug' => 'some-post', 'published_at' => date('Y-m-d H:i:s')]);

        $result = $this->get('blog/posts?category=does-not-exist');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertSame([], $body['posts']);
    }

    // -------------------------------------------------------------------------
    // GET /blog/posts/{slug}
    // -------------------------------------------------------------------------

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $result = $this->get('blog/posts/does-not-exist');
        $result->assertStatus(404);
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $this->insertPost(['slug' => 'secret-draft', 'title' => 'Secret', 'status' => 'draft']);

        $result = $this->get('blog/posts/secret-draft');
        $result->assertStatus(404);
    }

    public function test_show_returns_post_data(): void
    {
        $this->insertPost([
            'slug' => 'my-post', 'title' => 'My Post',
            'excerpt' => 'A summary', 'author_name' => 'Thabang',
            'published_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->get('blog/posts/my-post');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertSame('my-post',  $body['post']['slug']);
        $this->assertSame('My Post',  $body['post']['title']);
        $this->assertSame('Thabang',  $body['post']['author_name']);
        $this->assertSame([],         $body['related']);
    }

    public function test_show_returns_related_posts_from_same_category(): void
    {
        $catId = $this->insertCategory('culture', 'Culture');
        $this->insertPost(['slug' => 'main-post',    'published_at' => date('Y-m-d H:i:s'), 'category_id' => $catId]);
        $this->insertPost(['slug' => 'related-post', 'published_at' => date('Y-m-d H:i:s'), 'category_id' => $catId]);

        $result = $this->get('blog/posts/main-post');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertCount(1, $body['related']);
        $this->assertSame('related-post', $body['related'][0]['slug']);
    }

    // -------------------------------------------------------------------------
    // GET /blog/categories
    // -------------------------------------------------------------------------

    public function test_categories_returns_all_categories(): void
    {
        $this->insertCategory('street', 'Street');
        $this->insertCategory('culture', 'Culture');

        $result = $this->get('blog/categories');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $this->assertCount(2, $body['categories']);
    }

    public function test_categories_includes_post_count(): void
    {
        $catId = $this->insertCategory('tricks', 'Tricks');
        $this->insertPost(['slug' => 'flip-trick', 'published_at' => date('Y-m-d H:i:s'), 'category_id' => $catId]);

        $result = $this->get('blog/categories');
        $body   = $this->json($result);

        $result->assertStatus(200);
        $cat = array_values(array_filter($body['categories'], fn($c) => $c['slug'] === 'tricks'))[0];
        $this->assertSame(1, $cat['post_count']);
    }
}
