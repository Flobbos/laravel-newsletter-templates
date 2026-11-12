<?php

namespace Flobbos\NewsletterTemplates;

use Flobbos\NewsletterTemplates\Contracts\CompilerContract;
use Flobbos\NewsletterTemplates\Exceptions\CompilerException;
use Illuminate\Support\Facades\Process;

class LocalCompiler implements CompilerContract
{
    public function compile(string $mjml, ?array $scss = null): string
    {
        $binary = (string) config('newsletter-templates.mjml_binary', 'mjml');
        $result = Process::input($this->inlineStyles($mjml, $scss ?? []))->run([$binary, '--stdin']);

        if ($result->failed()) {
            $message = trim($result->errorOutput()) !== ''
                ? trim($result->errorOutput())
                : 'mjml exited with status '.$result->exitCode();

            throw new CompilerException($message);
        }

        $html = trim($result->output());

        if ($html === '') {
            throw new CompilerException('mjml produced no HTML.');
        }

        return $html;
    }

    /**
     * @param  array<int, array{filename: string, contents: string}>  $scss
     */
    private function inlineStyles(string $mjml, array $scss): string
    {
        if ($scss === []) {
            return $mjml;
        }

        $css = implode("\n", array_map(
            fn (array $file): string => $file['contents'],
            $scss,
        ));
        $tag = "<mj-style>\n{$css}\n</mj-style>";

        if (str_contains($mjml, '</mj-head>')) {
            return str_replace('</mj-head>', $tag.'</mj-head>', $mjml);
        }

        return $tag.$mjml;
    }
}
