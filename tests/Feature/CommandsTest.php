<?php

use Illuminate\Support\Facades\File;

it('creates the template directories during a deployment install', function () {
    $this->artisan('newsletter-templates:install', ['--deployment' => true])
        ->assertSuccessful();

    expect(File::isDirectory(storage_path((string) config('newsletter-templates.template_storage_path'))))->toBeTrue()
        ->and(File::isDirectory(storage_path('app/public/'.config('newsletter-templates.asset_path'))))->toBeTrue()
        ->and(File::isDirectory(resource_path((string) config('newsletter-templates.layout_path'))))->toBeTrue();
});

it('publishes config, the base layout, and an app model that extends the package model', function () {
    $this->artisan('newsletter-templates:install')
        ->expectsConfirmation('Publish config, migrations, base layout, and model?', 'yes')
        ->expectsConfirmation('Create template directories and the public storage link?', 'yes')
        ->assertSuccessful();

    expect(File::exists(config_path('newsletter-templates.php')))->toBeTrue()
        ->and(File::exists(resource_path('newsletter-templates/layouts/base/base.blade.php')))->toBeTrue()
        ->and(File::get(app_path('Models/NewsletterTemplate.php')))->toContain('extends BaseNewsletterTemplate');
});

it('generates a layout from the published base layout', function () {
    File::deleteDirectory(resource_path('newsletter-templates/layouts/promo'));

    $base = resource_path('newsletter-templates/layouts/base');
    File::ensureDirectoryExists($base);
    File::put($base.'/base.blade.php', '<mjml><mj-body>Base</mj-body></mjml>');
    File::put($base.'/base.scss', 'body{}');

    $this->artisan('newsletter-templates:layout', [
        'name' => 'promo',
        '--no-interaction' => true,
    ])->assertSuccessful();

    $promo = resource_path('newsletter-templates/layouts/promo/promo.blade.php');

    expect(File::get($promo))->toContain('<mj-body>Base</mj-body>');
});

it('writes template views that use the package translations', function () {
    File::deleteDirectory(resource_path('views/admin'));
    config(['newsletter-templates.css_framework' => 'tailwind']);

    $this->artisan('newsletter-templates:views', [
        'path' => 'admin.templates',
    ])->expectsConfirmation('The views will use route [admin.newsletter-templates]. Change it?', 'no')
        ->assertSuccessful();

    $views = File::allFiles(resource_path('views'));

    expect($views)->not->toBeEmpty();

    $contents = implode("\n", array_map(
        fn ($file) => File::get($file->getPathname()),
        $views,
    ));

    expect($contents)->toContain('newsletter-templates::templates')
        ->and($contents)->toContain('newsletter-templates.preview_email');
});

it('writes a controller against the package contract', function () {
    File::delete(app_path('Http/Controllers/NewsletterTemplateController.php'));

    $this->artisan('newsletter-templates:controller', [
        'name' => 'NewsletterTemplateController',
    ])->expectsConfirmation('The controller will use route [admin.newsletter-templates]. Change it?', 'no')
        ->expectsConfirmation('The controller will use view path [newsletter-templates.templates]. Change it?', 'no')
        ->assertSuccessful();

    $controller = File::get(app_path('Http/Controllers/NewsletterTemplateController.php'));

    expect($controller)->toContain('Flobbos\\NewsletterTemplates\\Contracts\\TemplateContract')
        ->and($controller)->toContain('newsletter-templates.preview_email')
        ->and($controller)->toContain('getValidationRulesUpdate($id)');
});
