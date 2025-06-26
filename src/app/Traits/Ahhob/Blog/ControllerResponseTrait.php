<?php

namespace App\Traits\Ahhob\Blog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 컨트롤러 응답 표준화 트레이트
 * 
 * API와 웹 응답의 일관성을 유지하고 표준화된 응답 형식을 제공합니다.
 * 성공, 실패, 오류 응답을 일관되게 처리합니다.
 */
trait ControllerResponseTrait
{
    /**
     * 성공 응답 (JSON)
     * 
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * 실패 응답 (JSON)
     * 
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * 페이지네이션 응답 (JSON)
     * 
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @return JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * 컬렉션 응답 (JSON)
     * 
     * @param Collection $collection
     * @param string $message
     * @return JsonResponse
     */
    protected function collectionResponse(Collection $collection, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $collection,
            'count' => $collection->count(),
        ]);
    }

    /**
     * 생성 완료 응답 (JSON)
     * 
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function createdResponse($data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * 업데이트 완료 응답 (JSON)
     * 
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function updatedResponse($data = null, string $message = 'Updated successfully'): JsonResponse
    {
        return $this->successResponse($data, $message);
    }

    /**
     * 삭제 완료 응답 (JSON)
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function deletedResponse(string $message = 'Deleted successfully'): JsonResponse
    {
        return $this->successResponse(null, $message);
    }

    /**
     * 찾을 수 없음 응답 (JSON)
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * 권한 없음 응답 (JSON)
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 접근 금지 응답 (JSON)
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * 유효성 검증 실패 응답 (JSON)
     * 
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * 서버 오류 응답 (JSON)
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * 웹 응답 (리다이렉트)
     * 
     * @param string $route
     * @param string $message
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectWithMessage(string $route, string $message, string $type = 'success')
    {
        return redirect()->route($route)->with($type, $message);
    }

    /**
     * 뒤로가기 응답 (에러 메시지 포함)
     * 
     * @param string $message
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectBackWithError(string $message, string $type = 'error')
    {
        return redirect()->back()->with($type, $message)->withInput();
    }

    /**
     * 뷰 응답 (데이터 포함)
     * 
     * @param string $view
     * @param array $data
     * @return \Illuminate\View\View
     */
    protected function viewResponse(string $view, array $data = [])
    {
        return view($view, $data);
    }

    /**
     * 조건부 응답 (JSON 또는 웹)
     * 
     * @param bool $wantsJson
     * @param mixed $data
     * @param string $view
     * @param array $viewData
     * @param string $message
     * @return JsonResponse|\Illuminate\View\View
     */
    protected function conditionalResponse(
        bool $wantsJson,
        $data = null,
        string $view = '',
        array $viewData = [],
        string $message = 'Success'
    ) {
        if ($wantsJson) {
            return $this->successResponse($data, $message);
        }

        return $this->viewResponse($view, array_merge($viewData, ['data' => $data]));
    }

    /**
     * 파일 다운로드 응답
     * 
     * @param string $filePath
     * @param string $fileName
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    protected function downloadResponse(string $filePath, string $fileName = null, array $headers = [])
    {
        return response()->download($filePath, $fileName, $headers);
    }

    /**
     * 스트림 응답
     * 
     * @param callable $callback
     * @param int $status
     * @param array $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function streamResponse(callable $callback, int $status = 200, array $headers = [])
    {
        return response()->stream($callback, $status, $headers);
    }

    /**
     * 캐시 응답
     * 
     * @param mixed $data
     * @param int $ttl
     * @param string $message
     * @return JsonResponse
     */
    protected function cachedResponse($data, int $ttl = 3600, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse($data, $message)
                    ->header('Cache-Control', "public, max-age={$ttl}")
                    ->header('Expires', now()->addSeconds($ttl)->toRfc2822String());
    }
}