<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use App\Models\Cms\PageVersion;
use App\Models\Cms\ContentType;
use App\Models\Cms\Widget;
use App\Models\Cms\GlobalSection;
use App\Models\Cms\FormSubmission;
use App\Models\Cms\PageAnalytics;
use App\Models\PageTemplate;
use App\Models\PageBlockAsset;
use App\Models\UrlRedirect;
use App\Models\Product;
use App\Models\Category;
use App\Models\Offer;
use App\Services\Cms\PageService;
use App\Services\Cms\VersionService;
use App\Services\Cms\DataBindingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageBuilderController extends Controller
{
    public function __construct(
        protected PageService $pageService,
        protected VersionService $versionService,
        protected DataBindingService $dataBindingService
    ) {}

    // ============ PAGES CRUD ============

    public function index(Request $request): JsonResponse
    {
        $query = Page::with([
            'contentType:id,name,slug,icon',
            'draftVersion:id,page_id,title,version_number',
            'publishedVersion:id,page_id,version_number',
            'creator:id,first_name,last_name',
        ]);

        if ($request->filled('content_type')) {
            $query->whereHas('contentType', fn($q) => $q->where('slug', $request->content_type));
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'published' => $query->published(),
                'draft' => $query->draft(),
                'scheduled' => $query->scheduled(),
                default => null,
            };
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                  ->orWhereHas('draftVersion', fn($qv) => $qv->where('title', 'like', "%{$search}%"));
            });
        }

        $perPage = min($request->input('per_page', 15), 100);
        $pages = $query->orderByDesc('updated_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pages->items(),
            'pagination' => [
                'current_page' => $pages->currentPage(),
                'last_page' => $pages->lastPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::with([
            'contentType',
            'draftVersion.creator:id,first_name,last_name',
            'publishedVersion',
            'creator:id,first_name,last_name',
        ])->find($id);

        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $history = $this->versionService->getHistory($page, 10);

        return response()->json([
            'success' => true,
            'data' => [
                'page' => $page,
                'version_history' => $history,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content_type_id' => 'nullable|exists:content_types,id',
            'widgets' => 'nullable|array',
            'layout_settings' => 'nullable|array',
            'seo' => 'nullable|array',
        ]);

        $slug = $request->slug ?? Str::slug($request->title);
        $count = Page::where('slug', 'like', $slug . '%')->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $page = $this->pageService->create([
            'slug' => $slug,
            'content_type_id' => $request->content_type_id,
            'title' => $request->title,
            'widgets' => $request->widgets ?? [],
            'layout_settings' => $request->layout_settings ?? [],
            'seo' => $request->seo ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully.',
            'data' => $page,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($id)],
            'widgets' => 'sometimes|array',
            'layout_settings' => 'sometimes|array',
            'data_bindings' => 'sometimes|array',
            'seo' => 'sometimes|array',
            'custom_fields' => 'sometimes|array',
            'change_note' => 'nullable|string|max:500',
        ]);

        $version = $this->pageService->update($page, $request->only([
            'title', 'slug', 'widgets', 'layout_settings',
            'data_bindings', 'seo', 'custom_fields', 'change_note',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Page updated. New version created.',
            'data' => [
                'page' => $page->fresh(),
                'version' => $version,
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $this->pageService->delete($page);

        return response()->json(['success' => true, 'message' => 'Page deleted successfully.']);
    }

    public function duplicate(int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $newPage = $this->pageService->duplicate($page);

        return response()->json([
            'success' => true,
            'message' => 'Page duplicated successfully.',
            'data' => $newPage,
        ], 201);
    }

    public function preview(int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $data = $this->pageService->getForPreview($page);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ============ VERSION MANAGEMENT ============

    public function versions(int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $versions = $this->versionService->getHistory($page, 50);

        return response()->json(['success' => true, 'data' => $versions]);
    }

    public function getVersion(int $versionId): JsonResponse
    {
        $version = PageVersion::with('creator:id,first_name,last_name')->find($versionId);
        if (!$version) {
            return response()->json(['success' => false, 'message' => 'Version not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $version]);
    }

    public function publishVersion(int $versionId): JsonResponse
    {
        $version = PageVersion::find($versionId);
        if (!$version) {
            return response()->json(['success' => false, 'message' => 'Version not found.'], 404);
        }

        $this->versionService->publishVersion($version);

        return response()->json([
            'success' => true,
            'message' => 'Version published successfully.',
            'data' => $version->page->fresh(['publishedVersion', 'draftVersion']),
        ]);
    }

    public function unpublish(int $id): JsonResponse
    {
        $page = Page::find($id);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $this->pageService->unpublish($page);

        return response()->json(['success' => true, 'message' => 'Page unpublished.']);
    }

    public function revertVersion(Request $request, int $pageId, int $versionId): JsonResponse
    {
        $page = Page::find($pageId);
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $version = $this->pageService->revert($page, $versionId, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Reverted to version. New version created.',
            'data' => $version,
        ]);
    }

    public function compareVersions(Request $request): JsonResponse
    {
        $request->validate([
            'version_1' => 'required|exists:page_versions_kernel,id',
            'version_2' => 'required|exists:page_versions_kernel,id',
        ]);

        $v1 = PageVersion::findOrFail($request->version_1);
        $v2 = PageVersion::findOrFail($request->version_2);

        $comparison = $this->versionService->compareVersions($v1, $v2);

        return response()->json(['success' => true, 'data' => $comparison]);
    }

    public function scheduleVersion(Request $request, int $versionId): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $version = PageVersion::find($versionId);
        if (!$version) {
            return response()->json(['success' => false, 'message' => 'Version not found.'], 404);
        }

        $this->versionService->scheduleVersion($version, \Carbon\Carbon::parse($request->scheduled_at));

        return response()->json([
            'success' => true,
            'message' => 'Version scheduled for publish.',
            'data' => $version->page->fresh(),
        ]);
    }

    // ============ CONTENT TYPES ============

    public function contentTypes(): JsonResponse
    {
        $types = ContentType::active()->get();
        return response()->json(['success' => true, 'data' => $types]);
    }

    // ============ WIDGETS ============

    public function widgets(): JsonResponse
    {
        $widgets = Widget::active()->ordered()->get();

        // Build categories metadata
        $categoryMeta = [
            'layout' => ['label' => 'Layout', 'icon' => 'bi-grid', 'order' => 1],
            'content' => ['label' => 'Content', 'icon' => 'bi-file-text', 'order' => 2],
            'media' => ['label' => 'Media', 'icon' => 'bi-image', 'order' => 3],
            'ecommerce' => ['label' => 'E-commerce', 'icon' => 'bi-cart', 'order' => 4],
            'conversion' => ['label' => 'Conversion', 'icon' => 'bi-bullseye', 'order' => 5],
            'social' => ['label' => 'Social Proof', 'icon' => 'bi-people', 'order' => 6],
            'forms' => ['label' => 'Forms', 'icon' => 'bi-envelope', 'order' => 7],
            'advanced' => ['label' => 'Advanced', 'icon' => 'bi-code-slash', 'order' => 8],
        ];

        // Build widgets keyed by identifier with the format expected by frontend
        $widgetsByIdentifier = [];
        $usedCategories = [];

        foreach ($widgets as $widget) {
            $identifier = str_replace('-', '_', $widget->identifier); // Convert kebab-case to snake_case
            $usedCategories[$widget->category] = true;

            $widgetsByIdentifier[$identifier] = [
                'name' => $widget->name,
                'description' => $widget->description ?? '',
                'icon' => $widget->icon ?? 'bi-square',
                'category' => $widget->category,
                'supports_data_binding' => $widget->supports_data_binding,
                'data_provider_types' => $widget->data_provider_types ?? [],
                'default_data' => $widget->default_config ?? [],
                'default_settings' => [],
                'admin_component' => $widget->admin_component,
                'frontend_component' => $widget->frontend_component,
            ];
        }

        // Only include categories that have widgets
        $categories = array_filter($categoryMeta, function ($key) use ($usedCategories) {
            return isset($usedCategories[$key]);
        }, ARRAY_FILTER_USE_KEY);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'widgets' => $widgetsByIdentifier,
                'blocks' => $widgetsByIdentifier, // Alias for backward compatibility
            ],
        ]);
    }

    public function widgetSchema(string $identifier): JsonResponse
    {
        $widget = Widget::where('identifier', $identifier)->first();
        if (!$widget) {
            return response()->json(['success' => false, 'message' => 'Widget not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'identifier' => $widget->identifier,
                'name' => $widget->name,
                'config_schema' => $widget->config_schema,
                'default_config' => $widget->default_config,
                'supports_data_binding' => $widget->supports_data_binding,
            ],
        ]);
    }

    // ============ DATA PROVIDERS ============

    public function dataProviders(): JsonResponse
    {
        $providers = $this->dataBindingService->getAvailableProviders();
        return response()->json(['success' => true, 'data' => $providers]);
    }

    public function dataProviderSchema(string $identifier): JsonResponse
    {
        $schema = $this->dataBindingService->getProviderSchema($identifier);
        if (!$schema) {
            return response()->json(['success' => false, 'message' => 'Provider not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $schema]);
    }

    public function previewDataBinding(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string',
            'config' => 'required|array',
        ]);

        $result = $this->dataBindingService->previewBinding([
            'provider' => $request->provider,
            'config' => $request->config,
        ]);

        return response()->json(['success' => true, 'data' => $result->toArray()]);
    }

    // ============ ASSETS ============

    public function uploadAsset(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm',
            'page_id' => 'nullable|exists:pages,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('cms/assets/' . date('Y/m'), 'public');

        $asset = PageBlockAsset::create([
            'page_id' => $request->page_id,
            'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'metadata' => ['original_name' => $file->getClientOriginalName()],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'url' => asset('storage/' . $path),
                'path' => $path,
                'type' => $asset->type,
            ],
        ]);
    }

    // ============ TEMPLATES ============

    public function templates(Request $request): JsonResponse
    {
        $query = PageTemplate::where('is_active', true);
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        return response()->json(['success' => true, 'data' => $query->orderBy('name')->get()]);
    }

    // ============ GLOBAL SECTIONS ============

    public function globalSections(): JsonResponse
    {
        $sections = GlobalSection::with(['publishedVersion', 'draftVersion'])->get();
        return response()->json([
            'success' => true,
            'data' => [
                'headers' => $sections->where('type', 'header')->values(),
                'footers' => $sections->where('type', 'footer')->values(),
            ],
        ]);
    }

    // ============ E-COMMERCE DATA ============

    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::select('id', 'name', 'sku', 'main_image', 'final_price')
                        ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json(['success' => true, 'data' => $query->limit(50)->get()]);
    }

    public function getCategories(): JsonResponse
    {
        $categories = Category::select('id', 'name', 'parent_id', 'slug', 'image')
                              ->where('status', 'A')
                              ->withCount('products')
                              ->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function getOffers(): JsonResponse
    {
        $offers = Offer::select('id', 'title', 'coupon_code', 'discount_type', 'discount_amount', 'discount_percent', 'valid_from', 'valid_to')
                       ->where('status', 'active')
                       ->get();
        return response()->json(['success' => true, 'data' => $offers]);
    }

    // ============ FORM SUBMISSIONS ============

    public function formSubmissions(Request $request): JsonResponse
    {
        $query = FormSubmission::with('page:id,slug');

        if ($request->filled('page_id')) {
            $query->where('page_id', $request->page_id);
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        $perPage = min($request->input('per_page', 20), 100);
        $submissions = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'pagination' => [
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
            'unread_count' => FormSubmission::unread()->count(),
        ]);
    }

    // ============ ANALYTICS ============

    public function analyticsOverview(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        $totalViews = PageAnalytics::where('date', '>=', $startDate)->sum('views');
        $totalUnique = PageAnalytics::where('date', '>=', $startDate)->sum('unique_visitors');

        $topPages = PageAnalytics::selectRaw('page_id, SUM(views) as total_views')
            ->where('date', '>=', $startDate)
            ->groupBy('page_id')
            ->orderByDesc('total_views')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_views' => $totalViews,
                    'unique_visitors' => $totalUnique,
                ],
                'top_pages' => $topPages,
            ],
        ]);
    }
}
