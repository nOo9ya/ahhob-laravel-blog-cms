<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | 이 파일은 애플리케이션의 보안 관련 설정을 관리합니다.
    | XSS 방어, CSRF 보호, 비밀번호 정책, 세션 보안 등을 포함합니다.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | XSS (Cross-Site Scripting) Protection
    |--------------------------------------------------------------------------
    |
    | XSS 공격을 방어하기 위한 설정들입니다.
    | 입력 데이터 필터링, 출력 인코딩, CSP 헤더 등을 관리합니다.
    |
    */
    'xss_protection' => [
        'enabled' => env('XSS_PROTECTION_ENABLED', true),
        
        // 허용할 HTML 태그 (마크다운 에디터용)
        'allowed_html_tags' => [
            'p', 'br', 'strong', 'em', 'u', 's', 'del', 'ins',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
            'a', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'div', 'span'
        ],
        
        // 허용할 HTML 속성
        'allowed_attributes' => [
            'href', 'src', 'alt', 'title', 'class', 'id',
            'target', 'rel', 'data-*', 'width', 'height'
        ],
        
        // Content Security Policy 설정
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'default_src' => ["'self'"],
            'script_src' => [
                "'self'", 
                "'unsafe-inline'", // Toast UI Editor용 (개발 환경에서만)
                "https://www.googletagmanager.com",
                "https://www.google-analytics.com",
                "https://pagead2.googlesyndication.com"
            ],
            'style_src' => [
                "'self'", 
                "'unsafe-inline'", // TailwindCSS용
                "https://fonts.googleapis.com"
            ],
            'img_src' => [
                "'self'", 
                "data:", 
                "https:",
                "blob:"
            ],
            'font_src' => [
                "'self'", 
                "https://fonts.gstatic.com"
            ],
            'connect_src' => [
                "'self'",
                "https://www.google-analytics.com"
            ],
            'frame_src' => [
                "https://www.google.com" // reCAPTCHA용
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF (Cross-Site Request Forgery) Protection
    |--------------------------------------------------------------------------
    |
    | CSRF 공격을 방어하기 위한 설정들입니다.
    | 토큰 유효기간, 예외 경로, 추가 검증 등을 관리합니다.
    |
    */
    'csrf_protection' => [
        'enabled' => env('CSRF_PROTECTION_ENABLED', true),
        
        // CSRF 토큰 유효기간 (분 단위)
        'token_lifetime' => env('CSRF_TOKEN_LIFETIME', 120),
        
        // CSRF 검증에서 제외할 경로
        'except_routes' => [
            'api/*', // API 경로는 Sanctum으로 보호
            'webhooks/*', // 웹훅은 별도 인증
        ],
        
        // 추가 보안 검증 활성화
        'verify_referer' => env('CSRF_VERIFY_REFERER', true),
        'verify_origin' => env('CSRF_VERIFY_ORIGIN', true),
        
        // AJAX 요청을 위한 설정
        'ajax_setup' => [
            'header_name' => 'X-CSRF-TOKEN',
            'meta_name' => 'csrf-token'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security Policy
    |--------------------------------------------------------------------------
    |
    | 비밀번호 보안 정책 설정입니다.
    | 복잡도 요구사항, 히스토리 관리, 만료 정책 등을 정의합니다.
    |
    */
    'password_policy' => [
        'enabled' => env('PASSWORD_POLICY_ENABLED', true),
        
        // 비밀번호 복잡도 요구사항
        'complexity' => [
            'min_length' => env('PASSWORD_MIN_LENGTH', 8),
            'max_length' => env('PASSWORD_MAX_LENGTH', 128),
            'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
            'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
            'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
            'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
            'allowed_symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
        ],
        
        // 비밀번호 히스토리 관리
        'history' => [
            'enabled' => env('PASSWORD_HISTORY_ENABLED', true),
            'remember_count' => env('PASSWORD_HISTORY_COUNT', 5), // 최근 5개 비밀번호 기억
        ],
        
        // 비밀번호 만료 정책
        'expiration' => [
            'enabled' => env('PASSWORD_EXPIRATION_ENABLED', false),
            'days' => env('PASSWORD_EXPIRATION_DAYS', 90),
            'warning_days' => env('PASSWORD_EXPIRATION_WARNING_DAYS', 7),
        ],
        
        // 계정 잠금 정책
        'lockout' => [
            'enabled' => env('PASSWORD_LOCKOUT_ENABLED', true),
            'max_attempts' => env('PASSWORD_MAX_ATTEMPTS', 5),
            'lockout_duration' => env('PASSWORD_LOCKOUT_DURATION', 15), // 분 단위
            'decay_minutes' => env('PASSWORD_ATTEMPTS_DECAY', 60),
        ],
        
        // 비밀번호 재확인 정책
        'confirmation' => [
            'enabled' => env('PASSWORD_CONFIRMATION_ENABLED', true),
            'timeout' => env('PASSWORD_CONFIRMATION_TIMEOUT', 10800), // 3시간 (초 단위)
            
            // 비밀번호 재확인이 필요한 액션들
            'required_for' => [
                'profile_update',
                'password_change',
                'account_deletion',
                'sensitive_settings',
                'admin_actions'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | 세션 보안 관련 설정입니다.
    | 세션 하이재킹 방지, 동시 로그인 제한 등을 관리합니다.
    |
    */
    'session_security' => [
        'enabled' => env('SESSION_SECURITY_ENABLED', true),
        
        // 세션 재생성 설정
        'regenerate_on_login' => true,
        'regenerate_interval' => env('SESSION_REGENERATE_INTERVAL', 300), // 5분마다
        
        // IP 주소 검증
        'verify_ip_address' => env('SESSION_VERIFY_IP', true),
        'ip_change_action' => env('SESSION_IP_CHANGE_ACTION', 'logout'), // logout, warn, ignore
        
        // User-Agent 검증
        'verify_user_agent' => env('SESSION_VERIFY_USER_AGENT', true),
        
        // 동시 로그인 제한
        'concurrent_sessions' => [
            'enabled' => env('SESSION_CONCURRENT_LIMIT_ENABLED', false),
            'max_sessions' => env('SESSION_MAX_CONCURRENT', 3),
            'action' => env('SESSION_CONCURRENT_ACTION', 'logout_oldest'), // logout_oldest, block_new
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation & Sanitization
    |--------------------------------------------------------------------------
    |
    | 입력 데이터 검증 및 정제 설정입니다.
    | SQL 인젝션, XSS, 파일 업로드 보안 등을 관리합니다.
    |
    */
    'input_validation' => [
        'enabled' => env('INPUT_VALIDATION_ENABLED', true),
        
        // SQL 인젝션 방어
        'sql_injection_protection' => [
            'enabled' => true,
            'blocked_keywords' => [
                'union', 'select', 'insert', 'update', 'delete', 'drop',
                'alter', 'create', 'exec', 'execute', 'script'
            ]
        ],
        
        // 파일 업로드 보안
        'file_upload_security' => [
            'enabled' => true,
            'scan_for_malware' => env('FILE_SCAN_MALWARE', false),
            'check_file_content' => true,
            'blocked_extensions' => [
                'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
                'jar', 'php', 'asp', 'aspx', 'jsp', 'pl', 'py', 'rb'
            ],
            'max_file_size' => env('FILE_MAX_SIZE', 10485760), // 10MB
        ],
        
        // URL 검증
        'url_validation' => [
            'allowed_protocols' => ['http', 'https', 'ftp', 'mailto'],
            'blocked_domains' => [], // 차단할 도메인 목록
            'check_domain_reputation' => false
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | API 및 웹 요청에 대한 속도 제한 설정입니다.
    | DDoS 공격 방어 및 리소스 보호를 위한 설정들입니다.
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        
        // 로그인 시도 제한
        'login_attempts' => [
            'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('LOGIN_ATTEMPTS_DECAY', 60),
            'lockout_duration' => env('LOGIN_LOCKOUT_DURATION', 60),
        ],
        
        // 일반 웹 요청 제한
        'web_requests' => [
            'enabled' => true,
            'max_attempts' => env('WEB_RATE_LIMIT', 1000),
            'decay_minutes' => env('WEB_RATE_DECAY', 1),
        ],
        
        // API 요청 제한
        'api_requests' => [
            'enabled' => true,
            'max_attempts' => env('API_RATE_LIMIT', 100),
            'decay_minutes' => env('API_RATE_DECAY', 1),
        ],
        
        // 특별한 엔드포인트 제한
        'special_endpoints' => [
            'contact_form' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'image_upload' => [
                'max_attempts' => 20,
                'decay_minutes' => 1,
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | HTTP 보안 헤더 설정입니다.
    | 브라우저 보안 기능을 활용한 추가적인 보호층을 제공합니다.
    |
    */
    'security_headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        
        // HTTP Strict Transport Security
        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1년
            'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('HSTS_PRELOAD', false),
        ],
        
        // X-Frame-Options
        'frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        
        // X-Content-Type-Options
        'content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        
        // X-XSS-Protection
        'xss_protection_header' => env('X_XSS_PROTECTION', '1; mode=block'),
        
        // Referrer Policy
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        
        // Permissions Policy
        'permissions_policy' => [
            'camera' => '()',
            'microphone' => '()',
            'geolocation' => '()',
            'payment' => '()'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | 보안 이벤트 모니터링 및 로깅 설정입니다.
    | 공격 시도 감지, 알림, 로그 관리 등을 담당합니다.
    |
    */
    'monitoring' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        
        // 로그 레벨 설정
        'log_level' => env('SECURITY_LOG_LEVEL', 'warning'),
        
        // 모니터링할 이벤트들
        'monitor_events' => [
            'failed_login_attempts',
            'password_changes',
            'account_lockouts',
            'suspicious_activity',
            'file_upload_attempts',
            'admin_actions'
        ],
        
        // 알림 설정
        'notifications' => [
            'enabled' => env('SECURITY_NOTIFICATIONS_ENABLED', false),
            'channels' => ['mail', 'slack'], // 알림 채널
            'threshold' => [
                'failed_logins' => 10, // 10번 실패 시 알림
                'lockouts' => 3, // 3번 계정 잠금 시 알림
            ]
        ],
        
        // 보안 리포트
        'reports' => [
            'enabled' => env('SECURITY_REPORTS_ENABLED', false),
            'frequency' => env('SECURITY_REPORT_FREQUENCY', 'weekly'),
            'recipients' => explode(',', env('SECURITY_REPORT_RECIPIENTS', '')),
        ]
    ]
];