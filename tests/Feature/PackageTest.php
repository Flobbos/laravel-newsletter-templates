<?php

use Flobbos\NewsletterTemplates\Contracts\CompilerContract;
use Flobbos\NewsletterTemplates\Contracts\TemplateContract;
use Flobbos\NewsletterTemplates\LocalCompiler;
use Flobbos\NewsletterTemplates\Models\NewsletterTemplate;
use Flobbos\NewsletterTemplates\RemoteCompiler;
use Flobbos\NewsletterTemplates\Templates;

it('binds the template contract to the templates service', function () {
    expect(app(TemplateContract::class))->toBeInstanceOf(Templates::class);
});

it('resolves the remote compiler by default and the local compiler when configured', function () {
    expect(app(CompilerContract::class))->toBeInstanceOf(RemoteCompiler::class);

    config(['newsletter-templates.compiler' => 'local']);

    expect(app(CompilerContract::class))->toBeInstanceOf(LocalCompiler::class);
});

it('builds a public image url from the asset path', function () {
    config(['newsletter-templates.asset_path' => 'newsletter-assets']);

    expect(newsletter_image('My Template', 'logo.png'))
        ->toBe('http://localhost/newsletter-assets/my-template/assets/images/logo.png');
});

it('exposes the compiled html url and filesystem path', function () {
    $template = NewsletterTemplate::query()->create([
        'template_name' => 'welcome',
        'layout' => 'base',
        'title' => 'Welcome',
    ]);

    expect($template->template_file_url)
        ->toBe('http://localhost/storage/newsletter-assets/welcome/welcome.html')
        ->and($template->template_file_path)
        ->toBe(storage_path('app/public/newsletter-assets/welcome/welcome.html'));
});
