<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\Concerns\ValidatesInput;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class CategoryService
{
    use ValidatesInput;

    /**
     * @param  array<string, mixed>  $data  name, parent_uuid
     */
    public function create(User $actor, array $data): Category
    {
        $valid = $this->validated(Arr::only($data, ['name', 'parent_uuid']));
        $parentId = $this->resolveParent($valid['parent_uuid'] ?? null, null);

        return DB::transaction(fn (): Category => Category::create(['name' => $valid['name'], 'parent_id' => $parentId]));
    }

    /**
     * The slug never changes after create.
     *
     * @param  array<string, mixed>  $data  name, parent_uuid
     */
    public function update(User $actor, Category $category, array $data): Category
    {
        $this->assertLive($category);

        $input = Arr::only($data, ['name', 'parent_uuid']);
        $valid = $this->validated($input);
        $attributes = ['name' => $valid['name']];

        if (array_key_exists('parent_uuid', $input)) {
            $attributes['parent_id'] = $this->resolveParent($valid['parent_uuid'] ?? null, $category);
        }

        DB::transaction(function () use ($category, $attributes): void {
            $category->fill($attributes)->save();
        });

        return $category;
    }

    public function delete(User $actor, Category $category): void
    {
        $this->assertLive($category);

        if ($category->products()->withTrashed()->exists()) {
            throw new ConflictException('The category still has products.');
        }

        if ($category->children()->withTrashed()->exists()) {
            throw new ConflictException('The category still has child categories.');
        }

        DB::transaction(function () use ($category): void {
            $category->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validated(array $input): array
    {
        return $this->validate($input, [
            'name' => ['required', 'string', 'max:255'],
            'parent_uuid' => ['nullable', 'uuid', Rule::exists('product_categories', 'uuid')->whereNull('deleted_at')],
        ]);
    }

    private function resolveParent(?string $uuid, ?Category $self): ?int
    {
        if ($uuid === null) {
            return null;
        }

        $parent = Category::query()->where('uuid', $uuid)->first();

        if ($parent === null) {
            throw new DomainValidationException(['parent_uuid' => 'The parent category does not exist.']);
        }

        if ($self !== null && $parent->is($self)) {
            throw new DomainValidationException(['parent_uuid' => 'A category cannot be its own parent.']);
        }

        if ($parent->parent_id !== null) {
            throw new DomainValidationException(['parent_uuid' => 'Categories can be nested one level deep only.']);
        }

        if ($self !== null && $self->children()->withTrashed()->exists()) {
            throw new DomainValidationException(['parent_uuid' => 'A category with children cannot be nested.']);
        }

        return $parent->id;
    }

    private function assertLive(Category $category): void
    {
        if ($category->trashed()) {
            throw new ConflictException('The category is deleted.');
        }
    }
}
