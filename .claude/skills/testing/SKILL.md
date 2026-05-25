# Testing — CodeIgniter 4 Feature Tests

You are writing **feature tests** for the Skater Nation API — a CodeIgniter 4 REST API with a layered architecture (Domain → Application → Infrastructure).

## Working Directory Rule
All paths are relative to `skater-nation-api/`. Never touch `skater-nation-site/`.

---

## Stack

| Tool | Version | Purpose |
|------|---------|---------|
| PHPUnit | ^10.5 | Test runner |
| CI4 `FeatureTestTrait` | 4.5 | Full HTTP request/response cycle |
| CI4 `DatabaseTestTrait` | 4.5 | Migrate + refresh test DB per class |
| MySQL `client_cms_test` | — | Isolated test database |

Run command: `./vendor/bin/phpunit` (from `skater-nation-api/`)

---

## Test Database Setup

**One-time setup** (run once per machine):
```bash
mysql -u root -P 3307 -e "CREATE DATABASE IF NOT EXISTS client_cms_test;"
```

The `tests` DB group in `app/Config/Database.php` points to `client_cms_test` on port 3307.
All `.env` test credentials live under `database.tests.*`.

CI4's `DatabaseTestTrait` automatically:
- Runs all pending migrations before the first test in each class (`$migrate = true`)
- Rolls back to a clean state between tests (`$refresh = true`)

**Never run tests against the dev database** (`skater_nation_db`).

---

## File Structure

```
tests/
  Feature/
    BaseFeatureTest.php          ← Extend this — never extend CIUnitTestCase directly
    Blog/
      BlogPostsTest.php          ← Example: public blog endpoints
    Shop/
      ProductsTest.php
      OrdersTest.php
    Admin/
      AdminBlogPostsTest.php
```

Group tests by domain/controller, mirroring `app/Infrastructure/Http/Controllers/`.

---

## Base Class

Always extend `Tests\Feature\BaseFeatureTest`. It provides:

```php
// HTTP helpers (via FeatureTestTrait)
$response = $this->get('blog/posts');
$response = $this->post('blog/posts', $this->json(['title' => 'Hello']));
$response = $this->put('admin/blog/posts/1', $this->json([...]));
$response = $this->patch('admin/blog/posts/1/publish');
$response = $this->delete('admin/blog/posts/1');

// Response assertion helpers
$this->assertOk($response);                          // 200 + ok:true
$body = $this->assertJsonStatus($response, 201);     // assert status, return decoded body

// DB helpers
$id  = $this->insertRow('blog_posts', [...]);        // insert + return ID
$row = $this->fetchRow('blog_posts', ['id' => $id]); // fetch single row

// JSON body builder
$opts = $this->json(['title' => 'Hello']);           // Content-Type header + encoded body
```

---

## Test Class Pattern

```php
<?php

namespace Tests\Feature\Blog;

use Tests\Feature\BaseFeatureTest;

class BlogPostsTest extends BaseFeatureTest
{
    // Override if this class needs a specific DB seed — usually leave empty
    // protected $seed = MySeeder::class;

    public function test_index_returns_empty_list(): void
    {
        $response = $this->get('blog/posts');
        $body     = $this->assertJsonStatus($response, 200);

        $this->assertSame([], $body['posts']);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->get('blog/posts/does-not-exist');
        $response->assertStatus(404);
    }
}
```

### Naming conventions
- Class: `{Domain}{Resource}Test` — e.g. `BlogPostsTest`, `AdminOrdersTest`
- Method: `test_{verb}_{condition}_{expected_outcome}` — all lowercase snake_case
- Examples: `test_index_returns_only_published_posts`, `test_create_returns_422_when_title_missing`

---

## What to Test (Priority Order)

### 1. Happy path
- Correct status code and response shape for the main use case
- Returned data matches what was inserted

### 2. Access control
- Admin endpoints return 401 when no session cookie is set
- Public endpoints return data without auth

### 3. Validation errors
- Missing required fields → 422 with meaningful error
- Invalid types → 422

### 4. Not-found / edge cases
- Unknown slug/ID → 404
- Draft posts hidden from public endpoints → 404

### 5. State transitions
- Publish toggle changes `status` and sets `published_at`
- Delete removes the row (or archives it)

---

## Inserting Test Data

Always insert directly via `$this->insertRow()` — never call handlers or repositories from tests. This keeps tests fast and decoupled.

```php
// Insert a published blog post
$catId = $this->insertRow('blog_categories', [
    'slug' => 'tricks', 'name' => 'Tricks',
    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
]);
$postId = $this->insertRow('blog_posts', [
    'slug'         => 'kickflip-tutorial',
    'title'        => 'Kickflip Tutorial',
    'status'       => 'published',
    'category_id'  => $catId,
    'published_at' => date('Y-m-d H:i:s'),
    'created_at'   => date('Y-m-d H:i:s'),
    'updated_at'   => date('Y-m-d H:i:s'),
]);
```

---

## Testing Admin Endpoints

Admin routes use the `adminauth` filter (session cookie). To test them, bypass the filter by overriding it in the test environment, **or** seed an admin session. The recommended approach is to mock the filter:

```php
// In BaseFeatureTest or a specific admin test class
protected function withAdminSession(): void
{
    // Set a fake admin session so AdminAuth filter passes
    $session = \Config\Services::session();
    $session->set('admin_id', 1);
}
```

Then call `$this->withAdminSession()` before each admin request.

> If the AdminAuth filter reads from DB, insert a matching admin row first.

---

## Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Single file
./vendor/bin/phpunit tests/Feature/Blog/BlogPostsTest.php

# Single test method
./vendor/bin/phpunit --filter test_show_returns_404_for_draft_post

# With coverage report (requires Xdebug or PCOV)
./vendor/bin/phpunit --coverage-text
```

---

## Common Pitfalls

| Problem | Fix |
|---------|-----|
| Tests hit dev DB | Check `$DBGroup = 'tests'` in BaseFeatureTest |
| Migration not found | Run `php spark migrate` once manually to verify migrations are valid |
| Port mismatch | Ensure `database.tests.port = 3307` in `.env` matches MySQL port |
| Filter blocks all requests | Bypass auth filters per test or use `withAdminSession()` helper |
| `$refresh = true` too slow | Acceptable trade-off for correctness; use `$seed` + manual truncation only if proven bottleneck |
| Auto-routing catches test routes | Confirm `$routes->setAutoRoute(false)` in `app/Config/Routing.php` |

---

## Workflow — Writing Tests for a New Feature

1. **Read the controller** — identify endpoints, inputs, response shape
2. **Create** `tests/Feature/{Domain}/{Resource}Test.php` extending `BaseFeatureTest`
3. **Write happy-path test first** — verify shape and status
4. **Add access control tests** — 401 for unauthenticated admin calls
5. **Add validation tests** — 422 for missing/invalid fields
6. **Add edge cases** — 404, empty list, filtering
7. **Run** `./vendor/bin/phpunit tests/Feature/{Domain}/{Resource}Test.php`
8. **All green → commit** with test file alongside the feature code
