<?php

namespace App\Services\Ahhob\Blog\Shared;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\TableExtension;

class MarkdownService
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new TableExtension());

        $this->converter = new CommonMarkConverter([], $environment);
    }

    /**
     * 마크다운을 HTML로 변환
     */
    public function toHtml(string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        try {
            $html = $this->converter->convert($markdown)->getContent();

            // 추가적인 HTML 정제 (보안)
            $html = $this->sanitizeHtml($html);

            return $html;
        } catch (\Exception $e) {
            // 변환 실패 시 원본 텍스트 반환 (이스케이프 처리)
            return '<p>' . htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }

    /**
     * HTML에서 마크다운으로 변환 (간단한 변환)
     */
    public function toMarkdown(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 기본적인 HTML to Markdown 변환
        $markdown = $html;

        // 간단한 태그 변환
        $replacements = [
            '/<h1[^>]*>(.*?)<\/h1>/i' => '# $1',
            '/<h2[^>]*>(.*?)<\/h2>/i' => '## $1',
            '/<h3[^>]*>(.*?)<\/h3>/i' => '### $1',
            '/<h4[^>]*>(.*?)<\/h4>/i' => '#### $1',
            '/<h5[^>]*>(.*?)<\/h5>/i' => '##### $1',
            '/<h6[^>]*>(.*?)<\/h6>/i' => '###### $1',
            '/<strong[^>]*>(.*?)<\/strong>/i' => '**$1**',
            '/<b[^>]*>(.*?)<\/b>/i' => '**$1**',
            '/<em[^>]*>(.*?)<\/em>/i' => '*$1*',
            '/<i[^>]*>(.*?)<\/i>/i' => '*$1*',
            '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i' => '[$2]($1)',
            '/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i' => '![$2]($1)',
            '/<img[^>]*src=["\']([^"\']*)["\'][^>]*>/i' => '![]($1)',
            '/<br[^>]*>/i' => "\n",
            '/<p[^>]*>/i' => '',
            '/<\/p>/i' => "\n\n",
        ];

        foreach ($replacements as $pattern => $replacement) {
            $markdown = preg_replace($pattern, $replacement, $markdown);
        }

        // HTML 태그 제거
        $markdown = strip_tags($markdown);

        // 불필요한 공백 정리
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
        $markdown = trim($markdown);

        return $markdown;
    }

    /**
     * HTML 정제 (보안 목적)
     */
    private function sanitizeHtml(string $html): string
    {
        // 허용할 태그들
        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'strike', 'del',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'blockquote', 'pre', 'code',
            'a', 'img',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'hr'
        ];

        // 허용할 속성들
        $allowedAttributes = [
            'href', 'src', 'alt', 'title', 'class'
        ];

        // 기본적인 정제
        $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');

        // 위험한 속성 제거
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);

        return $html;
    }

    /**
     * 마크다운에서 이미지 URL 추출
     */
    public function extractImageUrls(string $markdown): array
    {
        $urls = [];

        // 마크다운 이미지 패턴 매칭
        preg_match_all('/!\[.*?\]\((.*?)\)/', $markdown, $matches);

        if (!empty($matches[1])) {
            $urls = array_merge($urls, $matches[1]);
        }

        // HTML img 태그 패턴 매칭
        preg_match_all('/<img[^>]*src=["\']([^"\']*)["\'][^>]*>/i', $markdown, $matches);

        if (!empty($matches[1])) {
            $urls = array_merge($urls, $matches[1]);
        }

        return array_unique($urls);
    }

    /**
     * 텍스트에서 요약 추출
     */
    public function extractExcerpt(string $markdown, int $length = 160): string
    {
        if (empty($markdown)) {
            return '';
        }

        // 마크다운을 일반 텍스트로 변환
        $text = $this->toPlainText($markdown);

        if (strlen($text) <= $length) {
            return $text;
        }

        // 단어 경계에서 자르기
        $excerpt = substr($text, 0, $length);
        $lastSpace = strrpos($excerpt, ' ');

        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }

        return trim($excerpt) . '...';
    }

    /**
     * 마크다운을 일반 텍스트로 변환
     */
    public function toPlainText(string $markdown): string
    {
        if (empty($markdown)) {
            return '';
        }

        // 마크다운 문법 제거
        $text = $markdown;

        // 헤딩 마크다운 제거
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // 볼드, 이탤릭 마크다운 제거
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/__(.*?)__/', '$1', $text);
        $text = preg_replace('/_(.*?)_/', '$1', $text);

        // 링크 마크다운 제거 (텍스트만 남김)
        $text = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $text);

        // 이미지 마크다운 제거
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '', $text);

        // 코드 마크다운 제거
        $text = preg_replace('/`([^`]*)`/', '$1', $text);
        $text = preg_replace('/```[^`]*```/s', '', $text);

        // 인용 마크다운 제거
        $text = preg_replace('/^>\s*/m', '', $text);

        // 리스트 마크다운 제거
        $text = preg_replace('/^[\*\-\+]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // 여러 줄바꿈을 하나로
        $text = preg_replace('/\n+/', ' ', $text);

        // 여러 공백을 하나로
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
