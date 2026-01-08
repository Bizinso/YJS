<?php

namespace Tests\Feature;

use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\Widget;
use App\Models\Cms\ContentType;
use App\Models\Cms\GlobalSection;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsKernelTest extends TestCase
{
    use RefreshDatabase;

    protected Employee $employee;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an employee for admin access
        $this->employee = Employee::factory()->create([
            'status' => 'A',
        ]);

        $this->token = $this->employee->createToken('test', ['employee'])->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    // ==================== WIDGETS REGISTRY TESTS ====================

    public function test_can_list_widgets(): void
    {
        // Seed some widgets
        Widget::factory()->count(5)->create(['category' => 'content']);
        Widget::factory()->count(3)->create(['category' => 'layout']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/widgets');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'categories',
                    'widgets',
                    'blocks', // Backward compatibility alias
                ],
            ]);
    }

    public function test_widgets_response_includes_categories(): void
    {
        Widget::factory()->create(['category' => 'content']);
        Widget::factory()->create(['category' => 'ecommerce']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/widgets');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('content', $data['categories']);
        $this->assertArrayHasKey('ecommerce', $data['categories']);
    }

    public function test_widgets_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/cms/widgets');

        $response->assertStatus(401);
    }

    // ==================== PAGES CRUD TESTS ====================

    public function test_can_list_pages(): void
    {
        // Create pages using factory
        $page1 = Page::factory()->create();
        $page2 = Page::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/pages');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_create_page(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/cms/pages', [
                'title' => 'New Test Page',
                'slug' => 'new-test-page',
                'widgets' => [
                    [
                        'id' => 'widget_1',
                        'type' => 'hero',
                        'data' => ['title' => 'Welcome'],
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Page created successfully.',
            ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'new-test-page',
        ]);
    }

    public function test_page_creation_generates_unique_slug(): void
    {
        Page::factory()->create(['slug' => 'test-page']);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/cms/pages', [
                'title' => 'Test Page',
            ]);

        $response->assertStatus(201);

        // Get the newly created page from response data
        $responseData = $response->json('data');
        $this->assertNotEquals('test-page', $responseData['slug']);
        $this->assertStringStartsWith('test-page-', $responseData['slug']);
    }

    public function test_can_get_single_page(): void
    {
        $page = Page::factory()->create();
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'title' => 'Test Page Title',
            'version_number' => 1,
        ]);
        $page->update(['draft_version_id' => $version->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/admin/cms/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'page',
                    'version_history',
                ],
            ]);
    }

    public function test_returns_404_for_non_existent_page(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/pages/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Page not found.',
            ]);
    }

    public function test_can_update_page_creates_new_version(): void
    {
        $page = Page::factory()->create();
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'title' => 'Original Title',
            'version_number' => 1,
        ]);
        $page->update(['draft_version_id' => $version->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/admin/cms/pages/{$page->id}", [
                'title' => 'Updated Title',
                'widgets' => [
                    ['id' => 'widget_1', 'type' => 'hero', 'data' => []],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page updated. New version created.',
            ]);

        // Verify new version was created
        $this->assertDatabaseCount('page_versions_kernel', 2);
    }

    public function test_can_delete_page(): void
    {
        $page = Page::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/admin/cms/pages/{$page->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page deleted successfully.',
            ]);

        // Verify soft delete
        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_can_duplicate_page(): void
    {
        $page = Page::factory()->create(['slug' => 'original-page']);
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'title' => 'Original Page',
            'version_number' => 1,
        ]);
        $page->update(['draft_version_id' => $version->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/admin/cms/pages/{$page->id}/duplicate");

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Page duplicated successfully.',
            ]);

        // Verify duplicate was created
        $this->assertDatabaseCount('pages', 2);
    }

    // ==================== VERSION MANAGEMENT TESTS ====================

    public function test_can_list_page_versions(): void
    {
        $page = Page::factory()->create();
        PageVersion::factory()->count(3)->create([
            'page_id' => $page->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/admin/cms/pages/{$page->id}/versions");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_publish_version(): void
    {
        $page = Page::factory()->create();
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'status' => 'draft',
        ]);
        $page->update(['draft_version_id' => $version->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/admin/cms/versions/{$version->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Version published successfully.',
            ]);

        $page->refresh();
        $this->assertEquals($version->id, $page->published_version_id);
    }

    public function test_can_unpublish_page(): void
    {
        $page = Page::factory()->create();
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'status' => 'published',
        ]);
        $page->update([
            'draft_version_id' => $version->id,
            'published_version_id' => $version->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/admin/cms/pages/{$page->id}/unpublish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Page unpublished.',
            ]);

        $page->refresh();
        $this->assertNull($page->published_version_id);
    }

    public function test_can_revert_to_previous_version(): void
    {
        $page = Page::factory()->create();
        $version1 = PageVersion::factory()->create([
            'page_id' => $page->id,
            'version_number' => 1,
            'title' => 'Version 1',
        ]);
        $version2 = PageVersion::factory()->create([
            'page_id' => $page->id,
            'version_number' => 2,
            'title' => 'Version 2',
        ]);
        $page->update(['draft_version_id' => $version2->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/admin/cms/pages/{$page->id}/versions/{$version1->id}/revert", [
                'reason' => 'Reverting to previous version',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reverted to version. New version created.',
            ]);

        // Verify new version was created
        $this->assertDatabaseCount('page_versions_kernel', 3);
    }

    // ==================== PUBLIC PAGE API TESTS ====================

    public function test_public_can_fetch_published_page(): void
    {
        $page = Page::factory()->create(['slug' => 'public-page']);
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'title' => 'Public Page Title',
            'widgets' => [
                ['id' => 'widget_1', 'type' => 'hero', 'data' => ['title' => 'Welcome']],
            ],
            'status' => 'published',
        ]);
        $page->update([
            'draft_version_id' => $version->id,
            'published_version_id' => $version->id,
        ]);

        $response = $this->getJson('/api/pages/public-page');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'slug',
                    'title',
                    'widgets',
                    'blocks', // Alias for frontend compatibility
                ],
            ]);
    }

    public function test_public_returns_404_for_unpublished_page(): void
    {
        $page = Page::factory()->create(['slug' => 'unpublished-page']);
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'status' => 'draft',
        ]);
        $page->update(['draft_version_id' => $version->id]);

        $response = $this->getJson('/api/pages/unpublished-page');

        $response->assertStatus(404);
    }

    public function test_public_page_response_includes_blocks_alias(): void
    {
        $page = Page::factory()->create(['slug' => 'test-blocks']);
        $version = PageVersion::factory()->create([
            'page_id' => $page->id,
            'widgets' => [
                ['id' => 'w1', 'type' => 'hero', 'data' => []],
            ],
            'status' => 'published',
        ]);
        $page->update([
            'draft_version_id' => $version->id,
            'published_version_id' => $version->id,
        ]);

        $response = $this->getJson('/api/pages/test-blocks');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('widgets', $data);
        $this->assertArrayHasKey('blocks', $data);
        $this->assertEquals($data['widgets'], $data['blocks']);
    }

    // ==================== CONTENT TYPES TESTS ====================

    public function test_can_list_content_types(): void
    {
        ContentType::factory()->count(3)->create(['is_active' => true]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/content-types');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ==================== GLOBAL SECTIONS TESTS ====================

    public function test_can_list_global_sections(): void
    {
        GlobalSection::factory()->create(['type' => 'header']);
        GlobalSection::factory()->create(['type' => 'footer']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/global-sections');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'headers',
                    'footers',
                ],
            ]);
    }

    public function test_public_can_fetch_header(): void
    {
        $header = GlobalSection::factory()->create([
            'type' => 'header',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/pages/global/header');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_public_can_fetch_footer(): void
    {
        $footer = GlobalSection::factory()->create([
            'type' => 'footer',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/pages/global/footer');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ==================== ASSETS TESTS ====================

    public function test_can_upload_image_asset(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/cms/assets/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'url',
                    'path',
                    'type',
                ],
            ]);
    }

    // ==================== E-COMMERCE DATA TESTS ====================

    public function test_can_fetch_products_for_binding(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/block-data/products');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_fetch_categories_for_binding(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/block-data/categories');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_fetch_offers_for_binding(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/cms/block-data/offers');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
