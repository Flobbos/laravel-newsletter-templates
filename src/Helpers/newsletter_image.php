<?php

use Illuminate\Support\Str;

if (! function_exists('newsletter_image')) {
    function newsletter_image(string $templateName, string $filename): string
    {
        $assetPath = trim((string) config('newsletter-templates.asset_path', 'newsletter-assets'), '/');

        return url($assetPath.'/'.Str::slug($templateName).'/assets/images/'.$filename);
    }
}
