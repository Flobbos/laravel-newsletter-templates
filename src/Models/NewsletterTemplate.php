<?php

namespace Flobbos\NewsletterTemplates\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterTemplate extends Model
{
    protected $fillable = [
        'template_name',
        'layout',
        'title',
    ];

    /**
     * @return Attribute<string, never>
     */
    protected function templateUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->compiledUrl(),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function templateFileUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->compiledUrl().'/'.$this->storedName().'.html',
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function templatePath(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->compiledPath(),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function templateFilePath(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->compiledPath().'/'.$this->storedName().'.html',
        );
    }

    private function storedName(): string
    {
        return Str::slug((string) $this->getAttribute('template_name'));
    }

    private function compiledUrl(): string
    {
        return url('storage/'.config('newsletter-templates.asset_path').'/'.$this->storedName());
    }

    private function compiledPath(): string
    {
        return storage_path('app/public/'.config('newsletter-templates.asset_path').'/'.$this->storedName());
    }
}
