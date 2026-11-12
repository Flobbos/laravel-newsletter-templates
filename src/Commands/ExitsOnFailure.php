<?php

namespace Flobbos\NewsletterTemplates\Commands;

use Illuminate\Console\ManuallyFailedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * GeneratorCommand documents handle() as bool|null. Command then casts that
 * with (int), so both false and null become exit code 0.
 */
trait ExitsOnFailure
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $status = $this->laravel->call([$this, 'handle']);
        } catch (ManuallyFailedException $exception) {
            $this->components->error($exception->getMessage());

            return static::FAILURE;
        }

        if ($status === false) {
            return static::FAILURE;
        }

        return (int) ($status ?? static::SUCCESS);
    }
}
