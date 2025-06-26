<?php

namespace App\Services\Ahhob\Blog\Shared;

class AnalyticsService
{
    /**
     * Google Analytics가 활성화되어 있는지 확인
     */
    public function isGoogleAnalyticsEnabled(): bool
    {
        return config('ahhob_blog.analytics.google_analytics.enabled', false);
    }

    /**
     * Google Tag Manager가 활성화되어 있는지 확인
     */
    public function isGoogleTagManagerEnabled(): bool
    {
        return config('ahhob_blog.analytics.google_tag_manager.enabled', false);
    }

    /**
     * Google AdSense가 활성화되어 있는지 확인
     */
    public function isGoogleAdSenseEnabled(): bool
    {
        return config('ahhob_blog.analytics.google_adsense.enabled', false);
    }

    /**
     * Google Analytics Measurement ID 반환
     */
    public function getGoogleAnalyticsMeasurementId(): ?string
    {
        return config('ahhob_blog.analytics.google_analytics.measurement_id');
    }

    /**
     * Google Tag Manager Container ID 반환
     */
    public function getGoogleTagManagerId(): ?string
    {
        return config('ahhob_blog.analytics.google_tag_manager.container_id');
    }

    /**
     * Google AdSense Client ID 반환
     */
    public function getGoogleAdSenseClientId(): ?string
    {
        return config('ahhob_blog.analytics.google_adsense.client_id');
    }

    /**
     * Google Analytics gtag 이벤트 데이터 생성
     */
    public function createGtagEvent(string $action, array $parameters = []): array
    {
        if (!$this->isGoogleAnalyticsEnabled()) {
            return [];
        }

        return array_merge([
            'event_category' => 'engagement',
            'event_label' => url()->current(),
        ], $parameters, [
            'action' => $action,
        ]);
    }

    /**
     * 페이지 뷰 이벤트 생성
     */
    public function createPageViewEvent(string $pageTitle, string $pageLocation = null): array
    {
        return $this->createGtagEvent('page_view', [
            'page_title' => $pageTitle,
            'page_location' => $pageLocation ?: url()->current(),
        ]);
    }

    /**
     * 게시물 읽기 이벤트 생성
     */
    public function createPostViewEvent(string $postTitle, string $postCategory = null): array
    {
        return $this->createGtagEvent('view_item', [
            'item_id' => request()->route('post'),
            'item_name' => $postTitle,
            'item_category' => $postCategory,
            'content_type' => 'blog_post',
        ]);
    }

    /**
     * 검색 이벤트 생성
     */
    public function createSearchEvent(string $searchTerm): array
    {
        return $this->createGtagEvent('search', [
            'search_term' => $searchTerm,
        ]);
    }

    /**
     * 게시물 좋아요 이벤트 생성
     */
    public function createLikeEvent(string $postTitle): array
    {
        return $this->createGtagEvent('like', [
            'item_name' => $postTitle,
            'content_type' => 'blog_post',
        ]);
    }

    /**
     * 댓글 작성 이벤트 생성
     */
    public function createCommentEvent(string $postTitle): array
    {
        return $this->createGtagEvent('comment', [
            'item_name' => $postTitle,
            'content_type' => 'blog_post',
        ]);
    }

    /**
     * 공유 이벤트 생성
     */
    public function createShareEvent(string $contentTitle, string $method = 'unknown'): array
    {
        return $this->createGtagEvent('share', [
            'content_type' => 'blog_post',
            'item_id' => request()->route('post'),
            'item_name' => $contentTitle,
            'method' => $method,
        ]);
    }

    /**
     * GTM DataLayer 이벤트 생성
     */
    public function createGTMEvent(string $event, array $data = []): array
    {
        if (!$this->isGoogleTagManagerEnabled()) {
            return [];
        }

        return array_merge([
            'event' => $event,
            'timestamp' => now()->toISOString(),
            'page_location' => url()->current(),
            'page_title' => request()->route()->getName() ?? 'unknown',
        ], $data);
    }

    /**
     * GTM 게시물 상호작용 이벤트 생성
     */
    public function createGTMPostInteractionEvent(string $action, string $postTitle): array
    {
        return $this->createGTMEvent('post_interaction', [
            'interaction_type' => $action,
            'post_title' => $postTitle,
            'content_type' => 'blog_post',
        ]);
    }

    /**
     * GTM 네비게이션 이벤트 생성
     */
    public function createGTMNavigationEvent(string $navigationItem): array
    {
        return $this->createGTMEvent('navigation_click', [
            'navigation_item' => $navigationItem,
        ]);
    }

    /**
     * GTM 외부 링크 클릭 이벤트 생성
     */
    public function createGTMExternalLinkEvent(string $url, string $linkText = ''): array
    {
        return $this->createGTMEvent('external_link_click', [
            'external_url' => $url,
            'link_text' => $linkText,
        ]);
    }

    /**
     * GTM 파일 다운로드 이벤트 생성
     */
    public function createGTMFileDownloadEvent(string $fileName, string $fileType): array
    {
        return $this->createGTMEvent('file_download', [
            'file_name' => $fileName,
            'file_type' => $fileType,
        ]);
    }

    /**
     * GTM 검색 이벤트 생성
     */
    public function createGTMSearchEvent(string $searchTerm, int $resultCount = 0): array
    {
        return $this->createGTMEvent('site_search', [
            'search_term' => $searchTerm,
            'search_results' => $resultCount,
        ]);
    }

    /**
     * GTM 폼 제출 이벤트 생성
     */
    public function createGTMFormSubmitEvent(string $formName): array
    {
        return $this->createGTMEvent('form_submit', [
            'form_name' => $formName,
        ]);
    }

    /**
     * GTM 스크롤 깊이 이벤트 생성
     */
    public function createGTMScrollDepthEvent(int $scrollDepth): array
    {
        return $this->createGTMEvent('scroll_depth', [
            'scroll_depth' => $scrollDepth,
        ]);
    }

    /**
     * GTM 페이지 체류 시간 이벤트 생성
     */
    public function createGTMTimeOnPageEvent(int $timeThreshold): array
    {
        return $this->createGTMEvent('time_on_page', [
            'time_threshold' => $timeThreshold,
        ]);
    }

    /**
     * GTM 비디오 상호작용 이벤트 생성
     */
    public function createGTMVideoInteractionEvent(string $action, string $videoTitle = ''): array
    {
        return $this->createGTMEvent('video_interaction', [
            'video_action' => $action,
            'video_title' => $videoTitle,
        ]);
    }

    /**
     * GTM 에러 이벤트 생성
     */
    public function createGTMErrorEvent(string $errorType, string $errorMessage = '', string $errorSource = ''): array
    {
        return $this->createGTMEvent('error_tracking', [
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'error_source' => $errorSource,
        ]);
    }

    /**
     * 모든 애널리틱스가 활성화되어 있는지 확인
     */
    public function isAnyAnalyticsEnabled(): bool
    {
        return $this->isGoogleAnalyticsEnabled() || 
               $this->isGoogleTagManagerEnabled() || 
               $this->isGoogleAdSenseEnabled();
    }

    /**
     * 활성화된 애널리틱스 서비스 목록 반환
     */
    public function getEnabledServices(): array
    {
        $services = [];
        
        if ($this->isGoogleAnalyticsEnabled()) {
            $services[] = 'google_analytics';
        }
        
        if ($this->isGoogleTagManagerEnabled()) {
            $services[] = 'google_tag_manager';
        }
        
        if ($this->isGoogleAdSenseEnabled()) {
            $services[] = 'google_adsense';
        }
        
        return $services;
    }

    /**
     * 개인정보 보호 모드 활성화 여부
     */
    public function isPrivacyModeEnabled(): bool
    {
        return config('ahhob_blog.analytics.privacy_mode', true);
    }

    /**
     * 쿠키 동의 필요 여부
     */
    public function requiresCookieConsent(): bool
    {
        return config('ahhob_blog.analytics.require_cookie_consent', true);
    }
}