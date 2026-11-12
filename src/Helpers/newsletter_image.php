<?php

use Illuminate\Support\Str;

if (! function_exists('newsletter_image')) {
    function newsletter_image(string $templateName, string $filename): string
    {
        $assetPath = trim((string) config('newsletter-templates.asset_path', 'newsletter-assets'), '/');
        $template = Str::slug($templateName);
        $file = ltrim(str_replace('\\', '/', $filename), '/');

        if ($template === '' || $file === '' || str_contains($file, '..')) {
            throw new InvalidArgumentException('Invalid newsletter image path.');
        }

        return url('storage/'.$assetPath.'/'.$template.'/images/'.$file);
    }
}
