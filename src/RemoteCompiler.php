<?php

namespace Flobbos\NewsletterTemplates;

use Flobbos\NewsletterTemplates\Contracts\CompilerContract;
use Flobbos\NewsletterTemplates\Exceptions\CompilerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemoteCompiler implements CompilerContract
{
    public function compile(string $mjml, ?array $scss = null): string
    {
        $url = (string) config('newsletter-templates.api_url');
        $token = (string) config('newsletter-templates.api_token');

        $request = Http::timeout(60)
            ->withToken($token)
            ->acceptJson()
            ->attach('html', $mjml);

        foreach ($scss ?? [] as $file) {
            $request = $request->attach(
                'sass-files[]',
                $file['contents'],
                $file['filename'],
                ['Content-Type' => $this->contentType($file['filename'])],
            );
        }

        try {
            $response = $request->post($url);
        } catch (ConnectionException $exception) {
            Log::error('RemoteCompiler error: '.$exception->getMessage());

            throw new CompilerException('Remote MJML compiler unreachable: '.$exception->getMessage(), 0, $exception);
        }

        if ($response->failed()) {
            $message = 'Remote MJML compiler failed ('.$response->status().'): '.$response->body();
            Log::error('RemoteCompiler error: '.$message);

            throw new CompilerException($message);
        }

        return $response->body();
    }

    private function contentType(string $filename): string
    {
        return match (pathinfo($filename, PATHINFO_EXTENSION)) {
            'scss' => 'text/x-scss',
            'css' => 'text/css',
            'txt' => 'text/plain',
            default => 'text/plain',
        };
    }
}
