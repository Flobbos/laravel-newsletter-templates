<?php

namespace Flobbos\NewsletterTemplates\Contracts;

interface CompilerContract
{
    /**
     * @param  array<int, array{filename: string, contents: string}>  $scss
     */
    public function compile(string $mjml, ?array $scss = null): string;
}
