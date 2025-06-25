<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * 게시물 내용용 이미지 업로드
     */
    public function uploadContentImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $image = $request->file('image');
            $filename = $this->generateUniqueFilename($image);
            $path = $image->storeAs('uploads/posts/content', $filename, 'public');

            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'alt' => $image->getClientOriginalName(),
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '이미지 업로드 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 고유한 파일명 생성
     */
    private function generateUniqueFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        // 파일명이 너무 길면 자르기
        if (strlen($safeName) > 50) {
            $safeName = substr($safeName, 0, 50);
        }

        return $safeName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * 업로드된 이미지 삭제
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $filename = $request->input('filename');
            $path = 'uploads/posts/content/' . $filename;

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return response()->json([
                    'success' => true,
                    'message' => '이미지가 삭제되었습니다.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => '삭제할 이미지를 찾을 수 없습니다.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '이미지 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
