<?php

declare(strict_types=1);

namespace App\Support\Livewire;

use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Search, filter, sort and page-size state for Livewire lists.
 * Every field name that can reach SQL comes from config/tables.php, never from the client.
 */
trait HasTableQuery
{
    public string $search = '';

    /** @var array<string, mixed> */
    public array $filters = [];

    public string $sortField = '';

    public string $sortDirection = 'asc';

    public int $perPage = 0;

    /**
     * Key of this list in config/tables.php.
     */
    abstract protected function tableKey(): string;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function applyTableQuery(Builder $query): Builder
    {
        $config = $this->tableConfig();
        $this->normalizeTableState($config);

        $search = trim($this->search);

        if ($search !== '') {
            $like = '%'.Sql::escapeLike($search).'%';

            $query->where(function (Builder $inner) use ($config, $like): void {
                foreach ($config['searchable'] as $column) {
                    $inner->orWhere($column, 'like', $like);
                }
            });
        }

        foreach ($this->filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query->orderBy($this->sortField, $this->sortDirection);
    }

    public function resolvedPerPage(): int
    {
        $this->normalizeTableState($this->tableConfig());

        return $this->perPage;
    }

    public function updatedSearch(): void
    {
        $this->resetTablePage();
    }

    public function updatedFilters(): void
    {
        $this->resetTablePage();
    }

    public function updatedSortField(): void
    {
        $this->resetTablePage();
    }

    public function updatedSortDirection(): void
    {
        $this->resetTablePage();
    }

    public function updatedPerPage(): void
    {
        $this->resetTablePage();
    }

    /**
     * @return array{searchable: array<int, string>, filterable: array<int, string>, sortable: array<int, string>, default_sort: array{0: string, 1: string}}
     */
    private function tableConfig(): array
    {
        $config = config('tables.'.$this->tableKey());

        if (! is_array($config)) {
            throw new LogicException('Unknown table key ['.$this->tableKey().'].');
        }

        return $config;
    }

    /**
     * @param  array{searchable: array<int, string>, filterable: array<int, string>, sortable: array<int, string>, default_sort: array{0: string, 1: string}}  $config
     */
    private function normalizeTableState(array $config): void
    {
        [$defaultField, $defaultDirection] = $config['default_sort'];

        if (! in_array($this->sortField, $config['sortable'], true)) {
            $this->sortField = $defaultField;
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = $defaultDirection;
        }

        $clean = [];

        foreach ($this->filters as $field => $value) {
            if (! is_string($field) || ! in_array($field, $config['filterable'], true) || ! is_scalar($value)) {
                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === '') {
                continue;
            }

            $clean[$field] = $value;
        }

        $this->filters = $clean;

        $default = (int) config('shop.pagination.default');
        $max = (int) config('shop.pagination.max');

        $this->perPage = $this->perPage < 1 ? $default : min($this->perPage, $max);
    }

    private function resetTablePage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
