<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire\Admin;

use App\Modules\Catalog\Livewire\Concerns\InteractsWithCatalogActor;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Categories')]
class CategoryIndex extends Component
{
    use HasTableQuery, InteractsWithCatalogActor, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public ?string $editingUuid = null;

    public string $name = '';

    public string $parentUuid = '';

    public ?string $notice = null;

    protected function tableKey(): string
    {
        return 'categories';
    }

    public function mount(): void
    {
        $this->allow('categories.manage');
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.categories.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function edit(string $uuid): void
    {
        $this->allow('categories.manage');
        $this->resetErrorBag();
        $this->notice = null;

        $category = Category::query()->where('uuid', $uuid)->firstOrFail();

        $this->editingUuid = $category->uuid;
        $this->name = $category->name;
        $this->parentUuid = (string) ($category->parent?->uuid ?? '');
    }

    public function cancel(): void
    {
        $this->resetErrorBag();
        $this->reset('editingUuid', 'name', 'parentUuid');
    }

    public function save(CategoryService $categories): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('categories.manage');

        $data = ['name' => $this->name, 'parent_uuid' => $this->parentUuid !== '' ? $this->parentUuid : null];

        try {
            if ($this->editingUuid === null) {
                $categories->create($actor, $data);
                $this->notice = 'Category created.';
            } else {
                $category = Category::query()->where('uuid', $this->editingUuid)->firstOrFail();
                $categories->update($actor, $category, $data);
                $this->notice = 'Category updated.';
            }

            $this->reset('editingUuid', 'name', 'parentUuid');
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('category', $exception->getMessage());
        }
    }

    public function delete(string $uuid, CategoryService $categories): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('categories.manage');

        $category = Category::query()->where('uuid', $uuid)->firstOrFail();

        try {
            $categories->delete($actor, $category);
            $this->notice = 'Category deleted.';
        } catch (ConflictException $exception) {
            $this->addError('category', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $categories = $this->applyTableQuery(Category::query()->with('parent'))->paginate($this->resolvedPerPage());

        return view('catalog.admin.categories', [
            'categories' => $categories,
            'parents' => Category::query()->whereNull('parent_id')->orderBy('name')->get(['uuid', 'name']),
        ]);
    }
}
