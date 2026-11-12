<?php

namespace Flobbos\NewsletterTemplates;

use Flobbos\NewsletterTemplates\Commands\ControllerCommand;
use Flobbos\NewsletterTemplates\Commands\InstallCommand;
use Flobbos\NewsletterTemplates\Commands\LayoutCommand;
use Flobbos\NewsletterTemplates\Commands\ViewCommand;
use Flobbos\NewsletterTemplates\Contracts\CompilerContract;
use Flobbos\NewsletterTemplates\Contracts\TemplateContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class NewsletterTemplatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/newsletter-templates.php',
            'newsletter-templates',
        );

        $this->app->bind(TemplateContract::class, Templates::class);
        $this->app->bind(CompilerContract::class, function ($app) {
            return match (config('newsletter-templates.compiler', 'remote')) {
                'remote' => $app->make(RemoteCompiler::class),
                'local' => $app->make(LocalCompiler::class),
                default => throw new InvalidArgumentException(
                    'Unknown newsletter template compiler ['.config('newsletter-templates.compiler').'].'
                ),
            };
        });

        $assetPath = trim((string) config('newsletter-templates.asset_path', 'newsletter-assets'), '/');

        config(['filesystems.disks.newsletter_templates' => [
            'driver' => 'local',
            'root' => storage_path('app/public/'.$assetPath),
            'url' => rtrim((string) config('app.url'), '/').'/storage/'.$assetPath,
            'visibility' => 'public',
        ]]);

        foreach ([
            storage_path((string) config('newsletter-templates.template_storage_path', 'app/newsletter-templates/templates')),
            resource_path((string) config('newsletter-templates.template_path')),
        ] as $templatePath) {
            if (File::isDirectory($templatePath)) {
                $this->app['view']->addLocation($templatePath);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                LayoutCommand::class,
                ViewCommand::class,
                ControllerCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__).'/config/newsletter-templates.php' => config_path('newsletter-templates.php'),
        ], 'newsletter-templates-config');

        $this->publishes([
            dirname(__DIR__).'/database/migrations/' => database_path('migrations'),
        ], 'newsletter-templates-migrations');

        $this->publishes([
            dirname(__DIR__).'/resources/defaults/base' => resource_path(config('newsletter-templates.layout_path').'/base'),
        ], 'newsletter-templates-layout');

        $this->publishes([
            dirname(__DIR__).'/resources/stubs/model.stub' => app_path('Models/NewsletterTemplate.php'),
        ], 'newsletter-templates-model');

        $framework = config('newsletter-templates.css_framework', 'tailwind');

        if (! in_array($framework, ['tailwind', 'bootstrap4'], true)) {
            $framework = 'tailwind';
        }

        $this->loadViewsFrom(dirname(__DIR__).'/resources/views/'.$framework, 'newsletter-templates');
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'newsletter-templates');
    }
}
