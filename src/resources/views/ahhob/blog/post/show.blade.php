<form action="{{ route('blog.posts.like', $post) }}" method="POST">
    @csrf
    <button type="submit" class="like-button {{ $post->isLikedByCurrentUser() ? 'active' : '' }}">
        @if($post->isLikedByCurrentUser())
            <i class="fas fa-heart"></i> 좋아요 취소
        @else
            <i class="far fa-heart"></i> 좋아요
        @endif
        <span>{{ $post->likes_count }}</span>
    </button>
</form>
