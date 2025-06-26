<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // JWT 시크릿 키 설정 (테스트 환경용)
        config(['jwt.secret' => base64_encode('test-secret-key-for-testing-purposes-only')]);
        config(['jwt.ttl' => 60]);
        config(['jwt.refresh_ttl' => 20160]);
        config(['jwt.algo' => 'HS256']);
        config(['jwt.blacklist_enabled' => true]);
        
        // 캐시 초기화
        if (app()->bound('cache')) {
            app('cache')->flush();
        }
    }
}
