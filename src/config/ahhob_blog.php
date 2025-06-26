<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AhHob Blog Configuration
    |--------------------------------------------------------------------------
    */

    'mode' => env('AHHOB_MODE', 'web'), // 'web', 'admin', 'api'

    'routes' => [
        'web_prefix' => '',
        'admin_prefix' => 'admin',
        'api_prefix' => 'api/v1',
    ],

    'auth' => [
        'admin_guard' => 'admin',
        'api_guard' => 'sanctum',
        'web_guard' => 'web',
    ],

    'upload' => [
        // 프로필 이미지 설정
        'profile_images' => [
            'path' => 'uploads/profiles',
            'max_size' => 2048, // KB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
        ],
        
        // 게시물 이미지 설정
        'post_images' => [
            'path' => 'uploads/posts',
            'max_size' => 5120, // KB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        ],
        
        // 이미지 최적화 설정
        'optimization' => [
            // 기본 설정
            'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
            'max_image_width' => env('IMAGE_MAX_WIDTH', 2000),
            'max_image_height' => env('IMAGE_MAX_HEIGHT', 2000),
            'default_quality' => env('IMAGE_DEFAULT_QUALITY', 90),
            
            // WebP 변환 설정
            'convert_to_webp' => env('IMAGE_CONVERT_TO_WEBP', true),
            'webp_quality' => env('IMAGE_WEBP_QUALITY', 85),
            
            // 썸네일 생성 설정
            'generate_thumbnails' => env('IMAGE_GENERATE_THUMBNAILS', true),
            'thumbnail_sizes' => [
                'thumbnail' => [
                    'width' => env('THUMBNAIL_SIZE_WIDTH', 150),
                    'height' => env('THUMBNAIL_SIZE_HEIGHT', 150),
                    'quality' => env('THUMBNAIL_QUALITY', 85)
                ],
                'small' => [
                    'width' => env('SMALL_SIZE_WIDTH', 300),
                    'height' => env('SMALL_SIZE_HEIGHT', 200),
                    'quality' => env('SMALL_QUALITY', 90)
                ],
                'medium' => [
                    'width' => env('MEDIUM_SIZE_WIDTH', 600),
                    'height' => env('MEDIUM_SIZE_HEIGHT', 400),
                    'quality' => env('MEDIUM_QUALITY', 90)
                ],
                'large' => [
                    'width' => env('LARGE_SIZE_WIDTH', 1200),
                    'height' => env('LARGE_SIZE_HEIGHT', 800),
                    'quality' => env('LARGE_QUALITY', 85)
                ]
            ],
            
            // 캐시 설정
            'cache_optimized_images' => env('IMAGE_CACHE_ENABLED', true),
            'cache_ttl' => env('IMAGE_CACHE_TTL', 86400), // 24시간
            
            // 정리 설정
            'auto_cleanup_orphaned' => env('IMAGE_AUTO_CLEANUP', false),
            'cleanup_schedule' => 'weekly', // daily, weekly, monthly
        ],
        
        // 보안 설정
        'security' => [
            'scan_for_malware' => env('IMAGE_SCAN_MALWARE', false),
            'strip_exif_data' => env('IMAGE_STRIP_EXIF', false),
            'watermark_enabled' => env('IMAGE_WATERMARK_ENABLED', false),
            'watermark_text' => env('IMAGE_WATERMARK_TEXT', ''),
            'watermark_opacity' => env('IMAGE_WATERMARK_OPACITY', 50),
        ]
    ],

    'pagination' => [
        'per_page' => 10,
        'admin_per_page' => 20,
    ],

    'cache' => [
        // 캐시 활성화 여부
        'enabled' => env('BLOG_CACHE_ENABLED', true),
        
        // 각 컨텐츠 타입별 TTL 설정 (초 단위)
        'posts_ttl' => env('BLOG_CACHE_POSTS_TTL', 3600), // 1시간
        'categories_ttl' => env('BLOG_CACHE_CATEGORIES_TTL', 7200), // 2시간
        'tags_ttl' => env('BLOG_CACHE_TAGS_TTL', 3600), // 1시간
        'pages_ttl' => env('BLOG_CACHE_PAGES_TTL', 7200), // 2시간
        'comments_ttl' => env('BLOG_CACHE_COMMENTS_TTL', 1800), // 30분
        'stats_ttl' => env('BLOG_CACHE_STATS_TTL', 1800), // 30분
        'static_ttl' => env('BLOG_CACHE_STATIC_TTL', 43200), // 12시간 (RSS, Sitemap)
        'search_ttl' => env('BLOG_CACHE_SEARCH_TTL', 900), // 15분
        
        // 캐시 키 프리픽스
        'prefix' => env('BLOG_CACHE_PREFIX', 'ahhob_blog'),
        
        // 태그 기반 캐시 설정
        'use_tags' => env('BLOG_CACHE_USE_TAGS', true),
        'default_tags' => ['blog'],
        
        // 자동 캐시 무효화 설정
        'auto_invalidate' => [
            'on_post_save' => true,
            'on_category_save' => true,
            'on_comment_save' => true,
            'on_user_action' => true, // 좋아요, 조회수 등
        ],
        
        // 캐시 워밍업 설정
        'warmup' => [
            'enabled' => env('BLOG_CACHE_WARMUP', false),
            'schedule' => 'daily', // daily, hourly, weekly
        ],
        
        // 개발 환경 설정
        'debug' => env('BLOG_CACHE_DEBUG', false),
        'log_hits' => env('BLOG_CACHE_LOG_HITS', false),
        'log_misses' => env('BLOG_CACHE_LOG_MISSES', false),
    ],

    'analytics' => [
        'google_analytics' => [
            'measurement_id' => env('GA_MEASUREMENT_ID'),
            'enabled' => env('GA_ENABLED', false) && env('GA_MEASUREMENT_ID') !== null,
            'anonymize_ip' => env('GA_ANONYMIZE_IP', true),
            'cookie_domain' => env('GA_COOKIE_DOMAIN', 'auto'),
            'cookie_expires' => env('GA_COOKIE_EXPIRES', 63072000), // 2년
        ],
        'google_tag_manager' => [
            'container_id' => env('GTM_ID'),
            'enabled' => env('GTM_ENABLED', false) && env('GTM_ID') !== null,
        ],
        'google_adsense' => [
            'client_id' => env('ADSENSE_CLIENT_ID'),
            'enabled' => env('ADSENSE_ENABLED', false) && env('ADSENSE_CLIENT_ID') !== null,
            'auto_ads' => env('ADSENSE_AUTO_ADS', true),
        ],
        // 개인정보 보호 설정
        'privacy_mode' => env('ANALYTICS_PRIVACY_MODE', true),
        'require_cookie_consent' => env('ANALYTICS_REQUIRE_CONSENT', true),
        'consent_cookie_name' => env('ANALYTICS_CONSENT_COOKIE', 'analytics_consent'),
        'consent_cookie_duration' => env('ANALYTICS_CONSENT_DURATION', 365), // 일 단위
        // 개발/테스트 환경 설정
        'debug_mode' => env('ANALYTICS_DEBUG_MODE', false),
        'test_mode' => env('ANALYTICS_TEST_MODE', false),
    ],
];

