<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Cms\DataBindingService;
use App\Services\Cms\VersionService;
use App\Services\Cms\PageService;
use App\Services\Cms\GlobalSectionService;
use App\Services\Cms\RenderService;
use App\Services\Cms\CmsCacheService;
use App\Services\Cms\Providers\ProductProvider;
use App\Services\Cms\Providers\CategoryProvider;
use App\Services\Cms\Providers\OfferProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register CMS services.
     */
    public function register(): void
    {
        // Register DataBindingService as singleton with providers
        $this->app->singleton(DataBindingService::class, function ($app) {
            $service = new DataBindingService();

            // Register all data providers
            $service->registerProvider(new ProductProvider());
            $service->registerProvider(new CategoryProvider());
            $service->registerProvider(new OfferProvider());

            return $service;
        });

        // Register VersionService as singleton
        $this->app->singleton(VersionService::class, function ($app) {
            return new VersionService();
        });

        // Register RenderService as singleton
        $this->app->singleton(RenderService::class, function ($app) {
            return new RenderService(
                $app->make(DataBindingService::class)
            );
        });

        // Register PageService with dependencies
        $this->app->singleton(PageService::class, function ($app) {
            return new PageService(
                $app->make(VersionService::class),
                $app->make(DataBindingService::class)
            );
        });

        // Register GlobalSectionService as singleton
        $this->app->singleton(GlobalSectionService::class, function ($app) {
            return new GlobalSectionService();
        });

        // Register CmsCacheService as singleton
        $this->app->singleton(CmsCacheService::class, function ($app) {
            return new CmsCacheService(
                $app->make(RenderService::class),
                $app->make(DataBindingService::class)
            );
        });
    }

    /**
     * Bootstrap CMS services.
     */
    public function boot(): void
    {
        // Register scheduled task for publishing scheduled versions
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

            $schedule->call(function () {
                $this->publishScheduledVersions();
            })->everyMinute()->name('cms:publish-scheduled')->withoutOverlapping();
        });
    }

    /**
     * Publish any scheduled versions that are due.
     */
    protected function publishScheduledVersions(): void
    {
        $versionService = app(VersionService::class);

        $pages = \App\Models\Cms\Page::query()
            ->whereNotNull('scheduled_version_id')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($pages as $page) {
            try {
                $scheduledVersion = $page->scheduledVersion;
                if ($scheduledVersion) {
                    $versionService->publishVersion($scheduledVersion);

                    // Clear scheduling
                    $page->update([
                        'scheduled_version_id' => null,
                        'scheduled_at' => null,
                    ]);

                    \Log::info("CMS: Published scheduled version for page {$page->slug}");
                }
            } catch (\Exception $e) {
                \Log::error("CMS: Failed to publish scheduled version for page {$page->slug}: " . $e->getMessage());
            }
        }
    }
}
