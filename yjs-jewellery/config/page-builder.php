<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Builder Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the dynamic page builder,
    | including block types, their settings, and default values.
    |
    */

    // Available page types
    'page_types' => [
        'homepage' => 'Homepage',
        'static' => 'Static Page',
        'landing' => 'Landing Page',
        'category' => 'Category Page',
    ],

    // Block categories for organization
    'block_categories' => [
        'layout' => [
            'label' => 'Layout',
            'icon' => 'bi-grid',
            'order' => 1,
        ],
        'content' => [
            'label' => 'Content',
            'icon' => 'bi-file-text',
            'order' => 2,
        ],
        'media' => [
            'label' => 'Media',
            'icon' => 'bi-image',
            'order' => 3,
        ],
        'ecommerce' => [
            'label' => 'E-commerce',
            'icon' => 'bi-cart',
            'order' => 4,
        ],
        'conversion' => [
            'label' => 'Conversion',
            'icon' => 'bi-bullseye',
            'order' => 5,
        ],
        'social' => [
            'label' => 'Social Proof',
            'icon' => 'bi-people',
            'order' => 6,
        ],
        'forms' => [
            'label' => 'Forms',
            'icon' => 'bi-envelope',
            'order' => 7,
        ],
        'advanced' => [
            'label' => 'Advanced',
            'icon' => 'bi-code-slash',
            'order' => 8,
        ],
    ],

    // Available block types with their configurations
    'blocks' => [

        // ============ LAYOUT BLOCKS ============

        'hero' => [
            'name' => 'Hero Banner',
            'description' => 'Full-width hero section with image, text, and CTA',
            'icon' => 'bi-aspect-ratio',
            'category' => 'layout',
            'data' => [
                'title' => '',
                'subtitle' => '',
                'background_image' => '',
                'background_video' => '',
                'overlay_color' => 'rgba(0,0,0,0.4)',
                'text_color' => '#ffffff',
                'cta_text' => '',
                'cta_link' => '',
                'cta_style' => 'primary', // primary, secondary, outline
                'alignment' => 'center', // left, center, right
                'height' => '500px',
            ],
            'settings' => [
                'full_width' => true,
                'parallax' => false,
                'autoplay_video' => true,
            ],
        ],

        'spacer' => [
            'name' => 'Spacer / Divider',
            'description' => 'Add vertical space or a divider line',
            'icon' => 'bi-distribute-vertical',
            'category' => 'layout',
            'data' => [
                'height' => 40,
                'show_divider' => false,
                'divider_style' => 'solid', // solid, dashed, dotted
                'divider_color' => '#e0e0e0',
                'divider_width' => '100%',
            ],
            'settings' => [],
        ],

        // ============ CONTENT BLOCKS ============

        'rich_text' => [
            'name' => 'Rich Text',
            'description' => 'WYSIWYG content block for formatted text',
            'icon' => 'bi-text-paragraph',
            'category' => 'content',
            'data' => [
                'content' => '',
            ],
            'settings' => [
                'max_width' => '800px',
                'text_align' => 'left',
            ],
        ],

        'faq' => [
            'name' => 'FAQ Accordion',
            'description' => 'Expandable FAQ section',
            'icon' => 'bi-question-circle',
            'category' => 'content',
            'data' => [
                'title' => 'Frequently Asked Questions',
                'items' => [
                    [
                        'question' => 'Sample question?',
                        'answer' => 'Sample answer.',
                    ],
                ],
            ],
            'settings' => [
                'allow_multiple_open' => false,
                'first_open' => true,
            ],
        ],

        // ============ MEDIA BLOCKS ============

        'gallery' => [
            'name' => 'Image Gallery',
            'description' => 'Grid or carousel of images',
            'icon' => 'bi-images',
            'category' => 'media',
            'data' => [
                'title' => '',
                'images' => [], // Array of { src, alt, caption }
            ],
            'settings' => [
                'layout' => 'grid', // grid, carousel, masonry
                'columns' => 3,
                'gap' => 16,
                'lightbox' => true,
                'show_captions' => true,
            ],
        ],

        'video' => [
            'name' => 'Video Embed',
            'description' => 'YouTube, Vimeo, or self-hosted video',
            'icon' => 'bi-play-circle',
            'category' => 'media',
            'data' => [
                'title' => '',
                'video_url' => '',
                'video_type' => 'youtube', // youtube, vimeo, self-hosted
                'thumbnail' => '',
            ],
            'settings' => [
                'autoplay' => false,
                'loop' => false,
                'muted' => false,
                'controls' => true,
                'aspect_ratio' => '16:9',
            ],
        ],

        // ============ E-COMMERCE BLOCKS ============

        'product_showcase' => [
            'name' => 'Product Showcase',
            'description' => 'Display products from your catalog',
            'icon' => 'bi-gem',
            'category' => 'ecommerce',
            'data' => [
                'title' => 'Featured Products',
                'subtitle' => '',
                'source' => 'featured', // featured, category, manual, bestsellers, new_arrivals
                'category_id' => null,
                'product_ids' => [],
                'limit' => 8,
            ],
            'settings' => [
                'layout' => 'grid', // grid, carousel
                'columns' => 4,
                'show_price' => true,
                'show_rating' => true,
                'show_add_to_cart' => true,
                'show_wishlist' => true,
            ],
        ],

        'category_grid' => [
            'name' => 'Category Grid',
            'description' => 'Display product categories',
            'icon' => 'bi-grid-3x3',
            'category' => 'ecommerce',
            'data' => [
                'title' => 'Shop by Category',
                'subtitle' => '',
                'source' => 'all', // all, parent_only, selected
                'category_ids' => [],
                'limit' => 6,
            ],
            'settings' => [
                'layout' => 'grid', // grid, carousel
                'columns' => 3,
                'show_product_count' => true,
                'image_style' => 'cover', // cover, contain
            ],
        ],

        'offer_banner' => [
            'name' => 'Offer Banner',
            'description' => 'Promotional banner with countdown',
            'icon' => 'bi-tag',
            'category' => 'ecommerce',
            'data' => [
                'title' => '',
                'subtitle' => '',
                'offer_id' => null, // Link to specific offer
                'background_image' => '',
                'background_color' => '#f8f9fa',
                'cta_text' => 'Shop Now',
                'cta_link' => '',
            ],
            'settings' => [
                'show_countdown' => true,
                'show_discount' => true,
                'text_color' => '#000000',
            ],
        ],

        // ============ CONVERSION BLOCKS ============

        'cta' => [
            'name' => 'Call to Action',
            'description' => 'Prominent CTA section',
            'icon' => 'bi-megaphone',
            'category' => 'conversion',
            'data' => [
                'title' => '',
                'description' => '',
                'primary_button_text' => '',
                'primary_button_link' => '',
                'secondary_button_text' => '',
                'secondary_button_link' => '',
                'background_color' => '#1a1a2e',
                'text_color' => '#ffffff',
            ],
            'settings' => [
                'layout' => 'horizontal', // horizontal, vertical, centered
                'full_width' => true,
            ],
        ],

        // ============ SOCIAL PROOF BLOCKS ============

        'testimonials' => [
            'name' => 'Testimonials',
            'description' => 'Customer testimonials and reviews',
            'icon' => 'bi-chat-quote',
            'category' => 'social',
            'data' => [
                'title' => 'What Our Customers Say',
                'subtitle' => '',
                'source' => 'manual', // manual, reviews (from Review model)
                'testimonials' => [
                    [
                        'name' => 'Customer Name',
                        'title' => 'Verified Buyer',
                        'avatar' => '',
                        'content' => 'Great experience!',
                        'rating' => 5,
                    ],
                ],
                'product_id' => null, // For pulling reviews from a product
                'limit' => 6,
            ],
            'settings' => [
                'layout' => 'carousel', // grid, carousel
                'show_rating' => true,
                'show_avatar' => true,
                'autoplay' => true,
            ],
        ],

        // ============ FORM BLOCKS ============

        'contact_form' => [
            'name' => 'Contact Form',
            'description' => 'Customizable contact form',
            'icon' => 'bi-envelope',
            'category' => 'forms',
            'data' => [
                'form_name' => 'Contact Form',
                'title' => 'Get in Touch',
                'subtitle' => '',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Your Name',
                        'placeholder' => 'Enter your name',
                        'required' => true,
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'Enter your email',
                        'required' => true,
                    ],
                    [
                        'type' => 'phone',
                        'name' => 'phone',
                        'label' => 'Phone Number',
                        'placeholder' => 'Enter your phone',
                        'required' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'message',
                        'label' => 'Message',
                        'placeholder' => 'Enter your message',
                        'required' => true,
                    ],
                ],
                'submit_text' => 'Send Message',
            ],
            'settings' => [
                'notify_email' => '', // Email to receive notifications
                'success_message' => 'Thank you for your message!',
                'redirect_url' => '',
                'show_labels' => true,
                'honeypot' => true, // Spam protection
            ],
        ],

        // ============ ADVANCED BLOCKS ============

        'custom_html' => [
            'name' => 'Custom HTML',
            'description' => 'Raw HTML/CSS/JS code',
            'icon' => 'bi-code-slash',
            'category' => 'advanced',
            'data' => [
                'html' => '',
                'css' => '',
                'js' => '',
            ],
            'settings' => [
                'sandbox' => true, // Sandbox mode for security
            ],
        ],

    ],

    // Default style options for all blocks
    'default_styles' => [
        'backgroundColor' => '#ffffff',
        'textColor' => '#333333',
        'padding' => [
            'top' => 40,
            'right' => 20,
            'bottom' => 40,
            'left' => 20,
        ],
        'margin' => [
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'left' => 0,
        ],
        'customCSS' => '',
    ],

    // Responsive breakpoints
    'breakpoints' => [
        'mobile' => 576,
        'tablet' => 768,
        'desktop' => 1200,
    ],

    // Animation options
    'animations' => [
        'none' => 'No Animation',
        'fadeIn' => 'Fade In',
        'fadeInUp' => 'Fade In Up',
        'fadeInDown' => 'Fade In Down',
        'fadeInLeft' => 'Fade In Left',
        'fadeInRight' => 'Fade In Right',
        'zoomIn' => 'Zoom In',
        'slideInUp' => 'Slide In Up',
        'slideInDown' => 'Slide In Down',
        'bounce' => 'Bounce',
    ],

    // SEO defaults
    'seo' => [
        'title_suffix' => ' | YJS Jewellery',
        'default_og_image' => '/images/og-default.jpg',
        'default_robots' => 'index, follow',
    ],

    // Auto-save interval (in seconds)
    'auto_save_interval' => 30,

    // Maximum versions to keep per page
    'max_versions' => 50,

    // Asset upload settings
    'assets' => [
        'max_file_size' => 5120, // KB (5MB)
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'],
        'storage_disk' => 'public',
        'storage_path' => 'cms/assets',
    ],

    // Global header block types
    'header_blocks' => [
        'logo' => [
            'name' => 'Logo',
            'icon' => 'bi-image',
            'data' => [
                'image' => '',
                'alt' => 'YJS Jewellery',
                'link' => '/',
            ],
        ],
        'navigation' => [
            'name' => 'Navigation Menu',
            'icon' => 'bi-list',
            'data' => [
                'items' => [],
                'style' => 'horizontal', // horizontal, dropdown
            ],
        ],
        'search' => [
            'name' => 'Search Bar',
            'icon' => 'bi-search',
            'data' => [
                'placeholder' => 'Search products...',
            ],
        ],
        'cart_icon' => [
            'name' => 'Cart Icon',
            'icon' => 'bi-cart',
            'data' => [
                'show_count' => true,
            ],
        ],
        'user_menu' => [
            'name' => 'User Menu',
            'icon' => 'bi-person',
            'data' => [
                'show_name' => true,
            ],
        ],
        'wishlist_icon' => [
            'name' => 'Wishlist Icon',
            'icon' => 'bi-heart',
            'data' => [
                'show_count' => true,
            ],
        ],
    ],

    // Global footer block types
    'footer_blocks' => [
        'footer_logo' => [
            'name' => 'Footer Logo',
            'icon' => 'bi-image',
            'data' => [
                'image' => '',
                'alt' => 'YJS Jewellery',
                'description' => '',
            ],
        ],
        'footer_links' => [
            'name' => 'Link Column',
            'icon' => 'bi-list',
            'data' => [
                'title' => '',
                'links' => [],
            ],
        ],
        'social_icons' => [
            'name' => 'Social Icons',
            'icon' => 'bi-share',
            'data' => [
                'title' => 'Follow Us',
                'icons' => [], // { platform, url, icon }
            ],
        ],
        'newsletter' => [
            'name' => 'Newsletter Signup',
            'icon' => 'bi-envelope',
            'data' => [
                'title' => 'Subscribe to Newsletter',
                'description' => '',
                'placeholder' => 'Enter your email',
                'button_text' => 'Subscribe',
            ],
        ],
        'contact_info' => [
            'name' => 'Contact Information',
            'icon' => 'bi-geo-alt',
            'data' => [
                'title' => 'Contact Us',
                'address' => '',
                'phone' => '',
                'email' => '',
                'hours' => '',
            ],
        ],
        'copyright' => [
            'name' => 'Copyright',
            'icon' => 'bi-c-circle',
            'data' => [
                'text' => '© {year} YJS Jewellery. All rights reserved.',
                'links' => [], // Terms, Privacy, etc.
            ],
        ],
    ],

];
