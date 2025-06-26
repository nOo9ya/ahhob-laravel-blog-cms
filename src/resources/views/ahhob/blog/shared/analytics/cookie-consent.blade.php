@php
    $analyticsService = app(\App\Services\Ahhob\Blog\Shared\AnalyticsService::class);
@endphp

@if($analyticsService->requiresCookieConsent() && $analyticsService->isAnyAnalyticsEnabled())
    @php
        $consentCookieName = config('ahhob_blog.analytics.consent_cookie_name', 'analytics_consent');
        $consentDuration = config('ahhob_blog.analytics.consent_cookie_duration', 365);
    @endphp

    <div id="cookie-consent-banner" 
         class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-4 shadow-lg z-50 transform translate-y-full transition-transform duration-300"
         style="display: none;">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
            <div class="flex-1">
                <h3 class="font-semibold mb-2">쿠키 사용 동의</h3>
                <p class="text-gray-300 text-sm">
                    이 사이트는 사용자 경험 개선과 웹사이트 분석을 위해 쿠키를 사용합니다. 
                    계속 이용하시면 쿠키 사용에 동의하시는 것으로 간주됩니다.
                    <a href="{{ route('pages.show', 'privacy-policy') }}" 
                       class="text-blue-300 hover:text-blue-200 underline">자세히 보기</a>
                </p>
            </div>
            
            <div class="flex space-x-3">
                <button id="cookie-consent-decline" 
                        class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors">
                    거부
                </button>
                <button id="cookie-consent-accept" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    동의
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const CONSENT_COOKIE_NAME = '{{ $consentCookieName }}';
            const CONSENT_DURATION = {{ $consentDuration }};
            const banner = document.getElementById('cookie-consent-banner');
            const acceptBtn = document.getElementById('cookie-consent-accept');
            const declineBtn = document.getElementById('cookie-consent-decline');

            // 쿠키 설정/읽기 헬퍼 함수
            function setCookie(name, value, days) {
                const expires = new Date();
                expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
                document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Strict';
            }

            function getCookie(name) {
                const nameEQ = name + '=';
                const ca = document.cookie.split(';');
                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }

            // 쿠키 동의 상태 확인
            function hasConsent() {
                const consent = getCookie(CONSENT_COOKIE_NAME);
                return consent === 'accepted';
            }

            function hasDeclined() {
                const consent = getCookie(CONSENT_COOKIE_NAME);
                return consent === 'declined';
            }

            // 분석 스크립트 로드/활성화
            function enableAnalytics() {
                // Google Analytics 활성화
                @if($analyticsService->isGoogleAnalyticsEnabled())
                if (window.gtag) {
                    gtag('consent', 'update', {
                        'analytics_storage': 'granted',
                        'ad_storage': 'granted'
                    });
                }
                @endif

                // Google Tag Manager 활성화
                @if($analyticsService->isGoogleTagManagerEnabled())
                if (window.dataLayer) {
                    window.dataLayer.push({
                        'event': 'consent_granted',
                        'consent_type': 'analytics'
                    });
                }
                @endif

                // 사용자 정의 이벤트 발생
                window.dispatchEvent(new CustomEvent('analyticsEnabled'));
                console.log('[Analytics] 사용자가 쿠키 사용에 동의했습니다.');
            }

            // 분석 스크립트 비활성화
            function disableAnalytics() {
                // Google Analytics 비활성화
                @if($analyticsService->isGoogleAnalyticsEnabled())
                if (window.gtag) {
                    gtag('consent', 'update', {
                        'analytics_storage': 'denied',
                        'ad_storage': 'denied'
                    });
                }
                @endif

                // Google Tag Manager 비활성화
                @if($analyticsService->isGoogleTagManagerEnabled())
                if (window.dataLayer) {
                    window.dataLayer.push({
                        'event': 'consent_denied',
                        'consent_type': 'analytics'
                    });
                }
                @endif

                // 사용자 정의 이벤트 발생
                window.dispatchEvent(new CustomEvent('analyticsDisabled'));
                console.log('[Analytics] 사용자가 쿠키 사용을 거부했습니다.');
            }

            // 배너 표시
            function showBanner() {
                banner.style.display = 'block';
                setTimeout(() => {
                    banner.classList.remove('translate-y-full');
                }, 100);
            }

            // 배너 숨김
            function hideBanner() {
                banner.classList.add('translate-y-full');
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 300);
            }

            // 동의 버튼 클릭
            acceptBtn.addEventListener('click', function() {
                setCookie(CONSENT_COOKIE_NAME, 'accepted', CONSENT_DURATION);
                hideBanner();
                enableAnalytics();
                
                // GA4 이벤트 추적
                if (window.gtag) {
                    gtag('event', 'cookie_consent', {
                        'event_category': 'user_consent',
                        'event_label': 'accepted'
                    });
                }
                
                // GTM 이벤트 추적
                if (window.gtmTrack) {
                    gtmTrack('cookie_consent', {
                        'consent_action': 'accepted'
                    });
                }
            });

            // 거부 버튼 클릭
            declineBtn.addEventListener('click', function() {
                setCookie(CONSENT_COOKIE_NAME, 'declined', CONSENT_DURATION);
                hideBanner();
                disableAnalytics();
                
                // 기본적인 이벤트 추적 (쿠키 없이)
                console.log('[Analytics] 쿠키 사용 거부됨');
            });

            // 초기화
            function init() {
                if (hasConsent()) {
                    enableAnalytics();
                } else if (hasDeclined()) {
                    disableAnalytics();
                } else {
                    // 동의/거부 기록이 없으면 배너 표시
                    setTimeout(showBanner, 1000); // 1초 후 표시
                }
            }

            // 페이지 로드 완료 후 초기화
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // 전역 함수 노출 (개발자가 프로그래밍 방식으로 접근 가능)
            window.cookieConsent = {
                hasConsent: hasConsent,
                hasDeclined: hasDeclined,
                accept: function() {
                    acceptBtn.click();
                },
                decline: function() {
                    declineBtn.click();
                },
                revoke: function() {
                    setCookie(CONSENT_COOKIE_NAME, '', -1);
                    location.reload();
                }
            };
        })();
    </script>
@endif