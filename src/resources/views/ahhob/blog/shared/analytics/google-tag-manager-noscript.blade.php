@php
    $analyticsService = app(\App\Services\Ahhob\Blog\Shared\AnalyticsService::class);
@endphp

@if($analyticsService->isGoogleTagManagerEnabled())
    @php
        $gtmId = $analyticsService->getGoogleTagManagerId();
    @endphp
    
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif