@extends('blog.web.layouts.app')

@section('title', $post->meta_title)
@section('description', $post->meta_description)

@push('meta')
    <!-- SEO Meta Tags -->
    <meta name="keywords" content="{{ is_array($post->meta_keywords) ? implode(', ', $post->meta_keywords) : $post->meta_keywords }}">
    <meta name="author" content="{{ $post->user->name }}">
    <link rel="canonical" href="{{ $post->canonical_url ?: url()->current() }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $post->og_title }}">
    <meta property="og:description" content="{{ $post->og_description }}">
    <meta property="og:type" content="{{ $post->og_type }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($post->og_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->og_image) }}">
    @elseif($post->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->featured_image) }}">
    @endif
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->og_title }}">
    <meta name="twitter:description" content="{{ $post->og_description }}">
    @if($post->og_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $post->og_image) }}">
    @elseif($post->featured_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $post->featured_image) }}">
    @endif
@endpush

@section('content')
<article class="max-w-4xl mx-auto px-4 py-8">
    <!-- 게시물 헤더 -->
    <header class="mb-8">
        <!-- 카테고리 -->
        @if($post->category)
            <div class="mb-4">
                <a href="{{ route('blog.categories.show', $post->category->slug) }}" 
                   class="inline-block bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full hover:bg-blue-200 transition-colors">
                    {{ $post->category->name }}
                </a>
            </div>
        @endif

        <!-- 제목 -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4 leading-tight">
            {{ $post->title }}
        </h1>

        <!-- 메타 정보 -->
        <div class="flex flex-wrap items-center text-gray-600 text-sm space-x-4 mb-6">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>{{ $post->user->name }}</span>
            </div>
            
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <time datetime="{{ $post->published_at->toISOString() }}">
                    {{ $post->published_at->format('Y년 m월 d일') }}
                </time>
            </div>
            
            @if($post->reading_time)
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ $post->reading_time }}분 읽기</span>
                </div>
            @endif
            
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>{{ number_format($post->views_count) }}회 조회</span>
            </div>
        </div>

        <!-- 태그 -->
        @if($post->tags->count() > 0)
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach($post->tags as $tag)
                    <a href="{{ route('blog.posts.by-tag', $tag->slug) }}" 
                       class="inline-block bg-gray-100 text-gray-700 text-xs font-medium px-2 py-1 rounded hover:bg-gray-200 transition-colors">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- 썸네일 이미지 -->
        @if($post->featured_image)
            <div class="mb-8">
                <img src="{{ asset('storage/' . $post->featured_image) }}" 
                     alt="{{ $post->title }}" 
                     class="w-full h-64 md:h-96 object-cover rounded-lg shadow-lg">
            </div>
        @endif
    </header>

    <!-- 게시물 내용 -->
    <div class="prose prose-lg max-w-none mb-8">
        <!-- 마크다운으로 변환된 HTML 콘텐츠 출력 -->
        {!! $post->getRenderedContent() !!}
    </div>

    <!-- 게시물 액션 -->
    <div class="border-t border-gray-200 pt-6 mb-8">
        <div class="flex items-center justify-between">
            <!-- 좋아요 버튼 -->
            @auth
                <form action="{{ route('blog.posts.like', $post) }}" method="POST" class="inline" onsubmit="trackLikeEvent()">
                    @csrf
                    <button type="submit" class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors {{ $post->isLikedByCurrentUser() ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        @if($post->isLikedByCurrentUser())
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        @endif
                        <span>{{ number_format($post->likes_count) }}</span>
                    </button>
                </form>
            @else
                <div class="flex items-center space-x-2 text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span>{{ number_format($post->likes_count) }}</span>
                </div>
            @endauth

            <!-- 공유 버튼 -->
            <div class="flex items-center space-x-2">
                <button onclick="sharePost()" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    <span>공유</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 관련 게시물 -->
    @if(isset($relatedPosts) && $relatedPosts->count() > 0)
        <section class="border-t border-gray-200 pt-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">관련 게시물</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($relatedPosts as $relatedPost)
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        @if($relatedPost->featured_image)
                            <img src="{{ asset('storage/' . $relatedPost->featured_image) }}" 
                                 alt="{{ $relatedPost->title }}" 
                                 class="w-full h-48 object-cover">
                        @endif
                        <div class="p-4">
                            <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="{{ route('blog.posts.show', $relatedPost->slug) }}" class="hover:text-blue-600">
                                    {{ $relatedPost->title }}
                                </a>
                            </h4>
                            <p class="text-gray-600 text-sm line-clamp-3">{{ $relatedPost->excerpt }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <!-- 댓글 섹션 -->
    @if($post->allow_comments)
        <section class="border-t border-gray-200 pt-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">
                댓글 <span class="text-gray-500">({{ $post->comments_count }})</span>
            </h3>

            <!-- 댓글 작성 폼 -->
            @auth
                <form action="{{ route('blog.posts.comments.store', $post) }}" method="POST" class="mb-8">
                    @csrf
                    <div class="mb-4">
                        <textarea name="content" rows="4" 
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                  placeholder="댓글을 작성해주세요..." required></textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        댓글 작성
                    </button>
                </form>
            @else
                <div class="mb-8 p-4 bg-gray-100 rounded-lg">
                    <p class="text-gray-600">
                        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">로그인</a>하시면 댓글을 작성할 수 있습니다.
                    </p>
                </div>
            @endauth

            <!-- 댓글 목록 -->
            @if(isset($comments) && $comments->count() > 0)
                <div class="space-y-6">
                    @foreach($comments as $comment)
                        <div class="border-l-4 border-gray-200 pl-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">{{ $comment->user->name }}</span>
                                    <time class="text-gray-500 text-sm">{{ $comment->created_at->diffForHumans() }}</time>
                                </div>
                                @auth
                                    @if($comment->user_id === auth()->id())
                                        <form action="{{ route('blog.posts.comments.destroy', [$post, $comment]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm" 
                                                    onclick="return confirm('댓글을 삭제하시겠습니까?')">
                                                삭제
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                            <p class="text-gray-700">{{ $comment->content }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">아직 댓글이 없습니다. 첫 번째 댓글을 작성해보세요!</p>
            @endif
        </section>
    @endif
</article>

<!-- Analytics & 공유 기능 JavaScript -->
<script>
// Google Analytics 이벤트 추적 함수들
function trackLikeEvent() {
    const postTitle = '{{ $post->title }}';
    
    // Google Analytics 이벤트 추적
    if (window.trackLike) {
        window.trackLike(postTitle);
    }
    
    // Google Tag Manager 이벤트 추적
    if (window.gtmTrackPostInteraction) {
        window.gtmTrackPostInteraction('like', postTitle);
    }
}

function trackCommentEvent() {
    const postTitle = '{{ $post->title }}';
    
    // Google Analytics 이벤트 추적
    if (window.trackComment) {
        window.trackComment(postTitle);
    }
    
    // Google Tag Manager 이벤트 추적
    if (window.gtmTrackPostInteraction) {
        window.gtmTrackPostInteraction('comment', postTitle);
    }
}

function sharePost() {
    const postTitle = '{{ $post->title }}';
    
    if (navigator.share) {
        navigator.share({
            title: postTitle,
            text: '{{ $post->excerpt }}',
            url: window.location.href
        }).then(() => {
            // 공유 성공 시 Analytics 이벤트 추적
            trackShareEvent('native_share');
        });
    } else {
        // 브라우저가 Web Share API를 지원하지 않는 경우 URL 복사
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('링크가 클립보드에 복사되었습니다!');
            // 클립보드 복사 시 Analytics 이벤트 추적
            trackShareEvent('clipboard');
        });
    }
}

function trackShareEvent(method) {
    const postTitle = '{{ $post->title }}';
    
    // Google Analytics 이벤트 추적
    if (window.trackShare) {
        window.trackShare(postTitle, method);
    }
    
    // Google Tag Manager 이벤트 추적
    if (window.gtmTrackPostInteraction) {
        window.gtmTrackPostInteraction('share', postTitle);
    }
}

// 페이지 로드 시 게시물 조회 이벤트 추적
document.addEventListener('DOMContentLoaded', function() {
    // 게시물 조회 이벤트 (Google Analytics)
    if (window.gtag) {
        gtag('event', 'view_item', {
            'item_id': '{{ $post->id }}',
            'item_name': '{{ $post->title }}',
            'item_category': '{{ $post->category->name ?? "uncategorized" }}',
            'content_type': 'blog_post'
        });
    }
    
    // 댓글 폼 제출 시 이벤트 추적
    const commentForm = document.querySelector('form[action*="comments"]');
    if (commentForm) {
        commentForm.addEventListener('submit', function() {
            trackCommentEvent();
        });
    }
    
    // 관련 게시물 클릭 추적
    document.querySelectorAll('a[href*="posts"]').forEach(function(link) {
        link.addEventListener('click', function() {
            const linkText = this.textContent.trim();
            if (window.gtag) {
                gtag('event', 'click', {
                    'event_category': 'related_posts',
                    'event_label': linkText
                });
            }
        });
    });
});

// 읽기 진행률 추적
let readingProgress = 0;
let readingMilestones = [25, 50, 75, 100];
let trackedMilestones = [];

window.addEventListener('scroll', function() {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    
    readingMilestones.forEach(function(milestone) {
        if (scrolled >= milestone && !trackedMilestones.includes(milestone)) {
            trackedMilestones.push(milestone);
            
            // Google Analytics 읽기 진행률 추적
            if (window.gtag) {
                gtag('event', 'reading_progress', {
                    'event_category': 'engagement',
                    'event_label': '{{ $post->title }}',
                    'value': milestone
                });
            }
            
            // Google Tag Manager 읽기 진행률 추적
            if (window.gtmTrack) {
                gtmTrack('reading_progress', {
                    'post_title': '{{ $post->title }}',
                    'progress_percentage': milestone
                });
            }
        }
    });
});
</script>

<!-- 스타일링 -->
<style>
.prose {
    color: #374151;
    line-height: 1.75;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    color: #111827;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h1 { font-size: 2.25rem; }
.prose h2 { font-size: 1.875rem; }
.prose h3 { font-size: 1.5rem; }
.prose h4 { font-size: 1.25rem; }

.prose p {
    margin-bottom: 1.25rem;
}

.prose ul,
.prose ol {
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
}

.prose li {
    margin-bottom: 0.5rem;
}

.prose blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6b7280;
}

.prose code {
    background-color: #f3f4f6;
    color: #ef4444;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.prose pre {
    background-color: #1f2937;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.prose pre code {
    background-color: transparent;
    color: inherit;
    padding: 0;
}

.prose a {
    color: #2563eb;
    text-decoration: underline;
}

.prose a:hover {
    color: #1d4ed8;
}

.prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5rem 0;
}

.prose table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
}

.prose th,
.prose td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
    text-align: left;
}

.prose th {
    background-color: #f9fafb;
    font-weight: 600;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection