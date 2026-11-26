<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CatalogFilter
{
    public function __construct(
        public ?string $q = null,
        public ?string $category = null,
        public ?string $status = null,
        public ?string $sort = 'title',
    ) {
    }

    public function query(): string
    {
        return trim($this->q ?? '');
    }

    public function selectedCategory(): string
    {
        return $this->category ?? '';
    }

    public function selectedStatus(): string
    {
        return $this->status ?? '';
    }

    public function selectedSort(): string
    {
        return in_array($this->sort, ['title', 'year', 'availability'], true) ? $this->sort : 'title';
    }
}
