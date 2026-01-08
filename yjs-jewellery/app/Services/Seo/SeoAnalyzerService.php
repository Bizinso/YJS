<?php

namespace App\Services\Seo;

use App\Models\CmsPage;
use App\Models\SeoAudit;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    protected array $issues = [];
    protected array $suggestions = [];
    protected int $score = 100;

    protected const TITLE_MIN_LENGTH = 30;
    protected const TITLE_MAX_LENGTH = 60;
    protected const DESC_MIN_LENGTH = 120;
    protected const DESC_MAX_LENGTH = 160;

    /**
     * Analyze a page and return SEO audit results.
     */
    public function analyze(CmsPage $page): array
    {
        $this->reset();

        $seo = $page->seo ?? [];
        $blocks = $page->blocks ?? [];

        // Run all checks
        $this->checkMetaTitle($seo['meta_title'] ?? '');
        $this->checkMetaDescription($seo['meta_description'] ?? '');
        $this->checkSlug($page->slug);
        $this->checkHeadingStructure($blocks);
        $this->checkImages($blocks);
        $this->checkContentLength($blocks);
        $this->checkKeywords($seo);
        $this->checkOpenGraph($seo);
        $this->checkCanonical($seo);

        return [
            'score' => max(0, $this->score),
            'issues' => $this->issues,
            'suggestions' => $this->suggestions,
            'checks' => $this->getCheckResults($seo, $blocks),
        ];
    }

    /**
     * Analyze and save audit to database.
     */
    public function analyzeAndSave(CmsPage $page): SeoAudit
    {
        $results = $this->analyze($page);

        return SeoAudit::updateOrCreate(
            ['page_id' => $page->id],
            [
                'score' => $results['score'],
                'issues' => $results['issues'],
                'suggestions' => $results['suggestions'],
                'last_audit_at' => now(),
            ]
        );
    }

    /**
     * Reset the analyzer state.
     */
    protected function reset(): void
    {
        $this->issues = [];
        $this->suggestions = [];
        $this->score = 100;
    }

    /**
     * Check meta title.
     */
    protected function checkMetaTitle(string $title): void
    {
        if (empty($title)) {
            $this->addIssue('critical', 'Missing meta title', 'Add a unique, descriptive meta title for this page.');
            $this->score -= 20;
            return;
        }

        $length = strlen($title);

        if ($length < self::TITLE_MIN_LENGTH) {
            $this->addIssue('warning', 'Meta title is too short', "Your title is {$length} characters. Aim for " . self::TITLE_MIN_LENGTH . "-" . self::TITLE_MAX_LENGTH . " characters.");
            $this->score -= 10;
        } elseif ($length > self::TITLE_MAX_LENGTH) {
            $this->addIssue('warning', 'Meta title is too long', "Your title is {$length} characters. Search engines may truncate it. Keep it under " . self::TITLE_MAX_LENGTH . " characters.");
            $this->score -= 5;
        }
    }

    /**
     * Check meta description.
     */
    protected function checkMetaDescription(string $description): void
    {
        if (empty($description)) {
            $this->addIssue('critical', 'Missing meta description', 'Add a compelling meta description to improve click-through rates.');
            $this->score -= 15;
            return;
        }

        $length = strlen($description);

        if ($length < self::DESC_MIN_LENGTH) {
            $this->addIssue('warning', 'Meta description is too short', "Your description is {$length} characters. Aim for " . self::DESC_MIN_LENGTH . "-" . self::DESC_MAX_LENGTH . " characters.");
            $this->score -= 8;
        } elseif ($length > self::DESC_MAX_LENGTH) {
            $this->addIssue('warning', 'Meta description is too long', "Your description is {$length} characters. It may be truncated. Keep it under " . self::DESC_MAX_LENGTH . " characters.");
            $this->score -= 3;
        }
    }

    /**
     * Check URL slug.
     */
    protected function checkSlug(string $slug): void
    {
        if (empty($slug)) {
            $this->addIssue('critical', 'Missing URL slug', 'Add a URL-friendly slug for this page.');
            $this->score -= 10;
            return;
        }

        if (strlen($slug) > 75) {
            $this->addIssue('info', 'URL slug is long', 'Consider using a shorter, more memorable URL slug.');
            $this->score -= 2;
        }

        if (preg_match('/[A-Z]/', $slug)) {
            $this->addIssue('warning', 'URL contains uppercase letters', 'Use lowercase letters in URLs for consistency.');
            $this->score -= 3;
        }

        if (strpos($slug, '--') !== false || strpos($slug, '__') !== false) {
            $this->addIssue('info', 'URL has consecutive separators', 'Clean up the URL slug by removing consecutive dashes or underscores.');
            $this->score -= 2;
        }
    }

    /**
     * Check heading structure in blocks.
     */
    protected function checkHeadingStructure(array $blocks): void
    {
        $h1Count = 0;
        $headings = [];

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'hero' && !empty($block['data']['title'])) {
                $h1Count++;
                $headings[] = ['level' => 1, 'text' => $block['data']['title']];
            }

            if (($block['type'] ?? '') === 'rich_text' && !empty($block['data']['content'])) {
                $content = $block['data']['content'];
                preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);
                foreach ($matches[1] as $i => $level) {
                    if ($level == 1) $h1Count++;
                    $headings[] = ['level' => (int)$level, 'text' => strip_tags($matches[2][$i])];
                }
            }
        }

        if ($h1Count === 0) {
            $this->addIssue('warning', 'No H1 heading found', 'Add a main heading (H1) to help search engines understand your page content.');
            $this->score -= 10;
        } elseif ($h1Count > 1) {
            $this->addIssue('warning', 'Multiple H1 headings found', "Found {$h1Count} H1 headings. Use only one H1 per page for optimal SEO.");
            $this->score -= 5;
        }

        // Check heading hierarchy
        if (count($headings) > 1) {
            $lastLevel = 0;
            foreach ($headings as $heading) {
                if ($heading['level'] > $lastLevel + 1 && $lastLevel > 0) {
                    $this->addSuggestion('Heading hierarchy could be improved', 'Avoid skipping heading levels (e.g., going from H1 to H3). Use proper heading hierarchy.');
                    $this->score -= 2;
                    break;
                }
                $lastLevel = $heading['level'];
            }
        }
    }

    /**
     * Check images have alt text.
     */
    protected function checkImages(array $blocks): void
    {
        $totalImages = 0;
        $missingAlt = 0;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            // Hero image
            if ($type === 'hero' && !empty($block['data']['background_image'])) {
                $totalImages++;
                if (empty($block['data']['background_alt'])) {
                    $missingAlt++;
                }
            }

            // Gallery images
            if ($type === 'gallery' && !empty($block['data']['images'])) {
                foreach ($block['data']['images'] as $image) {
                    $totalImages++;
                    if (empty($image['alt'])) {
                        $missingAlt++;
                    }
                }
            }

            // Rich text images
            if ($type === 'rich_text' && !empty($block['data']['content'])) {
                preg_match_all('/<img[^>]+>/i', $block['data']['content'], $matches);
                foreach ($matches[0] as $imgTag) {
                    $totalImages++;
                    if (!preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $imgTag)) {
                        $missingAlt++;
                    }
                }
            }
        }

        if ($totalImages > 0 && $missingAlt > 0) {
            $this->addIssue('warning', "{$missingAlt} images missing alt text", 'Add descriptive alt text to all images for better accessibility and SEO.');
            $this->score -= min(10, $missingAlt * 2);
        }
    }

    /**
     * Check content length.
     */
    protected function checkContentLength(array $blocks): void
    {
        $wordCount = 0;

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'rich_text' && !empty($block['data']['content'])) {
                $text = strip_tags($block['data']['content']);
                $wordCount += str_word_count($text);
            }

            if (($block['type'] ?? '') === 'hero') {
                if (!empty($block['data']['title'])) $wordCount += str_word_count($block['data']['title']);
                if (!empty($block['data']['subtitle'])) $wordCount += str_word_count($block['data']['subtitle']);
            }

            if (($block['type'] ?? '') === 'faq' && !empty($block['data']['items'])) {
                foreach ($block['data']['items'] as $item) {
                    if (!empty($item['question'])) $wordCount += str_word_count($item['question']);
                    if (!empty($item['answer'])) $wordCount += str_word_count($item['answer']);
                }
            }
        }

        if ($wordCount < 100) {
            $this->addIssue('warning', 'Low content length', "Page has approximately {$wordCount} words. Consider adding more content for better SEO (aim for 300+ words).");
            $this->score -= 10;
        } elseif ($wordCount < 300) {
            $this->addSuggestion('Content could be expanded', "Page has approximately {$wordCount} words. Consider adding more valuable content.");
            $this->score -= 3;
        }
    }

    /**
     * Check keywords configuration.
     */
    protected function checkKeywords(array $seo): void
    {
        $focusKeyword = $seo['focus_keyword'] ?? '';

        if (empty($focusKeyword)) {
            $this->addSuggestion('No focus keyword set', 'Set a focus keyword to help optimize your content for search engines.');
            return;
        }

        $metaTitle = $seo['meta_title'] ?? '';
        $metaDesc = $seo['meta_description'] ?? '';

        // Check if keyword is in title
        if (!empty($metaTitle) && stripos($metaTitle, $focusKeyword) === false) {
            $this->addSuggestion('Focus keyword not in meta title', 'Include your focus keyword in the meta title for better rankings.');
            $this->score -= 3;
        }

        // Check if keyword is in description
        if (!empty($metaDesc) && stripos($metaDesc, $focusKeyword) === false) {
            $this->addSuggestion('Focus keyword not in meta description', 'Include your focus keyword in the meta description.');
            $this->score -= 2;
        }
    }

    /**
     * Check Open Graph tags.
     */
    protected function checkOpenGraph(array $seo): void
    {
        if (empty($seo['og_image'])) {
            $this->addSuggestion('No Open Graph image set', 'Add an OG image for better social media sharing appearance.');
            $this->score -= 3;
        }
    }

    /**
     * Check canonical URL.
     */
    protected function checkCanonical(array $seo): void
    {
        // This is informational only
        if (!empty($seo['canonical_url'])) {
            // Canonical is set, which is good for duplicate content
        }
    }

    /**
     * Add an issue.
     */
    protected function addIssue(string $severity, string $title, string $description): void
    {
        $this->issues[] = [
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * Add a suggestion.
     */
    protected function addSuggestion(string $title, string $description): void
    {
        $this->suggestions[] = [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * Get detailed check results for display.
     */
    protected function getCheckResults(array $seo, array $blocks): array
    {
        $metaTitle = $seo['meta_title'] ?? '';
        $metaDesc = $seo['meta_description'] ?? '';

        return [
            'title' => [
                'value' => $metaTitle,
                'length' => strlen($metaTitle),
                'min' => self::TITLE_MIN_LENGTH,
                'max' => self::TITLE_MAX_LENGTH,
                'status' => $this->getLengthStatus(strlen($metaTitle), self::TITLE_MIN_LENGTH, self::TITLE_MAX_LENGTH),
            ],
            'description' => [
                'value' => $metaDesc,
                'length' => strlen($metaDesc),
                'min' => self::DESC_MIN_LENGTH,
                'max' => self::DESC_MAX_LENGTH,
                'status' => $this->getLengthStatus(strlen($metaDesc), self::DESC_MIN_LENGTH, self::DESC_MAX_LENGTH),
            ],
            'focus_keyword' => $seo['focus_keyword'] ?? '',
            'og_image' => !empty($seo['og_image']),
            'canonical' => !empty($seo['canonical_url']),
            'robots_index' => ($seo['robots'] ?? 'index') !== 'noindex',
        ];
    }

    /**
     * Get status based on length.
     */
    protected function getLengthStatus(int $length, int $min, int $max): string
    {
        if ($length === 0) return 'missing';
        if ($length < $min) return 'short';
        if ($length > $max) return 'long';
        return 'good';
    }

    /**
     * Generate score color class.
     */
    public static function getScoreClass(int $score): string
    {
        if ($score >= 80) return 'text-green-600';
        if ($score >= 60) return 'text-yellow-600';
        if ($score >= 40) return 'text-orange-600';
        return 'text-red-600';
    }

    /**
     * Generate score label.
     */
    public static function getScoreLabel(int $score): string
    {
        if ($score >= 80) return 'Good';
        if ($score >= 60) return 'Needs Improvement';
        if ($score >= 40) return 'Poor';
        return 'Critical';
    }
}
