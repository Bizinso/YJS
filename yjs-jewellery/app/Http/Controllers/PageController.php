<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\GlobalSection;
use App\Models\FormSubmission;
use App\Models\PageAnalytics;
use App\Models\UrlRedirect;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    /**
     * Get the homepage.
     */
    public function homepage(): JsonResponse
    {
        $page = CmsPage::where('type', 'homepage')
            ->published()
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Homepage not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPageForFrontend($page),
        ]);
    }

    /**
     * Get a page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        // First check for redirects
        $redirect = UrlRedirect::findForUrl('/' . $slug);

        if ($redirect) {
            $redirect->incrementHits();

            return response()->json([
                'success' => true,
                'redirect' => true,
                'redirect_url' => $redirect->new_url,
                'redirect_type' => $redirect->getHttpStatusCode(),
            ]);
        }

        $page = CmsPage::where('slug', $slug)
            ->published()
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPageForFrontend($page),
        ]);
    }

    /**
     * Get active header section.
     */
    public function header(): JsonResponse
    {
        $header = GlobalSection::getActiveHeader();

        if (!$header) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $header->id,
                'name' => $header->name,
                'blocks' => $header->blocks,
                'settings' => $header->settings,
            ],
        ]);
    }

    /**
     * Get active footer section.
     */
    public function footer(): JsonResponse
    {
        $footer = GlobalSection::getActiveFooter();

        if (!$footer) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $footer->id,
                'name' => $footer->name,
                'blocks' => $footer->blocks,
                'settings' => $footer->settings,
            ],
        ]);
    }

    /**
     * Submit a form from a page block.
     */
    public function submitForm(Request $request, int $pageId, string $blockId): JsonResponse
    {
        // Rate limiting: 5 submissions per minute per IP
        $key = 'form-submit:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many submissions. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Verify page exists and is published
        $page = CmsPage::where('id', $pageId)->published()->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }

        // Find the form block
        $blocks = $page->blocks ?? [];
        $formBlock = collect($blocks)->firstWhere('id', $blockId);

        if (!$formBlock || $formBlock['type'] !== 'contact_form') {
            return response()->json([
                'success' => false,
                'message' => 'Form not found',
            ], 404);
        }

        // Honeypot check (spam protection)
        if ($request->filled('website') || $request->filled('honeypot')) {
            // Silently accept but don't process (bot detection)
            return response()->json([
                'success' => true,
                'message' => 'Form submitted successfully',
            ]);
        }

        // Validate required fields from form configuration
        $fields = $formBlock['data']['fields'] ?? [];
        $rules = [];
        $messages = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            if (empty($fieldName)) {
                continue;
            }

            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Type-specific validation
            switch ($field['type'] ?? 'text') {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'phone':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:20';
                    break;
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
            }

            $rules[$fieldName] = $fieldRules;
            $messages["{$fieldName}.required"] = ($field['label'] ?? $fieldName) . ' is required.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sanitize and store form data
        $formData = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? '';
            if (!empty($fieldName) && $request->has($fieldName)) {
                $formData[$fieldName] = strip_tags($request->input($fieldName));
            }
        }

        // Create form submission
        $submission = FormSubmission::create([
            'page_id' => $pageId,
            'block_id' => $blockId,
            'form_name' => $formBlock['data']['form_name'] ?? 'Contact Form',
            'form_data' => $formData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_read' => false,
        ]);

        // Send email notification if configured
        $notifyEmail = $formBlock['settings']['notify_email'] ?? null;

        if ($notifyEmail && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::send('emails.form-submission', [
                    'formName' => $submission->form_name,
                    'formData' => $formData,
                    'pageName' => $page->title,
                ], function ($message) use ($notifyEmail, $submission) {
                    $message->to($notifyEmail)
                        ->subject('New Form Submission: ' . $submission->form_name);
                });
            } catch (\Exception $e) {
                // Log but don't fail the submission
                \Log::error('Form submission email failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $formBlock['settings']['success_message'] ?? 'Thank you for your submission!',
        ]);
    }

    /**
     * Track a page view for analytics.
     */
    public function trackView(Request $request, int $pageId): JsonResponse
    {
        // Rate limiting: 60 requests per minute per IP (to prevent abuse)
        $key = 'page-view:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['success' => true]); // Silent fail
        }

        RateLimiter::hit($key, 60);

        // Verify page exists
        $page = CmsPage::find($pageId);

        if (!$page) {
            return response()->json(['success' => false], 404);
        }

        // Check if this is a unique visitor (using session or fingerprint)
        $visitorKey = 'visitor:' . $pageId . ':' . $request->ip();
        $isUnique = !RateLimiter::tooManyAttempts($visitorKey, 1);

        if ($isUnique) {
            RateLimiter::hit($visitorKey, 86400); // 24 hours
        }

        // Record the view
        PageAnalytics::recordView($pageId, $isUnique);

        return response()->json(['success' => true]);
    }

    /**
     * Format page data for frontend consumption.
     */
    protected function formatPageForFrontend(CmsPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'type' => $page->type,
            'blocks' => $page->getBlocksWithData(), // Hydrates e-commerce data
            'page_settings' => $page->page_settings,
            'seo' => $page->getSeoMeta(),
        ];
    }
}
