<?php

use Flobbos\NewsletterTemplates\Contracts\TemplateContract;
use Flobbos\NewsletterTemplates\Models\NewsletterTemplate;

function makeTemplate(string $name, string $createdAt): NewsletterTemplate
{
    $template = new NewsletterTemplate([
        'template_name' => $name,
        'layout' => 'base',
        'title' => ucfirst($name),
    ]);
    $template->created_at = $createdAt;
    $template->updated_at = $createdAt;
    $template->save();

    return $template;
}

it('orders templates by a column descending', function () {
    makeTemplate('alpha', '2020-01-01 00:00:00');
    makeTemplate('bravo', '2022-01-01 00:00:00');
    makeTemplate('charlie', '2021-01-01 00:00:00');

    $ordered = app(TemplateContract::class)
        ->orderBy('created_at', 'desc')
        ->get();

    expect($ordered->pluck('template_name')->all())
        ->toBe(['bravo', 'charlie', 'alpha']);
});

it('orders templates ascending by default', function () {
    makeTemplate('alpha', '2020-01-01 00:00:00');
    makeTemplate('bravo', '2022-01-01 00:00:00');
    makeTemplate('charlie', '2021-01-01 00:00:00');

    $ordered = app(TemplateContract::class)
        ->orderBy('created_at')
        ->get();

    expect($ordered->pluck('template_name')->all())
        ->toBe(['alpha', 'charlie', 'bravo']);
});

it('returns the service from orderBy so calls can be chained', function () {
    $service = app(TemplateContract::class);

    expect($service->orderBy('created_at', 'desc'))->toBe($service);
});

it('gets all templates', function () {
    makeTemplate('alpha', '2020-01-01 00:00:00');
    makeTemplate('bravo', '2021-01-01 00:00:00');

    expect(app(TemplateContract::class)->get())->toHaveCount(2);
});

it('creates and finds a template', function () {
    $service = app(TemplateContract::class);

    $created = $service->create([
        'template_name' => 'welcome',
        'layout' => 'base',
        'title' => 'Welcome',
    ]);

    expect($service->find($created->id)->template_name)->toBe('welcome');
});

it('accepts the current template name when validating an update', function () {
    $template = makeTemplate('welcome', '2024-01-01 00:00:00');

    $validator = validator(
        ['template_name' => 'welcome'],
        app(TemplateContract::class)->getValidationRulesUpdate($template->id),
    );

    expect($validator->passes())->toBeTrue();
});
