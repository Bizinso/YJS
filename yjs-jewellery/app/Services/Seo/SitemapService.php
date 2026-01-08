<?php

namespace App\Services\Seo;

use App\Models\CmsPage;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class SitemapService
{
    protected string $baseUrl;
    protected array $urls = [];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.url'), '/');
    }

    /**
     * Generate the sitemap XML.
     */
    public function generate(): string
    {
        $this->urls = [];

        // Add static pages
        $this->addStaticPages();

        // Add CMS pages
        $this->addCmsPages();

        // Add products
        $this->addProducts();

        // Add categories
        $this->addCategories();

        return $this->buildXml();
    }

    /**
     * Generate and save sitemap to storage.
     */
    public function generateAndSave(): string
    {
        $xml = $this->generate();
        Storage::disk('public')->put('sitemap.xml', $xml);

        return Storage::disk('public')->url('sitemap.xml');
    }

    /**
     * Add static pages to sitemap.
     */
    protected function addStaticPages(): void
    {
        $staticPages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/about', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['url' => '/products', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => '/collections', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ];

        foreach ($staticPages as $page) {
            $this->addUrl(
                $this->baseUrl . $page['url'],
                now()->format('Y-m-d'),
                $page['changefreq'],
                $page['priority']
            );
        }
    }

    /**
     * Add CMS pages to sitemap.
     */
    protected function addCmsPages(): void
    {
        $pages = CmsPage::where('status', 'published')
            ->whereNotNull('published_at')
            ->select('slug', 'updated_at', 'type')
            ->get();

        foreach ($pages as $page) {
            $priority = $page->type === 'homepage' ? '1.0' : ($page->type === 'landing' ? '0.8' : '0.6');
            $changefreq = $page->type === 'homepage' ? 'daily' : 'weekly';

            $url = $page->type === 'homepage'
                ? $this->baseUrl
                : $this->baseUrl . '/page/' . $page->slug;

            $this->addUrl(
                $url,
                $page->updated_at->format('Y-m-d'),
                $changefreq,
                $priority
            );
        }
    }

    /**
     * Add products to sitemap.
     */
    protected function addProducts(): void
    {
        $products = Product::where('status', 'active')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($products as $product) {
            $this->addUrl(
                $this->baseUrl . '/product/' . $product->slug,
                $product->updated_at->format('Y-m-d'),
                'weekly',
                '0.7'
            );
        }
    }

    /**
     * Add categories to sitemap.
     */
    protected function addCategories(): void
    {
        $categories = Category::where('status', 'A')
            ->select('slug', 'updated_at')
            ->get();

        foreach ($categories as $category) {
            $this->addUrl(
                $this->baseUrl . '/category/' . $category->slug,
                $category->updated_at->format('Y-m-d'),
                'weekly',
                '0.8'
            );
        }
    }

    /**
     * Add a URL to the sitemap.
     */
    protected function addUrl(string $loc, string $lastmod, string $changefreq, string $priority): void
    {
        $this->urls[] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /**
     * Build the XML string.
     */
    protected function buildXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($this->urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Get URL count.
     */
    public function getUrlCount(): int
    {
        return count($this->urls);
    }
}
