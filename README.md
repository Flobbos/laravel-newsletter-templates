# laravel-newsletter-templates

MJML newsletter workshop for Laravel 12 and 13. Compile a template to HTML and images on the public disk. No email-service dependency.

## Install

```bash
composer require flobbos/laravel-newsletter-templates
php artisan newsletter-templates:install
php artisan migrate
```

`--deployment` only creates the storage directories and the `public/storage` link.

## Config

`php artisan vendor:publish --tag=newsletter-templates-config`

| Key | Default | |
|---|---|---|
| `compiler` | `remote` | `remote` or `local` |
| `api_url` | blinky MJML endpoint | POST target for the remote compiler |
| `api_token` | `NEWSLETTER_TEMPLATES_API_TOKEN` | Bearer token |
| `mjml_binary` | `mjml` | Used when `compiler=local` |
| `layout_path` | `newsletter-templates/layouts` | Under `resources/` |
| `template_storage_path` | `app/newsletter-templates/templates` | Source files, under `storage/` |
| `asset_path` | `newsletter-assets` | Compiled HTML and images, under `storage/app/public` |
| `preview_email` | `tester@example.com` | Used by the generated preview action |
| `css_framework` | `tailwind` | `tailwind` or `bootstrap4` for generated views |

The remote compiler receives the rendered MJML plus any sibling `.scss` files. The local binary receives MJML on stdin (`mjml --stdin`). Companion stylesheets are inlined as `<mj-style>` and are not run through Sass. Use the remote compiler when the stylesheet is real SCSS.

## Usage

```php
use Flobbos\NewsletterTemplates\Contracts\TemplateContract;

$templates = app(TemplateContract::class);

$template = $templates->create([
    'template_name' => 'welcome',
    'layout' => 'base',
    'title' => 'Welcome',
]);

$templates->compile($template->template_name, ['template' => $template]);

$url = $template->template_file_url;
$path = $template->template_file_path;
```

`compile()` renders the Blade source, posts the MJML, and writes `{asset_path}/{name}/{name}.html` on the `newsletter_templates` disk. That disk is `storage/app/public/{asset_path}`, so the file is public after `storage:link`.

Switch compilers with `newsletter-templates.compiler`. `local` needs the `mjml` CLI on `PATH`, or set `mjml_binary` to an absolute path.

Images in a template:

```blade
<img src="{{ newsletter_image($templateName, 'logo.png') }}" alt="">
```

That is the public URL of the file compile copies onto the `newsletter_templates` disk: `/storage/{asset_path}/{template}/images/{file}`.

## Generators

```bash
php artisan newsletter-templates:layout promo
php artisan newsletter-templates:controller NewsletterTemplateController
php artisan newsletter-templates:views admin.templates
```

The controller and views are generated into the app. Register the resource routes yourself. The model publish tag writes `app/Models/NewsletterTemplate.php` extending the package model. If that class exists, the service uses it; otherwise it uses `Flobbos\NewsletterTemplates\Models\NewsletterTemplate`.

## Requirements

PHP 8.3+, Laravel 12 or 13.
