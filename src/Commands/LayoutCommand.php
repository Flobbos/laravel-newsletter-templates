<?php

namespace Flobbos\NewsletterTemplates\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class LayoutCommand extends GeneratorCommand
{
    protected $signature = 'newsletter-templates:layout {name} {--fresh}';

    protected $description = 'Generate an MJML layout from the base layout';

    public function handle()
    {
        if (! config('newsletter-templates.multi_layout')) {
            $this->error('multi_layout is off. A new layout will not be used.');

            return false;
        }

        if (! $this->baseExists()) {
            if (! $this->confirm('Base layout is missing. Publish it now?', true)) {
                $this->error('Layouts can only be generated when the base layout exists.');

                return false;
            }

            $this->publishBaseLayout();
        }

        $path = $this->layoutDirectory($this->layoutName());

        if ($this->files->isDirectory($path)) {
            $this->error('Layout already exists.');

            return false;
        }

        foreach ($this->stubMap() as $name => $stub) {
            $this->makeDirectory($path.'/'.$name);
            $this->files->put($path.'/'.$name, $this->files->get($stub));
        }

        $this->files->makeDirectory($path.'/images');
        $this->info('Layout created.');

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function stubMap(): array
    {
        $source = $this->option('fresh')
            ? dirname(__DIR__, 2).'/resources/defaults/base'
            : $this->layoutDirectory('base');

        return [
            $this->layoutName().'.blade.php' => $source.'/base.blade.php',
            $this->layoutName().'.scss' => $source.'/base.scss',
        ];
    }

    protected function getStub(): string
    {
        return dirname(__DIR__, 2).'/resources/defaults/base/base.blade.php';
    }

    private function layoutName(): string
    {
        return strtolower(Str::kebab(trim((string) $this->argument('name'))));
    }

    private function layoutDirectory(string $name): string
    {
        return resource_path((string) config('newsletter-templates.layout_path').'/'.$name);
    }

    private function baseExists(): bool
    {
        return $this->files->isDirectory($this->layoutDirectory('base'));
    }

    private function publishBaseLayout(): void
    {
        $this->files->copyDirectory(
            dirname(__DIR__, 2).'/resources/defaults/base',
            $this->layoutDirectory('base'),
        );
        $this->info('Base layout published.');
    }
}
