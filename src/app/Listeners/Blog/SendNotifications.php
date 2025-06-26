<?php

namespace App\Listeners\Blog;

use App\Events\Blog\PostPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * 알림 발송 리스너
 * 
 * 게시물 발행 시 구독자들에게 알림을 발송합니다.
 * 이메일, 푸시 알림 등 다양한 채널을 통해 알림을 보낼 수 있습니다.
 */
class SendNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * 큐 연결
     * 
     * @var string
     */
    public $connection = 'database';

    /**
     * 큐 이름
     * 
     * @var string
     */
    public $queue = 'notifications';

    /**
     * 재시도 횟수
     * 
     * @var int
     */
    public $tries = 3;

    /**
     * 게시물 발행 이벤트 처리
     * 
     * @param PostPublished $event
     * @return void
     */
    public function handle(PostPublished $event): void
    {
        try {
            $post = $event->post;
            
            // 구독자 알림 활성화 확인
            if (!config('ahhob_blog.notifications.enabled', true)) {
                Log::info('Notifications disabled, skipping post notification', [
                    'post_id' => $post->id,
                ]);
                return;
            }
            
            // 이메일 구독자들에게 알림 발송
            $this->sendEmailNotifications($post);
            
            // 푸시 알림 발송
            $this->sendPushNotifications($post);
            
            // 슬랙 웹훅 (관리자용)
            $this->sendSlackNotification($post);
            
            Log::info('Notifications sent for published post', [
                'post_id' => $post->id,
                'post_title' => $post->title,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send notifications for published post', [
                'post_id' => $event->post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 이메일 구독자들에게 알림 발송
     * 
     * @param \App\Models\Blog\Post $post
     * @return void
     */
    private function sendEmailNotifications($post): void
    {
        // TODO: 실제 구독자 모델이 있다면 해당 모델에서 구독자 목록 조회
        // $subscribers = Subscriber::where('is_active', true)->get();
        
        // 임시로 관리자에게만 알림 발송
        $adminEmail = config('mail.admin_email');
        if ($adminEmail) {
            try {
                // TODO: 실제 메일 클래스 구현
                // Mail::to($adminEmail)->send(new PostPublishedMail($post));
                
                Log::info('Email notification would be sent', [
                    'post_id' => $post->id,
                    'recipient' => $adminEmail,
                ]);
                
            } catch (\Exception $e) {
                Log::warning('Failed to send email notification', [
                    'post_id' => $post->id,
                    'recipient' => $adminEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 푸시 알림 발송
     * 
     * @param \App\Models\Blog\Post $post
     * @return void
     */
    private function sendPushNotifications($post): void
    {
        // TODO: FCM, OneSignal 등 푸시 알림 서비스 연동
        
        $pushData = [
            'title' => '새 게시물이 발행되었습니다',
            'body' => $post->title,
            'icon' => asset('images/blog-icon.png'),
            'click_action' => route('web.posts.show', $post->slug),
            'data' => [
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'type' => 'post_published',
            ],
        ];
        
        Log::info('Push notification would be sent', [
            'post_id' => $post->id,
            'push_data' => $pushData,
        ]);
    }

    /**
     * 슬랙 웹훅으로 관리자 알림
     * 
     * @param \App\Models\Blog\Post $post
     * @return void
     */
    private function sendSlackNotification($post): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        if (!$webhookUrl) {
            return;
        }

        try {
            $payload = [
                'text' => '새 게시물이 발행되었습니다! 🎉',
                'attachments' => [
                    [
                        'color' => 'good',
                        'title' => $post->title,
                        'title_link' => route('web.posts.show', $post->slug),
                        'fields' => [
                            [
                                'title' => '작성자',
                                'value' => $post->user->name ?? '알 수 없음',
                                'short' => true,
                            ],
                            [
                                'title' => '카테고리',
                                'value' => $post->category->name ?? '없음',
                                'short' => true,
                            ],
                            [
                                'title' => '발행 시간',
                                'value' => $post->published_at->format('Y-m-d H:i:s'),
                                'short' => true,
                            ],
                        ],
                        'footer' => config('app.name', 'Blog'),
                        'ts' => $post->published_at->timestamp,
                    ],
                ],
            ];

            // TODO: 실제 HTTP 클라이언트로 슬랙 웹훅 호출
            // Http::post($webhookUrl, $payload);
            
            Log::info('Slack notification would be sent', [
                'post_id' => $post->id,
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to send Slack notification', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}