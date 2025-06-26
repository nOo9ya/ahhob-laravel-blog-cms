@extends('blog.web.layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('description', $page->meta_description)

@push('meta')
    <!-- SEO Meta Tags -->
    @if($page->keywords)
        <meta name="keywords" content="{{ $page->keywords }}">
    @endif
    <meta name="author" content="{{ $page->user->name }}">
    @if($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $page->og_title ?: $page->title }}">
    <meta property="og:description" content="{{ $page->og_description ?: $page->meta_description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($page->og_image)
        <meta property="og:image" content="{{ $page->og_image }}">
    @endif
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page->og_title ?: $page->title }}">
    <meta name="twitter:description" content="{{ $page->og_description ?: $page->meta_description }}">
    @if($page->og_image)
        <meta name="twitter:image" content="{{ $page->og_image }}">
    @endif
@endpush

@section('content')
<article class="max-w-4xl mx-auto px-4 py-8">
    <!-- 페이지 헤더 -->
    <header class="mb-8">
        <!-- 페이지 제목 -->
        <h1 class="text-4xl font-bold text-gray-900 mb-6 leading-tight">
            {{ $page->title }}
        </h1>

        <!-- 메타 정보 -->
        <div class="flex flex-wrap items-center text-gray-600 text-sm space-x-4 mb-6 border-b border-gray-200 pb-4">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>{{ $page->user->name }}</span>
            </div>
            
            @if($page->published_at)
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <time datetime="{{ $page->published_at->toISOString() }}">
                        {{ $page->published_at->format('Y년 m월 d일') }}
                    </time>
                </div>
            @endif

            @if($page->updated_at && $page->updated_at->ne($page->created_at))
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>{{ $page->updated_at->format('Y년 m월 d일') }} 업데이트</span>
                </div>
            @endif
        </div>
    </header>

    <!-- 페이지 내용 -->
    <div class="prose prose-lg max-w-none mb-8">
        <!-- 마크다운으로 변환된 HTML 콘텐츠 출력 -->
        {!! $page->getRenderedContent() !!}
    </div>

    <!-- 공유 섹션 -->
    <div class="border-t border-gray-200 pt-6 mb-8">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                이 페이지가 도움이 되었나요?
            </div>
            
            <!-- 공유 버튼 -->
            <div class="flex items-center space-x-2">
                <button onclick="sharePage()" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    <span>공유</span>
                </button>
                
                <button onclick="printPage()" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span>인쇄</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 네비게이션 -->
    <nav class="border-t border-gray-200 pt-6">
        <div class="flex justify-between items-center">
            <a href="{{ route('home') }}" class="flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                홈으로 돌아가기
            </a>
            
            <div class="text-sm text-gray-500">
                <a href="{{ route('contact') }}" class="hover:text-gray-700">문의하기</a>
            </div>
        </div>
    </nav>
</article>

<!-- JavaScript 기능들 -->
<script>
function sharePage() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $page->title }}',
            text: '{{ $page->meta_description ?: "유용한 정보를 확인해보세요" }}',
            url: window.location.href
        });
    } else {
        // 브라우저가 Web Share API를 지원하지 않는 경우 URL 복사
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('링크가 클립보드에 복사되었습니다!');
        }).catch(function() {
            // 클립보드 API도 지원하지 않는 경우
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('링크가 클립보드에 복사되었습니다!');
        });
    }
}

function printPage() {
    window.print();
}
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

/* 인쇄 스타일 */
@media print {
    .prose {
        font-size: 12pt;
        line-height: 1.4;
    }
    
    .prose h1 { font-size: 18pt; }
    .prose h2 { font-size: 16pt; }
    .prose h3 { font-size: 14pt; }
    .prose h4 { font-size: 12pt; }
    
    /* 불필요한 요소들 숨기기 */
    nav,
    .border-t,
    button {
        display: none !important;
    }
}
</style>
@endsection