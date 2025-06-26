@php
    $analyticsService = app(\App\Services\Ahhob\Blog\Shared\AnalyticsService::class);
@endphp

@if($analyticsService->isGoogleTagManagerEnabled())
    @php
        $gtmId = $analyticsService->getGoogleTagManagerId();
    @endphp
    
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
    <!-- End Google Tag Manager -->

    <script>
        // DataLayer 초기화
        window.dataLayer = window.dataLayer || [];

        // 사용자 정보를 DataLayer에 추가
        dataLayer.push({
            'user_type': '{{ auth()->check() ? "logged_in" : "anonymous" }}',
            @auth
            'user_id': '{{ auth()->id() }}',
            @endauth
            @if(isset($post))
            'content_type': 'blog_post',
            'content_category': '{{ $post->category->name ?? "uncategorized" }}',
            'content_id': '{{ $post->id }}',
            'content_title': '{{ $post->title }}',
            @elseif(isset($page))
            'content_type': 'page',
            'content_id': '{{ $page->id }}',
            'content_title': '{{ $page->title }}',
            @endif
        });

        // GTM 이벤트 추적 헬퍼 함수들
        window.gtmTrack = function(event, data) {
            dataLayer.push(Object.assign({
                'event': event,
                'timestamp': new Date().toISOString()
            }, data || {}));
        };

        // 페이지 뷰 추적
        window.gtmTrackPageView = function(pageTitle, pageLocation) {
            gtmTrack('page_view', {
                'page_title': pageTitle,
                'page_location': pageLocation || window.location.href
            });
        };

        // 검색 추적
        window.gtmTrackSearch = function(searchTerm) {
            gtmTrack('search', {
                'search_term': searchTerm
            });
        };

        // 게시물 상호작용 추적
        window.gtmTrackPostInteraction = function(action, postTitle) {
            gtmTrack('post_interaction', {
                'interaction_type': action,
                'post_title': postTitle
            });
        };

        // 폼 제출 추적
        window.gtmTrackFormSubmit = function(formName) {
            gtmTrack('form_submit', {
                'form_name': formName
            });
        };

        // 네비게이션 추적
        window.gtmTrackNavigation = function(navigationItem) {
            gtmTrack('navigation_click', {
                'navigation_item': navigationItem
            });
        };

        // 외부 링크 클릭 추적
        window.gtmTrackExternalLink = function(url, linkText) {
            gtmTrack('external_link_click', {
                'external_url': url,
                'link_text': linkText || ''
            });
        };

        // 파일 다운로드 추적
        window.gtmTrackFileDownload = function(fileName, fileType) {
            gtmTrack('file_download', {
                'file_name': fileName,
                'file_type': fileType
            });
        };

        // 이메일 링크 클릭 추적
        window.gtmTrackEmailClick = function(emailAddress) {
            gtmTrack('email_click', {
                'email_address': emailAddress
            });
        };

        // 전화번호 클릭 추적
        window.gtmTrackPhoneClick = function(phoneNumber) {
            gtmTrack('phone_click', {
                'phone_number': phoneNumber
            });
        };

        // 비디오 상호작용 추적
        window.gtmTrackVideoInteraction = function(action, videoTitle) {
            gtmTrack('video_interaction', {
                'video_action': action,
                'video_title': videoTitle || ''
            });
        };

        // 사이트 내 검색 추적 (개선)
        window.gtmTrackSiteSearch = function(searchTerm, resultCount) {
            gtmTrack('site_search', {
                'search_term': searchTerm,
                'search_results': resultCount || 0
            });
        };

        // 페이지 로드 완료 시 자동 이벤트 추적 설정
        document.addEventListener('DOMContentLoaded', function() {
            // 스크롤 깊이 추적 (개선된 버전)
            let scrollDepthTracked = [];
            let scrollDepths = [25, 50, 75, 100];
            
            window.addEventListener('scroll', function() {
                const scrollPercent = Math.round((window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
                
                scrollDepths.forEach(function(depth) {
                    if (scrollPercent >= depth && !scrollDepthTracked.includes(depth)) {
                        scrollDepthTracked.push(depth);
                        gtmTrack('scroll_depth', {
                            'scroll_depth': depth,
                            'page_title': document.title
                        });
                    }
                });
            });

            // 외부 링크 자동 추적
            document.querySelectorAll('a[href^="http"]:not([href*="' + window.location.hostname + '"])').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    gtmTrackExternalLink(this.href, this.textContent.trim());
                });
            });

            // 파일 다운로드 자동 추적
            document.querySelectorAll('a[href$=".pdf"], a[href$=".doc"], a[href$=".docx"], a[href$=".xls"], a[href$=".xlsx"], a[href$=".zip"], a[href$=".rar"], a[href$=".ppt"], a[href$=".pptx"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const fileName = this.href.split('/').pop();
                    const fileType = fileName.split('.').pop();
                    gtmTrackFileDownload(fileName, fileType);
                });
            });

            // 이메일 링크 자동 추적
            document.querySelectorAll('a[href^="mailto:"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const emailAddress = this.href.replace('mailto:', '');
                    gtmTrackEmailClick(emailAddress);
                });
            });

            // 전화번호 링크 자동 추적
            document.querySelectorAll('a[href^="tel:"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const phoneNumber = this.href.replace('tel:', '');
                    gtmTrackPhoneClick(phoneNumber);
                });
            });

            // 네비게이션 링크 추적
            document.querySelectorAll('nav a, .nav a, [role="navigation"] a').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    gtmTrackNavigation(this.textContent.trim());
                });
            });

            // 폼 제출 자동 추적
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const formName = this.name || this.id || this.action.split('/').pop() || 'unknown_form';
                    gtmTrackFormSubmit(formName);
                });
            });

            // 검색 폼 추적
            document.querySelectorAll('form[action*="search"]').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const searchInput = this.querySelector('input[name="q"], input[name="search"], input[name="query"]');
                    if (searchInput) {
                        gtmTrackSiteSearch(searchInput.value);
                    }
                });
            });

            // 이미지 로드 오류 추적
            document.querySelectorAll('img').forEach(function(img) {
                img.addEventListener('error', function() {
                    gtmTrack('image_error', {
                        'image_src': this.src,
                        'image_alt': this.alt || ''
                    });
                });
            });

            // 페이지 체류 시간 추적
            let timeOnPageTracked = [];
            let timeIntervals = [30, 60, 180, 300]; // 30초, 1분, 3분, 5분
            
            timeIntervals.forEach(function(interval) {
                setTimeout(function() {
                    if (!timeOnPageTracked.includes(interval)) {
                        timeOnPageTracked.push(interval);
                        gtmTrack('time_on_page', {
                            'time_threshold': interval,
                            'page_title': document.title
                        });
                    }
                }, interval * 1000);
            });

            // 페이지 가시성 변경 추적 (탭 전환 등)
            document.addEventListener('visibilitychange', function() {
                gtmTrack('page_visibility', {
                    'visibility_state': document.visibilityState,
                    'page_title': document.title
                });
            });
        });
    </script>
@endif