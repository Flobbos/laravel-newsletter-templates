<?php

namespace Flobbos\NewsletterTemplates\Commands;

use Illuminate\Console\GeneratorCommand;

class ControllerCommand extends GeneratorCommand
{
    use ExitsOnFailure;

    protected $signature = 'newsletter-templates:controller {name} {--route=admin.newsletter-templates}';

    protected $description = 'Generate the newsletter template controller';

    protected $type = 'Controller';

    private ?string $route = null;

    private ?string $viewPath = null;

    public function handle()
    {
        if ($this->confirm('The controller will use route ['.$this->option('route').']. Change it?', false)) {
            $answer = $this->ask('Route name', (string) $this->option('route'));
            $this->route = is_string($answer) ? $answer : (string) $this->option('route');
        }

        $defaultViewPath = 'newsletter-templates.templates';

        if ($this->confirm('The controller will use view path ['.$defaultViewPath.']. Change it?', false)) {
            $this->info('Use dot notation for the view path.');
            $answer = $this->ask('View path', $defaultViewPath);
            $this->viewPath = is_string($answer) ? $answer : $defaultViewPath;
        }

        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);

        if ($this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists.');

            return false;
        }

        $this->makeDirectory($path);
        $this->files->put($path, $this->buildClass($name));
        $this->info($this->type.' created.');

        return null;
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Controllers';
    }

    protected function getStub(): string
    {
        return dirname(__DIR__, 2).'/resources/stubs/controllers/template_controller.stub';
    }

    protected function buildClass($name): string
    {
        $controllerNamespace = $this->getNamespace($name);

        return str_replace(
            [
                "use {$controllerNamespace}\Controller;\n",
                'DummyViewPath',
                'DummyRoute',
            ],
            [
                '',
                $this->viewPath ?? 'newsletter-templates.templates',
                $this->route ?? (string) $this->option('route'),
            ],
            parent::buildClass($name),
        );
    }
}
