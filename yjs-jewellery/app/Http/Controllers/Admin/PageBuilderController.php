<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\PageTemplate;
use App\Models\PageVersion;
use App\Models\PageBlockAsset;
use App\Models\GlobalSection;
use App\Models\FormSubmission;
use App\Models\UrlRedirect;
use App\Models\PageAnalytics;
use App\Models\Product;
use App\Models\Category;
use App\Models\offers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\Seo\SeoAnalyzerService;
use App\Services\Seo\SitemapService;

class PageBuilderController extends Controller
{
    // ============ PAGES CRUD ============

    /**
     * List all CMS pages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CmsPage::with(['creator:id,first_name,last_name', 'template:id,name']);

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
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

    /**
     * Get a single page for editing.
     */
    public function show(int $id): JsonResponse
    {
        $page = CmsPage::with([
            'template:id,name',
            'creator:id,first_name,last_name',
            'updater:id,first_name,last_name',
            'versions' => fn($q) => $q->orderByDesc('version')->limit(10),
            'versions.creator:id,first_name,last_name',
            'seoAudit',
        ])->find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    /**
     * Create a new page.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug',
            'type' => 'required|in:homepage,static,landing,category',
            'template_id' => 'nullable|exists:page_templates,id',
            'blocks' => 'nullable|array',
            'page_settings' => 'nullable|array',
            'seo' => 'nullable|array',
            'status' => 'nullable|in:draft,published,scheduled',
            'scheduled_for' => 'nullable|date|required_if:status,scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
            // Ensure uniqueness
            $count = CmsPage::where('slug', 'like', $data['slug'] . '%')->count();
            if ($count > 0) {
                $data['slug'] .= '-' . ($count + 1);
            }
        }

        // If using template, copy blocks from template
        if (!empty($data['template_id']) && empty($data['blocks'])) {
            $template = PageTemplate::find($data['template_id']);
            if ($template) {
                $data['blocks'] = $template->blocks ?? [];
                $data['page_settings'] = array_merge(
                    $template->settings ?? [],
                    $data['page_settings'] ?? []
                );
            }
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'draft';

        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        $page = CmsPage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully.',
            'data' => $page->load('template:id,name'),
        ], 201);
    }

    /**
     * Update a page.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($id)],
            'type' => 'sometimes|in:homepage,static,landing,category',
            'blocks' => 'sometimes|array',
            'page_settings' => 'sometimes|array',
            'seo' => 'sometimes|array',
            'status' => 'sometimes|in:draft,published,scheduled',
            'scheduled_for' => 'nullable|date',
            'save_version' => 'boolean',
            'version_note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check if slug is changing and create redirect
        if (isset($data['slug']) && $data['slug'] !== $page->slug) {
            UrlRedirect::createForSlugChange($page->slug, $data['slug']);
        }

        // Save version if requested
        if ($request->boolean('save_version', false)) {
            $page->createVersion($data['version_note'] ?? null);
            $data['version'] = $page->version + 1;
        }

        $data['updated_by'] = auth()->id();

        // Handle publish
        if (isset($data['status']) && $data['status'] === 'published' && !$page->published_at) {
            $data['published_at'] = now();
        }

        unset($data['save_version'], $data['version_note']);
        $page->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully.',
            'data' => $page->fresh()->load('template:id,name'),
        ]);
    }

    /**
     * Delete a page.
     */
    public function destroy(int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        // Don't allow deleting the only homepage
        if ($page->type === 'homepage') {
            $homepageCount = CmsPage::where('type', 'homepage')->count();
            if ($homepageCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the only homepage.',
                ], 400);
            }
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully.',
        ]);
    }

    /**
     * Duplicate a page.
     */
    public function duplicate(int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $newPage = $page->replicate();
        $newPage->title = $page->title . ' (Copy)';
        $newPage->slug = Str::slug($newPage->title) . '-' . Str::random(6);
        $newPage->status = 'draft';
        $newPage->published_at = null;
        $newPage->version = 1;
        $newPage->created_by = auth()->id();
        $newPage->updated_by = auth()->id();
        $newPage->save();

        return response()->json([
            'success' => true,
            'message' => 'Page duplicated successfully.',
            'data' => $newPage,
        ], 201);
    }

    /**
     * Preview page with hydrated data.
     */
    public function preview(int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'page' => $page,
                'blocks' => $page->getBlocksWithData(),
            ],
        ]);
    }

    // ============ VERSIONS ============

    /**
     * Get version history for a page.
     */
    public function versions(int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $versions = $page->versions()
            ->with('creator:id,first_name,last_name')
            ->orderByDesc('version')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $versions,
        ]);
    }

    /**
     * Restore a specific version.
     */
    public function restoreVersion(int $pageId, int $versionId): JsonResponse
    {
        $page = CmsPage::find($pageId);
        $version = PageVersion::where('page_id', $pageId)->find($versionId);

        if (!$page || !$version) {
            return response()->json([
                'success' => false,
                'message' => 'Page or version not found.',
            ], 404);
        }

        $page->restoreVersion($version);

        return response()->json([
            'success' => true,
            'message' => 'Version restored successfully.',
            'data' => $page->fresh(),
        ]);
    }

    // ============ ASSETS ============

    /**
     * Upload asset for page blocks.
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm',
            'page_id' => 'nullable|exists:cms_pages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('cms/assets/' . date('Y/m'), 'public');

        $asset = PageBlockAsset::create([
            'page_id' => $request->page_id,
            'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'url' => $asset->url,
                'path' => $path,
                'type' => $asset->type,
            ],
        ]);
    }

    /**
     * Delete an asset.
     */
    public function deleteAsset(int $id): JsonResponse
    {
        $asset = PageBlockAsset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found.',
            ], 404);
        }

        $asset->deleteFile();
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully.',
        ]);
    }

    // ============ TEMPLATES ============

    /**
     * List templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $query = PageTemplate::active();

        if ($request->filled('category')) {
            $query->ofCategory($request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Create template from page.
     */
    public function createTemplate(Request $request, int $pageId): JsonResponse
    {
        $page = CmsPage::find($pageId);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'required|in:homepage,landing,static,general',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = PageTemplate::create([
            'name' => $request->name,
            'category' => $request->category,
            'blocks' => $page->blocks,
            'settings' => $page->page_settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully.',
            'data' => $template,
        ], 201);
    }

    /**
     * Delete a template.
     */
    public function deleteTemplate(int $id): JsonResponse
    {
        $template = PageTemplate::find($id);

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found.',
            ], 404);
        }

        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system templates.',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully.',
        ]);
    }

    // ============ E-COMMERCE DATA ============

    /**
     * Get products for block configuration.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::select('id', 'name', 'sku', 'main_image', 'final_price')
                        ->where('status', 'active');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->limit(50)->get(),
        ]);
    }

    /**
     * Get categories for block configuration.
     */
    public function getCategories(): JsonResponse
    {
        $categories = Category::select('id', 'name', 'parent_id', 'slug', 'image')
                              ->where('status', 'A')
                              ->withCount('products')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get active offers for block configuration.
     */
    public function getOffers(): JsonResponse
    {
        $offers = offers::select('id', 'title', 'coupon_code', 'discount_type', 'discount_amount', 'discount_percent', 'valid_from', 'valid_to')
                        ->active()
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    }

    /**
     * Get block types configuration.
     */
    public function getBlockTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => config('page-builder.block_types'),
        ]);
    }

    // ============ GLOBAL SECTIONS ============

    /**
     * Get global sections (header/footer).
     */
    public function globalSections(): JsonResponse
    {
        $sections = GlobalSection::all();

        return response()->json([
            'success' => true,
            'data' => [
                'header' => $sections->where('type', 'header')->first(),
                'footer' => $sections->where('type', 'footer')->first(),
            ],
        ]);
    }

    /**
     * Update global section.
     */
    public function updateGlobalSection(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['header', 'footer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid section type.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'blocks' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $section = GlobalSection::updateOrCreate(
            ['type' => $type],
            array_merge(
                ['name' => ucfirst($type)],
                $validator->validated()
            )
        );

        return response()->json([
            'success' => true,
            'message' => ucfirst($type) . ' updated successfully.',
            'data' => $section,
        ]);
    }

    // ============ FORM SUBMISSIONS ============

    /**
     * List form submissions.
     */
    public function formSubmissions(Request $request): JsonResponse
    {
        $query = FormSubmission::with('page:id,title,slug');

        if ($request->filled('page_id')) {
            $query->where('page_id', $request->page_id);
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
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

    /**
     * Get single submission.
     */
    public function showFormSubmission(int $id): JsonResponse
    {
        $submission = FormSubmission::with('page:id,title,slug')->find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission not found.',
            ], 404);
        }

        // Mark as read
        if (!$submission->is_read) {
            $submission->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $submission,
        ]);
    }

    /**
     * Delete submission.
     */
    public function deleteFormSubmission(int $id): JsonResponse
    {
        $submission = FormSubmission::find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission not found.',
            ], 404);
        }

        $submission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Submission deleted successfully.',
        ]);
    }

    /**
     * Export form submissions to CSV.
     */
    public function exportFormSubmissions(Request $request)
    {
        $query = FormSubmission::with('page:id,title,slug');

        if ($request->filled('page_id')) {
            $query->where('page_id', $request->page_id);
        }
        if ($request->filled('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $submissions = $query->orderByDesc('created_at')->get();

        // Build CSV
        $headers = ['ID', 'Page', 'Submitted At', 'Read', 'IP Address'];
        $formFields = [];

        // Collect all unique form fields
        foreach ($submissions as $submission) {
            if (is_array($submission->form_data)) {
                foreach (array_keys($submission->form_data) as $key) {
                    if (!in_array($key, $formFields)) {
                        $formFields[] = $key;
                    }
                }
            }
        }

        $headers = array_merge($headers, $formFields);

        $csv = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";

        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->page?->title ?? 'Unknown',
                $submission->created_at->format('Y-m-d H:i:s'),
                $submission->is_read ? 'Yes' : 'No',
                $submission->ip_address ?? '',
            ];

            foreach ($formFields as $field) {
                $value = $submission->form_data[$field] ?? '';
                $row[] = is_string($value) ? $value : json_encode($value);
            }

            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="form-submissions.csv"',
        ]);
    }

    // ============ URL REDIRECTS ============

    /**
     * List URL redirects.
     */
    public function redirects(Request $request): JsonResponse
    {
        $query = UrlRedirect::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('old_url', 'like', "%{$search}%")
                  ->orWhere('new_url', 'like', "%{$search}%");
            });
        }

        $redirects = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $redirects->items(),
            'pagination' => [
                'current_page' => $redirects->currentPage(),
                'last_page' => $redirects->lastPage(),
                'per_page' => $redirects->perPage(),
                'total' => $redirects->total(),
            ],
        ]);
    }

    /**
     * Create redirect.
     */
    public function createRedirect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_url' => 'required|string|max:255|unique:url_redirects,old_url',
            'new_url' => 'required|string|max:255',
            'type' => 'required|in:301,302',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $redirect = UrlRedirect::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Redirect created successfully.',
            'data' => $redirect,
        ], 201);
    }

    /**
     * Update redirect.
     */
    public function updateRedirect(Request $request, int $id): JsonResponse
    {
        $redirect = UrlRedirect::find($id);

        if (!$redirect) {
            return response()->json([
                'success' => false,
                'message' => 'Redirect not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'old_url' => ['sometimes', 'string', 'max:255', Rule::unique('url_redirects', 'old_url')->ignore($id)],
            'new_url' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:301,302',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $redirect->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Redirect updated successfully.',
            'data' => $redirect,
        ]);
    }

    /**
     * Delete redirect.
     */
    public function deleteRedirect(int $id): JsonResponse
    {
        $redirect = UrlRedirect::find($id);

        if (!$redirect) {
            return response()->json([
                'success' => false,
                'message' => 'Redirect not found.',
            ], 404);
        }

        $redirect->delete();

        return response()->json([
            'success' => true,
            'message' => 'Redirect deleted successfully.',
        ]);
    }

    // ============ SEO ============

    /**
     * Analyze SEO for a page.
     */
    public function analyzeSeo(int $pageId, SeoAnalyzerService $analyzer): JsonResponse
    {
        $page = CmsPage::find($pageId);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $results = $analyzer->analyze($page);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Run SEO audit and save results.
     */
    public function runSeoAudit(int $pageId, SeoAnalyzerService $analyzer): JsonResponse
    {
        $page = CmsPage::find($pageId);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $audit = $analyzer->analyzeAndSave($page);

        return response()->json([
            'success' => true,
            'message' => 'SEO audit completed.',
            'data' => $audit,
        ]);
    }

    /**
     * Get SEO dashboard data.
     */
    public function seoDashboard(): JsonResponse
    {
        $pages = CmsPage::with('seoAudit')
            ->where('status', 'published')
            ->get();

        $stats = [
            'total_pages' => $pages->count(),
            'pages_with_issues' => 0,
            'average_score' => 0,
            'critical_issues' => 0,
        ];

        $scoreSum = 0;
        $pageIssues = [];

        foreach ($pages as $page) {
            if ($page->seoAudit) {
                $scoreSum += $page->seoAudit->score;

                $issues = $page->seoAudit->issues ?? [];
                $criticalCount = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'critical'));

                if (count($issues) > 0) {
                    $stats['pages_with_issues']++;
                }
                $stats['critical_issues'] += $criticalCount;

                $pageIssues[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'score' => $page->seoAudit->score,
                    'issues_count' => count($issues),
                    'critical_count' => $criticalCount,
                    'last_audit' => $page->seoAudit->last_audit_at,
                ];
            } else {
                $pageIssues[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'score' => null,
                    'issues_count' => null,
                    'critical_count' => null,
                    'last_audit' => null,
                ];
            }
        }

        if ($pages->count() > 0) {
            $auditedCount = $pages->filter(fn($p) => $p->seoAudit)->count();
            $stats['average_score'] = $auditedCount > 0 ? round($scoreSum / $auditedCount) : 0;
        }

        // Sort by score (null/lowest first)
        usort($pageIssues, function ($a, $b) {
            if ($a['score'] === null) return -1;
            if ($b['score'] === null) return 1;
            return $a['score'] - $b['score'];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'pages' => $pageIssues,
            ],
        ]);
    }

    /**
     * Generate sitemap.
     */
    public function generateSitemap(SitemapService $sitemapService): JsonResponse
    {
        try {
            $url = $sitemapService->generateAndSave();

            return response()->json([
                'success' => true,
                'message' => 'Sitemap generated successfully.',
                'data' => [
                    'url' => $url,
                    'url_count' => $sitemapService->getUrlCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate sitemap: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sitemap info.
     */
    public function sitemapInfo(): JsonResponse
    {
        $path = storage_path('app/public/sitemap.xml');
        $exists = file_exists($path);

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'url' => $exists ? asset('storage/sitemap.xml') : null,
                'last_modified' => $exists ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'size' => $exists ? filesize($path) : null,
            ],
        ]);
    }

    // ============ ANALYTICS ============

    /**
     * Get analytics overview.
     */
    public function analyticsOverview(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);
        $endDate = now();

        // Aggregate stats
        $totalViews = PageAnalytics::where('date', '>=', $startDate)->sum('views');
        $totalUnique = PageAnalytics::where('date', '>=', $startDate)->sum('unique_visitors');

        // Daily data for chart
        $dailyData = PageAnalytics::selectRaw('date, SUM(views) as views, SUM(unique_visitors) as unique_visitors')
            ->where('date', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($d) => [
                'date' => $d->date->format('Y-m-d'),
                'views' => $d->views,
                'unique_visitors' => $d->unique_visitors,
            ]);

        // Top pages
        $topPages = PageAnalytics::selectRaw('page_id, SUM(views) as total_views, SUM(unique_visitors) as total_unique')
            ->where('date', '>=', $startDate)
            ->groupBy('page_id')
            ->orderByDesc('total_views')
            ->limit(10)
            ->with('page:id,title,slug,type')
            ->get()
            ->map(fn($p) => [
                'page_id' => $p->page_id,
                'title' => $p->page?->title ?? 'Unknown',
                'slug' => $p->page?->slug,
                'type' => $p->page?->type,
                'views' => $p->total_views,
                'unique_visitors' => $p->total_unique,
            ]);

        // Previous period comparison
        $prevStartDate = now()->subDays($days * 2);
        $prevEndDate = $startDate;
        $prevViews = PageAnalytics::whereBetween('date', [$prevStartDate, $prevEndDate])->sum('views');

        $viewsChange = $prevViews > 0 ? round(($totalViews - $prevViews) / $prevViews * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $days,
                ],
                'stats' => [
                    'total_views' => $totalViews,
                    'unique_visitors' => $totalUnique,
                    'views_change' => $viewsChange,
                    'avg_daily_views' => $days > 0 ? round($totalViews / $days) : 0,
                ],
                'daily_data' => $dailyData,
                'top_pages' => $topPages,
            ],
        ]);
    }

    /**
     * Get analytics for a specific page.
     */
    public function pageAnalytics(Request $request, int $id): JsonResponse
    {
        $page = CmsPage::find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.',
            ], 404);
        }

        $days = $request->input('days', 30);
        $startDate = now()->subDays($days);

        // Get daily data
        $dailyData = PageAnalytics::forPage($id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->map(fn($d) => [
                'date' => $d->date->format('Y-m-d'),
                'views' => $d->views,
                'unique_visitors' => $d->unique_visitors,
                'avg_time_on_page' => $d->avg_time_on_page,
                'bounce_rate' => $d->bounce_rate,
            ]);

        // Totals
        $totals = PageAnalytics::forPage($id)
            ->where('date', '>=', $startDate)
            ->selectRaw('SUM(views) as total_views, SUM(unique_visitors) as total_unique, AVG(avg_time_on_page) as avg_time, AVG(bounce_rate) as avg_bounce')
            ->first();

        // All-time totals
        $allTime = PageAnalytics::forPage($id)
            ->selectRaw('SUM(views) as total_views, MIN(date) as first_tracked')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'type' => $page->type,
                ],
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => now()->format('Y-m-d'),
                    'days' => $days,
                ],
                'stats' => [
                    'total_views' => $totals->total_views ?? 0,
                    'unique_visitors' => $totals->total_unique ?? 0,
                    'avg_time_on_page' => round($totals->avg_time ?? 0, 1),
                    'bounce_rate' => round($totals->avg_bounce ?? 0, 1),
                    'all_time_views' => $allTime->total_views ?? 0,
                    'first_tracked' => $allTime->first_tracked,
                ],
                'daily_data' => $dailyData,
            ],
        ]);
    }
}
