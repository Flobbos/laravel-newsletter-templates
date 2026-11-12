<?php

namespace Flobbos\NewsletterTemplates\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ViewCommand extends GeneratorCommand
{
    protected $signature = 'newsletter-templates:views {path} {--route=admin.newsletter-templates}';

    protected $description = 'Generate newsletter template views';

    protected $type = 'Views';

    private string $currentStub = '';

    private ?string $route = null;

    public function handle()
    {
        if ($this->confirm('The views will use route ['.$this->option('route').']. Change it?', false)) {
            $answer = $this->ask('Route name', (string) $this->option('route'));
            $this->route = is_string($answer) ? $answer : (string) $this->option('route');
        }

        $path = $this->destinationPath();

        if ($this->files->isDirectory($path)) {
            $this->error('Views already exist.');

            return false;
        }

        foreach ($this->stubMap() as $name => $stub) {
            $this->currentStub = $stub;
            $this->makeDirectory($path.'/'.$name);
            $this->files->put($path.'/'.$name, $this->buildFromStub());
        }

        $this->info('Views created.');

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function stubMap(): array
    {
        $framework = (string) config('newsletter-templates.css_framework', 'tailwind');
        $directory = dirname(__DIR__, 2).'/resources/stubs/views/'.$framework;

        if (! $this->files->isDirectory($directory)) {
            throw new InvalidArgumentException('Unknown css_framework ['.$framework.'].');
        }

        return [
            'index.blade.php' => $directory.'/index.stub',
            'create.blade.php' => $directory.'/create.stub',
            'edit.blade.php' => $directory.'/edit.stub',
        ];
    }

    protected function getStub(): string
    {
        return dirname(__DIR__, 2).'/resources/stubs/views/tailwind/index.stub';
    }

    private function destinationPath(): string
    {
        $relative = str_replace('.', '/', trim((string) $this->argument('path')));

        return resource_path('views/'.strtolower(Str::kebab($relative)));
    }

    private function buildFromStub(): string
    {
        return str_replace(
            ['DummyViewPath', 'DummyRoute'],
            [
                str_replace('/', '.', trim((string) $this->argument('path'))),
                $this->route ?? (string) $this->option('route'),
            ],
            $this->files->get($this->currentStub),
        );
    }
}
