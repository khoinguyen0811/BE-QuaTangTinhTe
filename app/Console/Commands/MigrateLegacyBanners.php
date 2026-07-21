<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\PageLayout;
use App\Services\HomeLayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateLegacyBanners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'home-layout:migrate-legacy-banners {--dry-run : Simulate the migration without saving changes} {--force : Force override customized Hero section}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate active legacy banners from banners table into Home Builder layout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy banners migration...');

        // 1. Fetch active banners from position: home_main
        $banners = Banner::query()
            ->where('is_active', true)
            ->where('position', 'home_main')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($banners->isEmpty()) {
            $this->warn('No active legacy banners found in banners table.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d active legacy banners to migrate.', $banners->count()));

        // 2. Map legacy banners to slides schema
        $newSlides = [];
        foreach ($banners as $banner) {
            $newSlides[] = [
                'desktop_image' => $banner->image_path,
                'mobile_image' => $banner->image_path,
                'alt_text' => $banner->title ?: 'Banner',
                'link_url' => $banner->link_url ?: '/collection',
                'eyebrow' => '',
                'title' => $banner->title ?: '',
                'description' => '',
                'primary_label' => 'Xem sản phẩm',
                'primary_href' => $banner->link_url ?: '/collection',
                'secondary_label' => '',
                'secondary_href' => '',
            ];
        }

        // 3. Retrieve Home Page Layout
        $layout = PageLayout::query()->where('page_key', HomeLayoutService::PAGE_KEY)->first();
        
        $currentContent = [];
        if ($layout) {
            $currentContent = $layout->draft_content;
        } else {
            // If no layout exists, load the default layout from HomeLayoutService
            $homeService = app(HomeLayoutService::class);
            $currentContent = $homeService->defaultLayout();
        }

        // 4. Find the Hero section
        $sections = $currentContent['sections'] ?? [];
        $heroIndex = -1;
        foreach ($sections as $index => $section) {
            if (($section['id'] ?? '') === 'hero') {
                $heroIndex = $index;
                break;
            }
        }

        if ($heroIndex === -1) {
            $this->error('Hero section not found in homepage layout.');
            return Command::FAILURE;
        }

        $heroSection = $sections[$heroIndex];
        $heroProps = $heroSection['props'] ?? [];

        // Check if Hero has custom slides (more than default 1 slide, or modified slide)
        $hasCustomization = false;
        if (isset($heroProps['slides'])) {
            $slides = $heroProps['slides'];
            // If slides count > 1, or it's modified from default
            if (count($slides) > 1 || (isset($slides[0]['desktop_image']) && $slides[0]['desktop_image'] !== 'public/images/slider_1.png')) {
                $hasCustomization = true;
            }
        }

        if ($hasCustomization && !$this->option('force')) {
            $this->warn('Hero section has already been customized in Home Builder.');
            $this->warn('Use --force option to overwrite current customizations.');
            return Command::FAILURE;
        }

        // 5. Apply the new slides
        $heroProps['slides'] = $newSlides;
        
        // Also enable slides configuration defaults
        $heroProps['autoplay'] = true;
        $heroProps['autoplay_interval'] = 5000;
        $heroProps['transition_duration'] = 600;
        $heroProps['pause_on_hover'] = true;
        $heroProps['show_arrows'] = true;
        $heroProps['show_dots'] = true;
        $heroProps['overlay_enabled'] = false;

        $sections[$heroIndex]['props'] = $heroProps;
        $newContent = $currentContent;
        $newContent['sections'] = $sections;

        if ($this->option('dry-run')) {
            $this->info('[Dry-run] Simulated migration successfully. Target slides configuration:');
            $this->line(json_encode($newSlides, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return Command::SUCCESS;
        }

        // 6. Backup existing layout to storage/logs
        if ($layout) {
            $backupFilename = sprintf('home_layout_backup_%d.json', time());
            Storage::disk('local')->put('logs/' . $backupFilename, json_encode($currentContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info(sprintf('Backup created: storage/app/logs/%s', $backupFilename));
        }

        // 7. Save to database
        if ($layout) {
            $layout->update([
                'draft_content' => $newContent,
                'published_content' => $newContent,
                'published_revision' => $layout->published_revision + 1,
                'published_at' => now(),
            ]);
        } else {
            PageLayout::query()->create([
                'page_key' => HomeLayoutService::PAGE_KEY,
                'schema_version' => HomeLayoutService::SCHEMA_VERSION,
                'draft_content' => $newContent,
                'published_content' => $newContent,
                'published_revision' => 1,
                'published_at' => now(),
            ]);
        }

        $this->info('Migration completed successfully!');
        return Command::SUCCESS;
    }
}
