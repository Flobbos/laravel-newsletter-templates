<?php

use Flobbos\NewsletterTemplates\Contracts\TemplateContract;
use Flobbos\NewsletterTemplates\Exceptions\CompilerException;
use Flobbos\NewsletterTemplates\Exceptions\NoLayoutsException;
use Flobbos\NewsletterTemplates\Models\NewsletterTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

function prepareBaseLayout(string $blade = '<mjml><mj-body>Hello {{ $name ?? "world" }}</mj-body></mjml>'): void
{
    $layout = resource_path(config('newsletter-templates.layout_path').'/base');
    File::ensureDirectoryExists($layout);
    File::put($layout.'/base.blade.php', $blade);

    $storage = storage_path((string) config('newsletter-templates.template_storage_path'));
    File::deleteDirectory($storage.'/welcome');
    File::ensureDirectoryExists($storage);
    app('view')->addLocation($storage);
}

function compiledHtmlPath(string $name = 'welcome'): string
{
    return storage_path('app/public/'.config('newsletter-templates.asset_path').'/'.$name.'/'.$name.'.html');
}

it('posts rendered mjml to the remote compiler and stores the html', function () {
    Http::fake([
        'https://compiler.test/generate' => Http::response('<html><body>ok</body></html>', 200),
    ]);

    config([
        'newsletter-templates.compiler' => 'remote',
        'newsletter-templates.api_url' => 'https://compiler.test/generate',
        'newsletter-templates.api_token' => 'secret-token',
    ]);

    prepareBaseLayout();

    expect(app(TemplateContract::class)->compile('welcome', ['name' => 'Ada']))->toBeTrue();
    expect(File::get(compiledHtmlPath()))->toBe('<html><body>ok</body></html>');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://compiler.test/generate'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && str_contains($request->body(), 'Hello Ada');
    });
});

it('throws when the remote compiler rejects the template', function () {
    Http::fake([
        'https://compiler.test/generate' => Http::response('bad mjml', 422),
    ]);

    config([
        'newsletter-templates.api_url' => 'https://compiler.test/generate',
        'newsletter-templates.api_token' => 'secret-token',
    ]);

    prepareBaseLayout();

    app(TemplateContract::class)->compile('welcome');
})->throws(CompilerException::class);

it('compiles with the local mjml binary and inlines companion styles', function () {
    Process::fake([
        '*' => Process::result('<html>local</html>'),
    ]);

    config([
        'newsletter-templates.compiler' => 'local',
        'newsletter-templates.mjml_binary' => 'mjml',
    ]);

    prepareBaseLayout('<mjml><mj-head></mj-head><mj-body>Hello</mj-body></mjml>');

    $layout = resource_path(config('newsletter-templates.layout_path').'/base');
    File::put($layout.'/base.scss', 'h1{color:red}');

    expect(app(TemplateContract::class)->compile('welcome'))->toBeTrue();
    expect(File::get(compiledHtmlPath()))->toBe('<html>local</html>');

    Process::assertRan(function ($process) {
        return $process->command === ['mjml', '--stdin']
            && str_contains((string) $process->input, '<mj-style>')
            && str_contains((string) $process->input, 'h1{color:red}');
    });
});

it('stores a slugged template name and leaves unrelated stylesheets alone', function () {
    Http::fake([
        'https://compiler.test/generate' => Http::response('<html>named</html>', 200),
    ]);

    config([
        'newsletter-templates.api_url' => 'https://compiler.test/generate',
        'newsletter-templates.api_token' => 'secret-token',
    ]);

    prepareBaseLayout();
    $layout = resource_path(config('newsletter-templates.layout_path').'/base');
    File::put($layout.'/database.scss', '$keep: true;');

    $template = NewsletterTemplate::query()->create([
        'template_name' => 'My Template',
        'layout' => 'base',
        'title' => 'Mine',
    ]);

    expect(app(TemplateContract::class)->compile('My Template'))->toBeTrue();

    $directory = storage_path((string) config('newsletter-templates.template_storage_path').'/my-template');

    expect(File::get(compiledHtmlPath('my-template')))->toBe('<html>named</html>')
        ->and(File::exists($directory.'/database.scss'))->toBeTrue()
        ->and(File::exists($directory.'/datamy-template.scss'))->toBeFalse();

    expect(app(TemplateContract::class)->delete($template->id))->toBeTrue()
        ->and(File::exists(compiledHtmlPath('my-template')))->toBeFalse();
});

it('refuses a template name that leaves the template directory', function () {
    prepareBaseLayout();

    expect(fn () => app(TemplateContract::class)->compile('../secret'))
        ->toThrow(InvalidArgumentException::class);
});

it('throws when the layout is missing', function () {
    config(['newsletter-templates.layout_path' => 'newsletter-templates/missing-layouts']);

    app(TemplateContract::class)->compile('missing-layout-template');
})->throws(NoLayoutsException::class);
