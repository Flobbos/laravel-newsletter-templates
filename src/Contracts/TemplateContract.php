<?php

namespace Flobbos\NewsletterTemplates\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface TemplateContract
{
    /**
     * @return Collection<int, Model>
     */
    public function get(): Collection;

    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data, bool $returnModel = false): bool|Model;

    /**
     * @param  array<int, string>|string  $relations
     */
    public function with(array|string $relations): static;

    public function find(int|string $id): ?Model;

    public function delete(int|string $id): bool;

    /**
     * @return Collection<int, Model>
     */
    public function getTemplatesFromDB(): Collection;

    public function setTemplate(string $templateName): static;

    public function getTemplate(): ?string;

    /**
     * @param  array<string, mixed>  $data
     */
    public function compile(string $templateName, array $data = []): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveViewAsHtml(string $view, array $data): string;

    public function copyImages(): bool;

    public function templateExists(string $templateName): bool;

    /**
     * @return array<int, string>
     */
    public function getLayouts(): array;

    /**
     * @return array<string, string>
     */
    public function getValidationRulesStore(): array;

    /**
     * @return array<string, string>
     */
    public function getValidationRulesUpdate(int|string|null $id = null): array;
}
