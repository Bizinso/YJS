<?php

namespace Database\Seeders;

use App\Models\Cms\Widget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds all CMS Page Builder widgets.
     */
    public function run(): void
    {
        $widgets = $this->getWidgets();

        foreach ($widgets as $index => $widget) {
            $supportsDataBinding = $widget['supports_data_binding'] ?? false;

            Widget::updateOrCreate(
                ['identifier' => $widget['identifier']],
                [
                    'name' => $widget['name'],
                    'category' => $widget['category'],
                    'description' => $widget['description'] ?? null,
                    'icon' => $widget['icon'],
                    'config_schema' => [],
                    'default_config' => [],
                    'supports_data_binding' => $supportsDataBinding,
                    'data_provider_types' => $supportsDataBinding ? ['products', 'categories', 'offers'] : null,
                    'admin_component' => ucfirst(Str::camel($widget['identifier'])) . 'BlockPreview',
                    'frontend_component' => ucfirst(Str::camel($widget['identifier'])) . 'Block',
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        $this->command->info('Seeded ' . count($widgets) . ' widgets.');
    }

    /**
     * Get all widget definitions.
     */
    protected function getWidgets(): array
    {
        return [
            // ============ LAYOUT ============
            ['identifier' => 'hero', 'name' => 'Hero Banner', 'category' => 'layout', 'icon' => 'bi-image', 'description' => 'Full-width hero section with background image'],
            ['identifier' => 'banner', 'name' => 'Banner', 'category' => 'layout', 'icon' => 'bi-file-richtext', 'description' => 'Full-width banner with text and button'],
            ['identifier' => 'two-column', 'name' => 'Two Column', 'category' => 'layout', 'icon' => 'bi-layout-split', 'description' => 'Two column layout section'],
            ['identifier' => 'three-column', 'name' => 'Three Column', 'category' => 'layout', 'icon' => 'bi-grid-3x3', 'description' => 'Three column layout section'],
            ['identifier' => 'container', 'name' => 'Container', 'category' => 'layout', 'icon' => 'bi-bounding-box', 'description' => 'Wrapper container with padding/margin'],
            ['identifier' => 'spacer', 'name' => 'Spacer/Divider', 'category' => 'layout', 'icon' => 'bi-distribute-vertical', 'description' => 'Vertical spacing or horizontal divider'],

            // ============ CONTENT ============
            ['identifier' => 'rich-text', 'name' => 'Rich Text', 'category' => 'content', 'icon' => 'bi-text-paragraph', 'description' => 'Rich text editor with formatting'],
            ['identifier' => 'heading', 'name' => 'Heading', 'category' => 'content', 'icon' => 'bi-type-h1', 'description' => 'Section heading with various styles'],
            ['identifier' => 'text', 'name' => 'Text Block', 'category' => 'content', 'icon' => 'bi-text-left', 'description' => 'Simple text paragraph'],
            ['identifier' => 'image-text', 'name' => 'Image with Text', 'category' => 'content', 'icon' => 'bi-card-image', 'description' => 'Image alongside text content'],
            ['identifier' => 'features', 'name' => 'Features Grid', 'category' => 'content', 'icon' => 'bi-grid-1x2', 'description' => 'Feature cards with icons'],
            ['identifier' => 'steps', 'name' => 'Steps/Process', 'category' => 'content', 'icon' => 'bi-list-ol', 'description' => 'Step-by-step process display'],
            ['identifier' => 'faq', 'name' => 'FAQ Accordion', 'category' => 'content', 'icon' => 'bi-question-circle', 'description' => 'Frequently asked questions'],
            ['identifier' => 'accordion', 'name' => 'Accordion', 'category' => 'content', 'icon' => 'bi-list-nested', 'description' => 'Collapsible content panels'],
            ['identifier' => 'tabs', 'name' => 'Tabs', 'category' => 'content', 'icon' => 'bi-folder', 'description' => 'Tabbed content sections'],
            ['identifier' => 'blockquote', 'name' => 'Quote', 'category' => 'content', 'icon' => 'bi-quote', 'description' => 'Stylized quotation block'],

            // ============ MEDIA ============
            ['identifier' => 'image', 'name' => 'Single Image', 'category' => 'media', 'icon' => 'bi-image', 'description' => 'Single image with caption'],
            ['identifier' => 'gallery', 'name' => 'Image Gallery', 'category' => 'media', 'icon' => 'bi-images', 'description' => 'Grid or masonry image gallery'],
            ['identifier' => 'slider', 'name' => 'Image Slider', 'category' => 'media', 'icon' => 'bi-carousel', 'description' => 'Carousel/slider with images'],
            ['identifier' => 'video', 'name' => 'Video Embed', 'category' => 'media', 'icon' => 'bi-play-circle', 'description' => 'YouTube/Vimeo video embed'],
            ['identifier' => 'before-after', 'name' => 'Before/After', 'category' => 'media', 'icon' => 'bi-arrows-move', 'description' => 'Image comparison slider'],
            ['identifier' => 'instagram-feed', 'name' => 'Instagram Feed', 'category' => 'media', 'icon' => 'bi-instagram', 'description' => 'Embed Instagram posts', 'supports_data_binding' => true],

            // ============ E-COMMERCE ============
            ['identifier' => 'product-showcase', 'name' => 'Product Showcase', 'category' => 'ecommerce', 'icon' => 'bi-bag', 'description' => 'Display products in grid/carousel', 'supports_data_binding' => true],
            ['identifier' => 'featured-products', 'name' => 'Featured Products', 'category' => 'ecommerce', 'icon' => 'bi-star', 'description' => 'Featured products carousel', 'supports_data_binding' => true],
            ['identifier' => 'bestsellers', 'name' => 'Bestsellers', 'category' => 'ecommerce', 'icon' => 'bi-trophy', 'description' => 'Top selling products', 'supports_data_binding' => true],
            ['identifier' => 'new-arrivals', 'name' => 'New Arrivals', 'category' => 'ecommerce', 'icon' => 'bi-bag-plus', 'description' => 'Latest products', 'supports_data_binding' => true],
            ['identifier' => 'category-grid', 'name' => 'Category Grid', 'category' => 'ecommerce', 'icon' => 'bi-grid', 'description' => 'Product categories display', 'supports_data_binding' => true],
            ['identifier' => 'collection-showcase', 'name' => 'Collection Showcase', 'category' => 'ecommerce', 'icon' => 'bi-collection', 'description' => 'Jewellery collection display', 'supports_data_binding' => true],
            ['identifier' => 'offer-banner', 'name' => 'Offer Banner', 'category' => 'ecommerce', 'icon' => 'bi-tag', 'description' => 'Special offer announcement', 'supports_data_binding' => true],
            ['identifier' => 'promo-banner', 'name' => 'Promo Banner', 'category' => 'ecommerce', 'icon' => 'bi-percent', 'description' => 'Promotional offer banner', 'supports_data_binding' => true],
            ['identifier' => 'countdown-timer', 'name' => 'Countdown Timer', 'category' => 'ecommerce', 'icon' => 'bi-clock-history', 'description' => 'Sale countdown timer'],

            // ============ CONVERSION ============
            ['identifier' => 'cta', 'name' => 'Call to Action', 'category' => 'conversion', 'icon' => 'bi-cursor', 'description' => 'CTA section with button'],
            ['identifier' => 'button', 'name' => 'Button', 'category' => 'conversion', 'icon' => 'bi-hand-index', 'description' => 'Call-to-action button'],
            ['identifier' => 'newsletter', 'name' => 'Newsletter', 'category' => 'conversion', 'icon' => 'bi-envelope-paper', 'description' => 'Email newsletter signup'],
            ['identifier' => 'popup', 'name' => 'Popup/Modal', 'category' => 'conversion', 'icon' => 'bi-window-stack', 'description' => 'Popup modal content'],

            // ============ SOCIAL PROOF ============
            ['identifier' => 'testimonials', 'name' => 'Testimonials', 'category' => 'social', 'icon' => 'bi-chat-quote', 'description' => 'Customer testimonials carousel'],
            ['identifier' => 'reviews', 'name' => 'Customer Reviews', 'category' => 'social', 'icon' => 'bi-star-half', 'description' => 'Customer product reviews', 'supports_data_binding' => true],
            ['identifier' => 'team', 'name' => 'Team Members', 'category' => 'social', 'icon' => 'bi-people', 'description' => 'Team/staff profiles'],
            ['identifier' => 'clients', 'name' => 'Client Logos', 'category' => 'social', 'icon' => 'bi-building', 'description' => 'Client/partner logos grid'],
            ['identifier' => 'trust-badges', 'name' => 'Trust Badges', 'category' => 'social', 'icon' => 'bi-award', 'description' => 'Certifications and trust badges'],
            ['identifier' => 'social-links', 'name' => 'Social Links', 'category' => 'social', 'icon' => 'bi-share', 'description' => 'Social media links'],

            // ============ FORMS ============
            ['identifier' => 'contact-form', 'name' => 'Contact Form', 'category' => 'forms', 'icon' => 'bi-envelope', 'description' => 'Contact form with fields'],
            ['identifier' => 'inquiry-form', 'name' => 'Inquiry Form', 'category' => 'forms', 'icon' => 'bi-chat-left-text', 'description' => 'Product/service inquiry form'],
            ['identifier' => 'appointment', 'name' => 'Book Appointment', 'category' => 'forms', 'icon' => 'bi-calendar-check', 'description' => 'Schedule appointment form'],
            ['identifier' => 'feedback', 'name' => 'Feedback Form', 'category' => 'forms', 'icon' => 'bi-chat-square-dots', 'description' => 'Customer feedback collection'],

            // ============ ADVANCED ============
            ['identifier' => 'custom-html', 'name' => 'Custom HTML', 'category' => 'advanced', 'icon' => 'bi-code-slash', 'description' => 'Raw HTML/CSS/JS code'],
            ['identifier' => 'embed', 'name' => 'Embed Code', 'category' => 'advanced', 'icon' => 'bi-code-square', 'description' => 'Embed external content (iframe)'],
            ['identifier' => 'map', 'name' => 'Google Map', 'category' => 'advanced', 'icon' => 'bi-geo-alt', 'description' => 'Embedded Google Maps'],
            ['identifier' => 'code', 'name' => 'Code Block', 'category' => 'advanced', 'icon' => 'bi-braces', 'description' => 'Display formatted code'],
            ['identifier' => 'countdown', 'name' => 'Countdown', 'category' => 'advanced', 'icon' => 'bi-hourglass-split', 'description' => 'Date countdown timer'],
        ];
    }
}
