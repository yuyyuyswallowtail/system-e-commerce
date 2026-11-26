<?php

declare(strict_types=1);

namespace App\Modules\Comment\Livewire;

use App\Models\User;
use App\Modules\Comment\Models\Comment;
use App\Modules\Comment\Services\CommentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Comment thread of one entity. Enrichment on host pages: a failure renders a generic message.
 */
class Thread extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Locked]
    public string $entityType = '';

    #[Locked]
    public string $entityUuid = '';

    public string $body = '';

    public ?string $replyTo = null;

    public string $editing = '';

    public string $editBody = '';

    public function mount(string $entityType, string $entityUuid): void
    {
        $this->entityType = $entityType;
        $this->entityUuid = $entityUuid;
    }

    public function post(CommentService $comments): void
    {
        $actor = $this->actor();

        try {
            $comments->post($actor, $this->entityType, $this->entityUuid, $this->body, $this->replyTo);
        } catch (DomainValidationException $e) {
            $this->addError('body', (string) collect($e->fields())->first());

            return;
        } catch (ConflictException $e) {
            $this->addError('body', $e->getMessage());

            return;
        }

        $this->reset('body', 'replyTo');
        $this->resetErrorBag();
    }

    public function startReply(string $uuid): void
    {
        $this->actor();
        $this->replyTo = $uuid;
        $this->resetErrorBag();
    }

    public function cancelReply(): void
    {
        $this->replyTo = null;
    }

    public function startEdit(string $uuid): void
    {
        $comment = $this->find($uuid);
        $this->authorize('update', $comment);

        $this->editing = (string) $comment->uuid;
        $this->editBody = (string) $comment->body;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->reset('editing', 'editBody');
    }

    public function saveEdit(CommentService $comments): void
    {
        $actor = $this->actor();
        $comment = $this->find($this->editing);
        $this->authorize('update', $comment);

        try {
            $comments->editOwn($actor, $comment, $this->editBody);
        } catch (DomainValidationException $e) {
            $this->addError('editBody', (string) collect($e->fields())->first());

            return;
        }

        $this->reset('editing', 'editBody');
    }

    public function delete(string $uuid, CommentService $comments): void
    {
        $actor = $this->actor();
        $comment = $this->find($uuid);
        $this->authorize('delete', $comment);

        $comments->deleteOwn($actor, $comment);
    }

    public function render(): View
    {
        $comments = null;
        $failed = false;

        try {
            $comments = app(CommentService::class)->visibleThread($this->entityType, $this->entityUuid, (int) config('shop.pagination.default'));
        } catch (Throwable $e) {
            report($e);
            $failed = true;
        }

        return view('comment.thread', ['comments' => $comments, 'failed' => $failed]);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function find(string $uuid): Comment
    {
        return Comment::query()
            ->where('entity_type', $this->entityType)
            ->where('entity_uuid', $this->entityUuid)
            ->where('uuid', $uuid)
            ->firstOr(fn () => abort(404));
    }
}
