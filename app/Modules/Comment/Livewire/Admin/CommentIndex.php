<?php

declare(strict_types=1);

namespace App\Modules\Comment\Livewire\Admin;

use App\Models\User;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Services\CommentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Comments')]
class CommentIndex extends Component
{
    use HasTableQuery, WithPagination;

    private const PERMISSION = 'comments.moderate';

    protected string $paginationTheme = 'bootstrap';

    public string $deleted = 'without';

    protected function tableKey(): string
    {
        return 'comments';
    }

    public function mount(): void
    {
        $this->allow();
    }

    public function updatedDeleted(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, (array) config('tables.comments.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function hide(string $uuid, CommentService $comments): void
    {
        $actor = $this->allow();
        $this->guarded(fn () => $comments->hide($actor, $this->find($uuid)));
    }

    public function show(string $uuid, CommentService $comments): void
    {
        $actor = $this->allow();
        $this->guarded(fn () => $comments->show($actor, $this->find($uuid)));
    }

    public function remove(string $uuid, CommentService $comments): void
    {
        $actor = $this->allow();
        $this->guarded(fn () => $comments->deleteByModerator($actor, $this->find($uuid)));
    }

    public function render(): View
    {
        $modes = (array) config('tables.comments.deleted_modes');
        $mode = in_array($this->deleted, $modes, true) ? $this->deleted : 'without';

        $query = Comment::query();

        if ($mode === 'with') {
            $query->withTrashed();
        } elseif ($mode === 'only') {
            $query->onlyTrashed();
        }

        $comments = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        $authors = User::query()
            ->whereIn('id', $comments->getCollection()->pluck('user_id')->unique()->all())
            ->pluck('name', 'id');

        return view('comment.admin.comments', [
            'comments' => $comments,
            'authors' => $authors,
            'statuses' => config('shop.comment_statuses'),
            'entityTypes' => config('shop.comment_entity_types'),
            'modes' => $modes,
        ]);
    }

    private function allow(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can(self::PERMISSION), 403);

        return $user;
    }

    private function find(string $uuid): Comment
    {
        return Comment::query()->where('uuid', $uuid)->firstOr(fn () => abort(404));
    }

    private function guarded(callable $action): void
    {
        try {
            $action();
        } catch (ConflictException|DomainValidationException $e) {
            $this->addError('action', $e->getMessage());
        }
    }
}
