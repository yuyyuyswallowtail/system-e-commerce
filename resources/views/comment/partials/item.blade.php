<div class="mb-3" wire:key="comment-{{ $comment->uuid }}">
    <strong>{{ $comment->user?->name ?? 'Deleted user' }}</strong>
    <small class="text-muted">{{ $comment->created_at?->diffForHumans() }}</small>

    @if ($editing === $comment->uuid)
        <textarea wire:model="editBody" class="form-control my-2 @error('editBody') is-invalid @enderror" rows="3" maxlength="{{ config('shop.comment_max_length') }}"></textarea>
        @error('editBody') <div class="text-danger small">{{ $message }}</div> @enderror
        <button type="button" wire:click="saveEdit" class="btn btn-sm btn-primary">Save</button>
        <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-link">Cancel</button>
    @else
        <div class="mb-1">@foreach (preg_split('/\R/', (string) $comment->body) as $line){{ $line }}@if (! $loop->last)<br />@endif@endforeach</div>
        <div class="small">
            @auth
                @if (! $isReply)
                    <button type="button" wire:click="startReply('{{ $comment->uuid }}')" class="btn btn-link btn-sm p-0 mr-2">Reply</button>
                @endif
            @endauth
            @can('update', $comment)
                <button type="button" wire:click="startEdit('{{ $comment->uuid }}')" class="btn btn-link btn-sm p-0 mr-2">Edit</button>
            @endcan
            @can('delete', $comment)
                <button type="button" wire:click="delete('{{ $comment->uuid }}')" wire:confirm="Delete this comment?" class="btn btn-link btn-sm p-0 text-danger">Delete</button>
            @endcan
        </div>
    @endif

    @if (! $isReply)
        @foreach ($comment->replies as $reply)
            <div class="ml-4 mt-2 pl-3 border-left">
                @include('comment.partials.item', ['comment' => $reply, 'isReply' => true])
            </div>
        @endforeach
    @endif
</div>
