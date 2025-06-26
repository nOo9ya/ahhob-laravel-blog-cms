<?php

namespace App\Traits\Blog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * 컨트롤러 응답 처리 트레이트
 * 
 * 이 트레이트는 모든 블로그 컨트롤러에서 공통적으로 사용되는
 * 응답 처리 로직을 중앙화합니다.
 * 
 * 주요 기능:
 * - 성공/실패 JSON 응답 생성
 * - 리다이렉트 응답 생성
 * - 에러 처리 및 로깅
 * - 표준화된 응답 형식
 * 
 * 사용법:
 * class PostController extends Controller {
 *     use ControllerResponseTrait;
 *     
 *     public function store() {
 *         return $this->successResponse('게시물이 생성되었습니다.', $post);
 *     }
 * }
 */
trait ControllerResponseTrait
{
    /**
     * 성공 JSON 응답 생성
     * 
     * @param string $message 성공 메시지
     * @param mixed $data 반환할 데이터
     * @param int $statusCode HTTP 상태 코드
     * @return JsonResponse
     */
    protected function successResponse(string $message, $data = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 실패 JSON 응답 생성
     * 
     * @param string $message 에러 메시지
     * @param array $errors 상세 에러 목록
     * @param int $statusCode HTTP 상태 코드
     * @return JsonResponse
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 유효성 검사 실패 JSON 응답 생성
     * 
     * @param array $errors 유효성 검사 에러
     * @param string $message 기본 메시지
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = '입력 데이터에 오류가 있습니다.'): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * 성공 리다이렉트 응답 생성
     * 
     * @param string $route 리다이렉트할 라우트
     * @param string $message 성공 메시지
     * @param array $parameters 라우트 매개변수
     * @return RedirectResponse
     */
    protected function successRedirect(string $route, string $message, array $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * 실패 리다이렉트 응답 생성 (이전 페이지로)
     * 
     * @param string $message 에러 메시지
     * @param array $errors 상세 에러 목록
     * @return RedirectResponse
     */
    protected function errorRedirect(string $message, array $errors = []): RedirectResponse
    {
        $redirect = back()->with('error', $message);

        if (!empty($errors)) {
            $redirect->with('errors', $errors);
        }

        return $redirect;
    }

    /**
     * 자원 생성 성공 응답 (Create 작업용)
     * 
     * @param string $resource 자원 이름 (예: '게시물', '카테고리')
     * @param string $route 리다이렉트할 라우트
     * @param mixed $model 생성된 모델 (라우트 매개변수용)
     * @return RedirectResponse
     */
    protected function resourceCreated(string $resource, string $route, $model = null): RedirectResponse
    {
        $parameters = $model ? [$model] : [];
        return $this->successRedirect($route, "{$resource}이(가) 성공적으로 생성되었습니다.", $parameters);
    }

    /**
     * 자원 수정 성공 응답 (Update 작업용)
     * 
     * @param string $resource 자원 이름 (예: '게시물', '카테고리')
     * @param string $route 리다이렉트할 라우트
     * @param mixed $model 수정된 모델 (라우트 매개변수용)
     * @return RedirectResponse
     */
    protected function resourceUpdated(string $resource, string $route, $model = null): RedirectResponse
    {
        $parameters = $model ? [$model] : [];
        return $this->successRedirect($route, "{$resource}이(가) 성공적으로 수정되었습니다.", $parameters);
    }

    /**
     * 자원 삭제 성공 응답 (Delete 작업용)
     * 
     * @param string $resource 자원 이름 (예: '게시물', '카테고리')
     * @param string $route 리다이렉트할 라우트
     * @param array $parameters 라우트 매개변수
     * @return RedirectResponse
     */
    protected function resourceDeleted(string $resource, string $route, array $parameters = []): RedirectResponse
    {
        return $this->successRedirect($route, "{$resource}이(가) 성공적으로 삭제되었습니다.", $parameters);
    }

    /**
     * 권한 없음 응답
     * 
     * @param string $message 커스텀 메시지
     * @return JsonResponse|RedirectResponse
     */
    protected function unauthorizedResponse(string $message = '이 작업을 수행할 권한이 없습니다.')
    {
        if (request()->expectsJson()) {
            return $this->errorResponse($message, [], 403);
        }

        return $this->errorRedirect($message);
    }

    /**
     * 자원을 찾을 수 없음 응답
     * 
     * @param string $resource 자원 이름
     * @return JsonResponse|RedirectResponse
     */
    protected function notFoundResponse(string $resource = '요청한 자원')
    {
        $message = "{$resource}을(를) 찾을 수 없습니다.";

        if (request()->expectsJson()) {
            return $this->errorResponse($message, [], 404);
        }

        return $this->errorRedirect($message);
    }

    /**
     * 서버 오류 응답 처리
     * 
     * @param \Exception $exception 발생한 예외
     * @param string $operation 수행 중이던 작업 (예: '게시물 생성')
     * @return JsonResponse|RedirectResponse
     */
    protected function serverErrorResponse(\Exception $exception, string $operation = '작업 처리')
    {
        // 예외 로깅
        logger()->error("Controller error during {$operation}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);

        $message = "{$operation} 중 오류가 발생했습니다.";
        
        // 개발 환경에서는 상세 오류 표시
        if (app()->environment('local')) {
            $message .= ' (' . $exception->getMessage() . ')';
        }

        if (request()->expectsJson()) {
            return $this->errorResponse($message, [], 500);
        }

        return $this->errorRedirect($message);
    }

    /**
     * 파일 크기 포맷팅 (바이트 -> 읽기 쉬운 형태)
     * 
     * @param int $bytes 바이트 크기
     * @return string 변환된 크기 문자열 (예: "1.5 MB")
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 벌크 작업 결과 메시지 생성
     * 
     * @param string $action 수행한 작업 (예: '삭제', '발행')
     * @param array $result 작업 결과 (success, failed 키 포함)
     * @return string 결과 메시지
     */
    protected function getBulkActionMessage(string $action, array $result): string
    {
        $successCount = $result['success'] ?? 0;
        $failedCount = $result['failed'] ?? 0;

        if ($failedCount > 0) {
            return "{$successCount}개 항목이 {$action}되었습니다. ({$failedCount}개 실패)";
        }

        return "{$successCount}개 항목이 {$action}되었습니다.";
    }
}