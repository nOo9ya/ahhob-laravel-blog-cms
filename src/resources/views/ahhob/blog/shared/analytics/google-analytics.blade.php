@php
    $analyticsService = app(\App\Services\Ahhob\Blog\Shared\AnalyticsService::class);
@endphp

@if($analyticsService->isGoogleAnalyticsEnabled())
    @php
        $measurementId = $analyticsService->getGoogleAnalyticsMeasurementId();
    @endphp
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ $measurementId }}', {
            // 개인정보 보호 설정
            anonymize_ip: true,
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
            
            // 페이지 로드 성능 측정
            send_page_view: true,
            
            // 사용자 정의 매개변수
            custom_map: {
                'dimension1': 'user_type',
                'dimension2': 'content_category'
            }
        });

        // 사용자 타입 설정 (로그인 여부)
        @auth
            gtag('set', {'user_type': 'logged_in'});
        @else
            gtag('set', {'user_type': 'anonymous'});
        @endauth

        // 현재 페이지 정보 설정
        @if(isset($page))
            gtag('set', {'content_category': 'page'});
        @elseif(isset($post))
            gtag('set', {'content_category': '{{ $post->category->name ?? "uncategorized" }}'});
        @endif

        // 스크롤 추적 (90% 스크롤 시)
        let scrollTracked = false;
        window.addEventListener('scroll', function() {
            if (!scrollTracked) {
                const scrollPercentage = (window.scrollY / (document.body.offsetHeight - window.innerHeight)) * 100;
                if (scrollPercentage >= 90) {
                    gtag('event', 'scroll', {
                        event_category: 'engagement',
                        event_label: '90_percent',
                        value: 90
                    });
                    scrollTracked = true;
                }
            }
        });

        // 외부 링크 클릭 추적
        document.addEventListener('click', function(event) {
            const link = event.target.closest('a');
            if (link && link.hostname !== window.location.hostname) {
                gtag('event', 'click', {
                    event_category: 'outbound',
                    event_label: link.href,
                    transport_type: 'beacon'
                });
            }
        });

        // 파일 다운로드 추적
        document.addEventListener('click', function(event) {
            const link = event.target.closest('a');
            if (link && link.href) {
                const fileExtensions = /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|jpg|jpeg|png|gif)$/i;
                if (fileExtensions.test(link.href)) {
                    const fileName = link.href.split('/').pop();
                    gtag('event', 'file_download', {
                        event_category: 'engagement',
                        event_label: fileName,
                        value: 1
                    });
                }
            }
        });

        // 검색 추적 함수 (검색 페이지에서 사용)
        window.trackSearch = function(searchTerm) {
            gtag('event', 'search', {
                search_term: searchTerm,
                event_category: 'engagement'
            });
        };

        // 게시물 좋아요 추적 함수
        window.trackLike = function(postTitle) {
            gtag('event', 'like', {
                event_category: 'engagement',
                event_label: postTitle,
                value: 1
            });
        };

        // 댓글 작성 추적 함수
        window.trackComment = function(postTitle) {
            gtag('event', 'comment', {
                event_category: 'engagement',
                event_label: postTitle,
                value: 1
            });
        };

        // 공유 추적 함수
        window.trackShare = function(contentTitle, method) {
            gtag('event', 'share', {
                event_category: 'engagement',
                event_label: contentTitle,
                method: method || 'unknown',
                value: 1
            });
        };

        // 페이지 이탈 시간 추적
        window.addEventListener('beforeunload', function() {
            if (window.startTime) {
                const timeOnPage = Date.now() - window.startTime;
                gtag('event', 'timing_complete', {
                    name: 'time_on_page',
                    value: Math.round(timeOnPage / 1000), // 초 단위
                    event_category: 'engagement'
                });
            }
        });

        // 페이지 로드 시간 기록
        window.startTime = Date.now();
    </script>
@endif