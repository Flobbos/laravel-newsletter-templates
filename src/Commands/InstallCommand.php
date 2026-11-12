<?php

namespace Flobbos\NewsletterTemplates\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'newsletter-templates:install {--deployment}';

    protected $description = 'Publish newsletter template assets and prepare storage directories';

    public function handle(): int
    {
        if ($this->option('deployment')) {
            $this->ensureDirectories();
            $this->info('Template directories are ready.');

            return self::SUCCESS;
        }

        if ($this->confirm('Publish config, migrations, base layout, and model?', true)) {
            $this->publishAssets();
        }

        if ($this->confirm('Create template directories and the public storage link?', true)) {
            $this->ensureDirectories();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function publishAssets(): void
    {
        foreach ([
            'newsletter-templates-config' => 'Configuration published.',
            'newsletter-templates-migrations' => 'Migrations published.',
            'newsletter-templates-layout' => 'Base layout published.',
            'newsletter-templates-model' => 'NewsletterTemplate model published.',
        ] as $tag => $message) {
            Artisan::call('vendor:publish', [
                '--tag' => $tag,
                '--force' => true,
            ]);
            $this->info($message);
        }
    }

    private function ensureDirectories(): void
    {
        File::ensureDirectoryExists(resource_path((string) config('newsletter-templates.layout_path')));
        File::ensureDirectoryExists(storage_path((string) config('newsletter-templates.template_storage_path')));
        File::ensureDirectoryExists(storage_path('app/public/'.trim((string) config('newsletter-templates.asset_path'), '/')));

        $publicStorage = public_path('storage');

        if (! is_link($publicStorage) && ! File::exists($publicStorage)) {
            Artisan::call('storage:link');
            $this->info('Created public/storage symlink.');
        }
    }
}
