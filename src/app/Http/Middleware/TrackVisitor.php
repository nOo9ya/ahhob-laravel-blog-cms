<?php

namespace App\Http\Middleware;

use App\Models\Blog\PostView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 게시물 상세 페이지에서만 추적
        if ($request->route('post') && $response->getStatusCode() === 200) {
            $this->trackPostView($request);
        }

        return $response;
    }

    private function trackPostView(Request $request): void
    {
        $post = $request->route('post');
        $ipAddress = $request->ip();
        $userId = Auth::id();

        // 동일 IP 또는 사용자의 중복 조회 방지 (24시간)
        $existingView = PostView::where('post_id', $post->id)
            ->where(function ($query) use ($ipAddress, $userId) {
                $query->where('ip_address', $ipAddress);
                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if (!$existingView) {
            PostView::create([
                'post_id' => $post->id,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'device_type' => $this->getDeviceType($request->userAgent()),
                'browser' => $this->getBrowser($request->userAgent()),
            ]);

            // 게시물 조회수 증가
            $post->increment('views_count');
        }
    }

    private function getDeviceType(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return 'desktop';
    }

    private function getBrowser(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        $browsers = [
            'Chrome' => '/Chrome\/[\d.]+/',
            'Firefox' => '/Firefox\/[\d.]+/',
            'Safari' => '/Safari\/[\d.]+/',
            'Edge' => '/Edge\/[\d.]+/',
            'Opera' => '/Opera\/[\d.]+/',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'unknown';
    }
}
