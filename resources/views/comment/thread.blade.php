<div class="card mt-4" id="comments">
    <div class="card-header"><h3 class="card-title">Comments</h3></div>
    <div class="card-body">
        @if ($failed)
            <div class="alert alert-warning mb-0">Comments are temporarily unavailable.</div>
        @else
            @auth
                <form wire:submit="post" class="mb-4">
                    @if ($replyTo)
                        <div class="small text-muted mb-1">
                            Replying to a comment
                            <button type="button" wire:click="cancelReply" class="btn btn-link btn-sm p-0">Cancel</button>
                        </div>
                    @endif
                    <textarea wire:model="body" class="form-control @error('body') is-invalid @enderror" rows="3" maxlength="{{ config('shop.comment_max_length') }}" placeholder="Write a comment"></textarea>
                    @error('body') <div class="text-danger small">{{ $message }}</div> @enderror
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Post comment</button>
                </form>
            @else
                <p class="text-muted">
                    <a href="{{ route('login') }}">Log in</a> to join the discussion.
                </p>
            @endauth

            @forelse ($comments as $comment)
                @include('comment.partials.item', ['comment' => $comment, 'isReply' => false])
            @empty
                <p class="text-muted mb-0">No comments yet.</p>
            @endforelse
        @endif
    </div>
    @if (! $failed && $comments->hasPages())
        <div class="card-footer">{{ $comments->links() }}</div>
    @endif
</div>
