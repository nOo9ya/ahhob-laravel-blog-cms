<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * XSS (Cross-Site Scripting) 방어 미들웨어
 * 
 * 이 미들웨어는 다음과 같은 XSS 공격 방어 기능을 제공합니다:
 * - 입력 데이터에서 악성 스크립트 패턴 탐지 및 차단
 * - HTML 태그 필터링 및 정제
 * - Content Security Policy (CSP) 헤더 설정
 * - 의심스러운 요청 로깅 및 모니터링
 * 
 * 설정은 config/security.php에서 관리됩니다.
 */
class XssProtection
{
    /**
     * XSS 공격에 일반적으로 사용되는 패턴들
     * 
     * 이 패턴들은 정규표현식으로 사용되어 입력 데이터를 검사합니다.
     * 새로운 공격 패턴이 발견되면 이 배열에 추가할 수 있습니다.
     */
    protected array $xssPatterns = [
        // JavaScript 실행 패턴
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/data:text\/html/i',
        
        // 이벤트 핸들러 패턴
        '/on\w+\s*=/i', // onclick, onload, onerror 등
        
        // HTML 태그 내 스크립트 패턴
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>/i',
        '/<applet[^>]*>.*?<\/applet>/is',
        
        // CSS 내 스크립트 패턴
        '/expression\s*\(/i',
        '/behavior\s*:/i',
        '/@import/i',
        
        // 특수 엔티티 패턴
        '/&#(\d+);/i',
        '/&#x([a-f0-9]+);/i',
        
        // Base64 인코딩된 스크립트 패턴
        '/data:text\/javascript/i',
        '/data:application\/javascript/i',
        
        // 기타 위험한 패턴
        '/formaction\s*=/i',
        '/<meta[^>]*http-equiv/i',
        '/<link[^>]*href.*javascript/i'
    ];

    /**
     * 안전한 HTML 태그 목록
     * 
     * 마크다운 에디터에서 사용되는 태그들과 일반적으로 안전한 태그들입니다.
     * 이 목록에 없는 태그들은 자동으로 제거되거나 이스케이프됩니다.
     */
    protected array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins', 'mark',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'blockquote', 'pre', 'code', 'kbd', 'samp', 'var',
        'a', 'img', 'figure', 'figcaption',
        'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'div', 'span', 'section', 'article', 'header', 'footer', 'main', 'aside',
        'hr', 'abbr', 'cite', 'q', 'time', 'small', 'sub', 'sup'
    ];

    /**
     * 안전한 HTML 속성 목록
     * 
     * 허용되는 HTML 속성들입니다. 이벤트 핸들러나 스크립트 실행이 
     * 가능한 속성들은 제외되어 있습니다.
     */
    protected array $allowedAttributes = [
        'href', 'src', 'alt', 'title', 'class', 'id', 'name',
        'target', 'rel', 'data-*', 'aria-*',
        'width', 'height', 'size', 'type', 'role',
        'colspan', 'rowspan', 'scope', 'headers',
        'datetime', 'cite', 'lang', 'dir'
    ];

    /**
     * 미들웨어 핸들러
     * 
     * @param Request $request HTTP 요청 객체
     * @param Closure $next 다음 미들웨어 체인
     * @return Response HTTP 응답 객체
     */
    public function handle(Request $request, Closure $next): Response
    {
        // XSS 보호가 비활성화된 경우 패스
        if (!config('security.xss_protection.enabled', true)) {
            return $next($request);
        }

        // 1. 요청 데이터 XSS 검사 및 정제
        $this->scanAndCleanRequest($request);

        // 2. 다음 미들웨어 실행
        $response = $next($request);

        // 3. 응답에 보안 헤더 추가
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * 요청 데이터 XSS 검사 및 정제
     * 
     * 모든 입력 데이터를 재귀적으로 검사하여 XSS 패턴을 탐지하고,
     * 발견된 악성 코드를 제거하거나 이스케이프 처리합니다.
     * 
     * @param Request $request HTTP 요청 객체
     * @return void
     */
    protected function scanAndCleanRequest(Request $request): void
    {
        try {
            // POST/PUT 요청 데이터 정제
            if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
                $cleanedData = $this->cleanArray($request->all());
                $request->replace($cleanedData);
            }

            // 쿼리 파라미터 정제
            $cleanedQuery = $this->cleanArray($request->query->all());
            $request->query->replace($cleanedQuery);

            // 헤더 검사 (User-Agent, Referer 등)
            $this->validateHeaders($request);

        } catch (\Exception $e) {
            // XSS 정제 중 오류 발생 시 로깅
            Log::warning('XSS protection middleware error', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // 보안상 민감한 오류는 사용자에게 노출하지 않음
            abort(400, 'Invalid request data');
        }
    }

    /**
     * 배열 데이터 재귀적 정제
     * 
     * 중첩된 배열과 객체를 포함한 모든 데이터를 재귀적으로 처리하여
     * XSS 패턴을 검사하고 정제합니다.
     * 
     * @param array $data 정제할 데이터 배열
     * @return array 정제된 데이터 배열
     */
    protected function cleanArray(array $data): array
    {
        $cleaned = [];

        foreach ($data as $key => $value) {
            // 키 자체도 XSS 검사
            $cleanKey = $this->cleanString((string)$key);

            if (is_array($value)) {
                // 배열인 경우 재귀적으로 처리
                $cleaned[$cleanKey] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                // 문자열인 경우 XSS 정제
                $cleaned[$cleanKey] = $this->cleanString($value);
            } else {
                // 기타 타입 (숫자, boolean 등)은 그대로 유지
                $cleaned[$cleanKey] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * 문자열 XSS 정제
     * 
     * 개별 문자열에서 XSS 패턴을 검사하고 정제합니다.
     * 마크다운 콘텐츠와 일반 텍스트를 구분하여 처리합니다.
     * 
     * @param string $input 정제할 입력 문자열
     * @param bool $allowHtml HTML 태그 허용 여부
     * @return string 정제된 문자열
     */
    protected function cleanString(string $input, bool $allowHtml = false): string
    {
        // 빈 문자열은 그대로 반환
        if (empty($input)) {
            return $input;
        }

        // 1. 위험한 패턴 탐지 및 로깅
        if ($this->detectXssPatterns($input)) {
            $this->logSuspiciousActivity($input);
            
            // 위험도가 높은 경우 요청 차단
            if ($this->isHighRiskPattern($input)) {
                abort(403, 'Malicious content detected');
            }
        }

        // 2. HTML 허용 여부에 따른 처리
        if ($allowHtml) {
            // HTML 허용 시: 안전한 태그만 유지하고 속성 정제
            return $this->sanitizeHtml($input);
        } else {
            // HTML 비허용 시: 모든 HTML 태그 이스케이프
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    /**
     * XSS 패턴 탐지
     * 
     * 미리 정의된 XSS 패턴들을 사용하여 입력 데이터에서
     * 악성 스크립트 패턴을 탐지합니다.
     * 
     * @param string $input 검사할 입력 문자열
     * @return bool XSS 패턴 발견 여부
     */
    protected function detectXssPatterns(string $input): bool
    {
        foreach ($this->xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        // 추가적인 휴리스틱 검사
        return $this->detectAdvancedXssPatterns($input);
    }

    /**
     * 고급 XSS 패턴 탐지
     * 
     * 정규표현식으로 탐지하기 어려운 고급 XSS 패턴들을
     * 휴리스틱 방법으로 탐지합니다.
     * 
     * @param string $input 검사할 입력 문자열
     * @return bool 고급 XSS 패턴 발견 여부
     */
    protected function detectAdvancedXssPatterns(string $input): bool
    {
        // URL 인코딩 우회 시도 탐지
        $decoded = urldecode($input);
        if ($decoded !== $input && $this->containsScriptTags($decoded)) {
            return true;
        }

        // HTML 엔티티 우회 시도 탐지
        $htmlDecoded = html_entity_decode($input, ENT_QUOTES | ENT_HTML5);
        if ($htmlDecoded !== $input && $this->containsScriptTags($htmlDecoded)) {
            return true;
        }

        // Base64 인코딩 우회 시도 탐지
        if (preg_match('/[A-Za-z0-9+\/]{20,}={0,2}/', $input)) {
            $base64Decoded = @base64_decode($input);
            if ($base64Decoded && $this->containsScriptTags($base64Decoded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 스크립트 태그 포함 여부 검사
     * 
     * @param string $input 검사할 문자열
     * @return bool 스크립트 태그 포함 여부
     */
    protected function containsScriptTags(string $input): bool
    {
        return stripos($input, '<script') !== false || 
               stripos($input, 'javascript:') !== false ||
               stripos($input, 'vbscript:') !== false;
    }

    /**
     * 고위험 패턴 여부 판단
     * 
     * 특별히 위험한 패턴들을 식별하여 즉시 차단할지 결정합니다.
     * 
     * @param string $input 검사할 입력 문자열
     * @return bool 고위험 패턴 여부
     */
    protected function isHighRiskPattern(string $input): bool
    {
        $highRiskPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/document\.cookie/i',
            '/window\.location/i',
            '/eval\s*\(/i',
            '/setTimeout\s*\(/i',
            '/setInterval\s*\(/i'
        ];

        foreach ($highRiskPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HTML 정제 (안전한 태그만 유지)
     * 
     * 허용된 태그와 속성만 유지하고 나머지는 제거하거나 이스케이프합니다.
     * 
     * @param string $html 정제할 HTML 문자열
     * @return string 정제된 HTML 문자열
     */
    protected function sanitizeHtml(string $html): string
    {
        // 설정에서 허용된 태그 가져오기
        $allowedTags = config('security.xss_protection.allowed_html_tags', $this->allowedTags);
        $allowedAttributes = config('security.xss_protection.allowed_attributes', $this->allowedAttributes);

        // HTML Purifier를 사용할 수 있다면 사용 (더 정교한 정제)
        if (class_exists('\HTMLPurifier')) {
            return $this->purifyWithHtmlPurifier($html, $allowedTags, $allowedAttributes);
        }

        // 기본 정제 로직
        return $this->basicHtmlSanitize($html, $allowedTags, $allowedAttributes);
    }

    /**
     * 기본 HTML 정제
     * 
     * HTML Purifier가 없는 경우 사용하는 기본적인 HTML 정제 로직입니다.
     * 
     * @param string $html 정제할 HTML
     * @param array $allowedTags 허용된 태그 목록
     * @param array $allowedAttributes 허용된 속성 목록
     * @return string 정제된 HTML
     */
    protected function basicHtmlSanitize(string $html, array $allowedTags, array $allowedAttributes): string
    {
        // 허용된 태그를 제외한 모든 HTML 태그 제거
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        $cleaned = strip_tags($html, $allowedTagsString);

        // 남은 태그들의 속성 정제
        return $this->sanitizeAttributes($cleaned, $allowedAttributes);
    }

    /**
     * HTML 속성 정제
     * 
     * @param string $html HTML 문자열
     * @param array $allowedAttributes 허용된 속성 목록
     * @return string 속성이 정제된 HTML
     */
    protected function sanitizeAttributes(string $html, array $allowedAttributes): string
    {
        // 간단한 속성 정제 (실제 구현에서는 더 정교한 파싱 필요)
        $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        $html = preg_replace('/vbscript\s*:/i', '', $html);
        
        return $html;
    }

    /**
     * HTTP 헤더 검증
     * 
     * 요청 헤더에서 XSS 공격 시도를 탐지합니다.
     * 
     * @param Request $request HTTP 요청 객체
     * @return void
     */
    protected function validateHeaders(Request $request): void
    {
        $suspiciousHeaders = ['user-agent', 'referer', 'x-forwarded-for'];

        foreach ($suspiciousHeaders as $headerName) {
            $headerValue = $request->header($headerName);
            
            if ($headerValue && $this->detectXssPatterns($headerValue)) {
                $this->logSuspiciousActivity($headerValue, 'header: ' . $headerName);
                
                // 헤더의 XSS는 특별히 위험하므로 차단
                abort(400, 'Invalid request headers');
            }
        }
    }

    /**
     * 의심스러운 활동 로깅
     * 
     * XSS 공격 시도나 의심스러운 패턴을 로그에 기록합니다.
     * 
     * @param string $input 의심스러운 입력
     * @param string $context 컨텍스트 정보
     * @return void
     */
    protected function logSuspiciousActivity(string $input, string $context = 'request_data'): void
    {
        Log::warning('Potential XSS attack detected', [
            'context' => $context,
            'suspicious_input' => substr($input, 0, 200), // 처음 200자만 로깅
            'request_path' => request()->path(),
            'request_method' => request()->method(),
            'user_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * 보안 헤더 추가
     * 
     * 응답에 XSS 방어를 위한 보안 헤더들을 추가합니다.
     * 
     * @param Response $response HTTP 응답 객체
     * @return void
     */
    protected function addSecurityHeaders(Response $response): void
    {
        if (!config('security.security_headers.enabled', true)) {
            return;
        }

        // X-XSS-Protection 헤더
        $response->headers->set(
            'X-XSS-Protection', 
            config('security.security_headers.xss_protection_header', '1; mode=block')
        );

        // X-Content-Type-Options 헤더
        $response->headers->set(
            'X-Content-Type-Options', 
            config('security.security_headers.content_type_options', 'nosniff')
        );

        // Content Security Policy 헤더
        if (config('security.xss_protection.csp.enabled', true)) {
            $cspHeader = $this->buildCspHeader();
            $response->headers->set('Content-Security-Policy', $cspHeader);
        }

        // X-Frame-Options 헤더
        $response->headers->set(
            'X-Frame-Options', 
            config('security.security_headers.frame_options', 'DENY')
        );

        // Referrer-Policy 헤더
        $response->headers->set(
            'Referrer-Policy', 
            config('security.security_headers.referrer_policy', 'strict-origin-when-cross-origin')
        );
    }

    /**
     * Content Security Policy 헤더 생성
     * 
     * 설정을 기반으로 CSP 헤더 문자열을 생성합니다.
     * 
     * @return string CSP 헤더 문자열
     */
    protected function buildCspHeader(): string
    {
        $cspConfig = config('security.xss_protection.csp', []);
        $cspParts = [];

        foreach ($cspConfig as $directive => $sources) {
            if ($directive === 'enabled') {
                continue;
            }

            $directiveName = str_replace('_', '-', $directive);
            
            if (is_array($sources)) {
                $sourceList = implode(' ', $sources);
                $cspParts[] = "{$directiveName} {$sourceList}";
            } else {
                $cspParts[] = "{$directiveName} {$sources}";
            }
        }

        return implode('; ', $cspParts);
    }
}