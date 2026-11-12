<?php

namespace Flobbos\NewsletterTemplates;

use Flobbos\NewsletterTemplates\Contracts\CompilerContract;
use Flobbos\NewsletterTemplates\Contracts\TemplateContract;
use Flobbos\NewsletterTemplates\Exceptions\NoLayoutsException;
use Flobbos\NewsletterTemplates\Exceptions\TemplateNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

class Templates implements TemplateContract
{
    protected Filesystem $disk;

    protected ?string $template = null;

    /** @var Model|Builder<Model> */
    protected Model|Builder $templateDb;

    public function __construct()
    {
        $this->disk = Storage::disk('newsletter_templates');
        $this->templateDb = $this->newModel();
    }

    public function get(): Collection
    {
        /** @var Collection<int, Model> $templates */
        $templates = $this->templateDb->get();

        return $templates;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->templateDb = $this->templateDb->orderBy($column, $direction);

        return $this;
    }

    public function create(array $data): Model
    {
        if (! config('newsletter-templates.multi_layout') && empty($data['layout'])) {
            $data['layout'] = config('newsletter-templates.base_layout', 'base');
        }

        return $this->templateDb->create($data);
    }

    public function update(int|string $id, array $data, bool $returnModel = false): bool|Model
    {
        if (! config('newsletter-templates.multi_layout') && empty($data['layout'])) {
            $data['layout'] = config('newsletter-templates.base_layout', 'base');
        }

        $model = $this->find($id);

        if ($model === null) {
            return false;
        }

        $updated = $model->update($data);

        return $returnModel ? $model : $updated;
    }

    public function with(array|string $relations): static
    {
        $this->templateDb = $this->templateDb->with($relations);

        return $this;
    }

    public function find(int|string $id): ?Model
    {
        $found = $this->templateDb->find($id);

        return $found instanceof Model ? $found : null;
    }

    public function delete(int|string $id): bool
    {
        $model = $this->find($id);

        if ($model === null) {
            return false;
        }

        $name = $this->safeName((string) $model->getAttribute('template_name'));
        $this->disk->deleteDirectory($name);
        File::deleteDirectory($this->templatePath($name));

        return (bool) $model->delete();
    }

    public function getTemplatesFromDB(): Collection
    {
        return $this->get();
    }

    public function setTemplate(string $templateName): static
    {
        $this->template = $templateName;

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function compile(string $templateName, array $data = []): bool
    {
        $templateName = $this->safeName($templateName);
        $this->setTemplate($templateName);

        $templateModel = Arr::get($data, 'template');
        $layoutChanged = $templateModel instanceof Model
            ? (bool) Arr::get($templateModel->getChanges(), 'layout')
            : false;
        $layout = Arr::get($data, 'template.layout', config('newsletter-templates.base_layout', 'base'));

        if (! is_string($layout) || $layout === '') {
            $layout = (string) config('newsletter-templates.base_layout', 'base');
        }

        $layout = $this->safeName($layout);

        if (! File::exists($this->templatePath($templateName)) || $layoutChanged) {
            $this->generateTemplate($layout);
        }

        $this->templateExists($templateName);
        $this->ensureViewLocation();

        $viewPath = $this->template.'.'.$this->template;
        $html = View::make($viewPath, $data)->render();
        $compiled = app(CompilerContract::class)->compile($html, $this->resourceFiles());

        $this->disk->put($this->template.'/'.$this->template.'.html', $compiled);
        $this->copyImages();

        return true;
    }

    public function saveViewAsHtml(string $view, array $data): string
    {
        $html = View::make($view.'.views.'.$view, $data)->render();
        $this->disk->put($this->template.'/'.$this->template.'.html', $html);

        return $html;
    }

    public function copyImages(): bool
    {
        return File::copyDirectory(
            $this->templatePath().'/images',
            $this->disk->path($this->template.'/images'),
        );
    }

    public function templateExists(string $templateName): bool
    {
        if (! File::exists($this->templatePath($templateName))) {
            throw new TemplateNotFoundException('Given template "'.$templateName.'" not found at '.$this->templateBasePath());
        }

        return true;
    }

    public function getLayouts(): array
    {
        if (! config('newsletter-templates.multi_layout')) {
            return [];
        }

        $path = resource_path((string) config('newsletter-templates.layout_path'));

        if (! File::isDirectory($path)) {
            throw new NoLayoutsException($path);
        }

        $directories = File::directories($path);

        if ($directories === []) {
            throw new NoLayoutsException($path);
        }

        return array_map(basename(...), $directories);
    }

    public function getValidationRulesStore(): array
    {
        if (config('newsletter-templates.multi_layout')) {
            return [
                'template_name' => 'required|unique:newsletter_templates,template_name',
                'layout' => 'required',
            ];
        }

        return [
            'template_name' => 'required|unique:newsletter_templates,template_name',
        ];
    }

    public function getValidationRulesUpdate(int|string|null $id = null): array
    {
        $unique = 'required|unique:newsletter_templates,template_name';

        if ($id !== null) {
            $unique .= ','.$id;
        }

        return [
            'template_name' => $unique,
        ];
    }

    private function newModel(): Model
    {
        $configured = config('newsletter-templates.model');

        if (is_string($configured) && $configured !== '' && class_exists($configured)) {
            $model = new $configured;

            if ($model instanceof Model) {
                return $model;
            }
        }

        return new Models\NewsletterTemplate;
    }

    private function generateTemplate(string $layout): void
    {
        $stubPath = $this->layoutPath($layout);
        $destPath = $this->templatePath((string) $this->template);

        if (! File::isDirectory($stubPath)) {
            throw new NoLayoutsException($stubPath);
        }

        File::deleteDirectory($destPath);
        File::copyDirectory($stubPath, $destPath);

        foreach (File::allFiles($destPath) as $file) {
            $filename = $file->getFilename();

            if (! str_starts_with($filename, $layout.'.') && ! str_starts_with($filename, $layout.'-')) {
                continue;
            }

            $renamed = $file->getPath().'/'.$this->template.substr($filename, strlen($layout));
            File::move($file->getPathname(), $renamed);
        }
    }

    /**
     * @return array<int, array{filename: string, contents: string}>
     */
    private function resourceFiles(): array
    {
        $directory = $this->templatePath((string) $this->template);

        if (! File::isDirectory($directory)) {
            return [];
        }

        $files = iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(true)->in($directory)->depth(0)->name('*.scss')->sortByName(),
            false,
        );

        $resources = [];

        foreach ($files as $file) {
            $pathname = (string) $file;
            $resources[] = [
                'filename' => File::name($pathname) === $this->template
                    ? 'template.'.File::extension($pathname)
                    : File::name($pathname).'.'.File::extension($pathname),
                'contents' => File::get($pathname),
            ];
        }

        return $resources;
    }

    private function ensureViewLocation(): void
    {
        $path = $this->templateBasePath();
        File::ensureDirectoryExists($path);

        $finder = View::getFinder();

        if ($finder instanceof FileViewFinder && ! in_array($path, $finder->getPaths(), true)) {
            $finder->addLocation($path);
        }
    }

    private function layoutPath(string $layout): string
    {
        return resource_path((string) config('newsletter-templates.layout_path').'/'.$layout);
    }

    private function templateBasePath(): string
    {
        if (config('newsletter-templates.template_location', 'storage') === 'resource') {
            return resource_path((string) config('newsletter-templates.template_path'));
        }

        return storage_path((string) config('newsletter-templates.template_storage_path', 'app/newsletter-templates/templates'));
    }

    private function templatePath(?string $template = null): string
    {
        $name = $template ?: $this->template;
        $basePath = $this->templateBasePath();

        if ($name === null || $name === '') {
            return $basePath;
        }

        return rtrim($basePath, '/').'/'.$name;
    }

    private function safeName(string $name): string
    {
        if ($name === '' || str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Invalid template or layout name.');
        }

        $slug = Str::slug($name);

        if ($slug === '') {
            throw new InvalidArgumentException('Invalid template or layout name.');
        }

        return $slug;
    }
}
