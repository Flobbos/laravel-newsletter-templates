<?php

return [
    // remote posts MJML to api_url. local runs mjml_binary.
    'compiler' => 'remote',

    'api_url' => 'https://blinky.ultrabold.de/api/mjml/generate',

    'api_token' => env('NEWSLETTER_TEMPLATES_API_TOKEN', ''),

    // Executable used when compiler=local. SCSS companions are inlined as mj-style;
    // Sass itself is only compiled by the remote endpoint.
    'mjml_binary' => 'mjml',

    'multi_layout' => true,

    'layout_path' => 'newsletter-templates/layouts',

    'base_layout' => 'base',

    // Legacy folder used when template_location=resource.
    'template_path' => 'newsletter-templates/templates',

    // storage|resource
    'template_location' => 'storage',

    'template_storage_path' => 'app/newsletter-templates/templates',

    // Public disk folder under storage/app/public. Served via storage:link.
    'asset_path' => 'newsletter-assets',

    // Used when App\Models\NewsletterTemplate does not exist yet.
    'model' => 'App\\Models\\NewsletterTemplate',

    'newsletter_template_route' => 'admin.newsletter-templates.',

    // tailwind|bootstrap4. Picks the generator stubs and the notifications partial.
    'css_framework' => 'tailwind',

    // Bootstrap stubs extend layouts.{layout_file}.
    'layout_file' => 'admin',

    'preview_email' => 'tester@example.com',

    'preview_subject' => 'Newsletter preview',
];
