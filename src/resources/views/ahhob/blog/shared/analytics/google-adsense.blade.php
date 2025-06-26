@php
    $analyticsService = app(\App\Services\Ahhob\Blog\Shared\AnalyticsService::class);
@endphp

@if($analyticsService->isGoogleAdSenseEnabled())
    @php
        $clientId = $analyticsService->getGoogleAdSenseClientId();
    @endphp
    
    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $clientId }}" crossorigin="anonymous"></script>
@endif

@props(['slot' => 'auto', 'style' => 'display:block', 'format' => 'auto', 'fullWidthResponsive' => true])

@if($analyticsService->isGoogleAdSenseEnabled())
    <ins class="adsbygoogle"
         style="{{ $style }}"
         data-ad-client="{{ $clientId }}"
         data-ad-slot="{{ $slot }}"
         data-ad-format="{{ $format }}"
         @if($fullWidthResponsive)
         data-full-width-responsive="true"
         @endif></ins>
    
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
@endif