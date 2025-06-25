@extends('blog.admin.layouts.master')

@section('title', '게시물 작성')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">게시물 작성</h1>
                    <a href="{{ route('admin.posts.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        목록으로
                    </a>
                </div>

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- 메인 콘텐츠 영역 -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- 제목 -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">제목 *</label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                            </div>

                            <!-- 슬러그 -->
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">슬러그</label>
                                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="자동 생성됩니다">
                            </div>

                            <!-- 요약 -->
                            <div>
                                <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">요약</label>
                                <textarea name="excerpt" id="excerpt" rows="3"
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="게시물의 간단한 요약을 입력하세요">{{ old('excerpt') }}</textarea>
                            </div>

                            <!-- 마크다운 에디터 -->
                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">내용 *</label>
                                <textarea name="content" id="content" class="hidden">{{ old('content') }}</textarea>
                                <div id="editor"></div>
                            </div>

                            <!-- SEO 섹션 -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO 설정</h3>

                                <!-- 메타 제목 -->
                                <div class="mb-4">
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">메타 제목</label>
                                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="비워두면 제목을 사용합니다">
                                </div>

                                <!-- 메타 설명 -->
                                <div class="mb-4">
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">메타 설명</label>
                                    <textarea name="meta_description" id="meta_description" rows="2"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="검색 엔진에 표시될 설명을 입력하세요">{{ old('meta_description') }}</textarea>
                                </div>

                                <!-- 키워드 -->
                                <div>
                                    <label for="keywords" class="block text-sm font-medium text-gray-700 mb-2">키워드</label>
                                    <input type="text" name="keywords" id="keywords" value="{{ old('keywords') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="키워드를 쉼표로 구분하여 입력하세요">
                                </div>
                            </div>
                        </div>

                        <!-- 사이드바 -->
                        <div class="space-y-6">
                            <!-- 발행 설정 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">발행 설정</h3>

                                <!-- 상태 -->
                                <div class="mb-4">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">상태</label>
                                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>임시저장</option>
                                        <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>발행</option>
                                    </select>
                                </div>

                                <!-- 발행일 -->
                                <div class="mb-4">
                                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">발행일</label>
                                    <input type="datetime-local" name="published_at" id="published_at"
                                           value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- 핀 고정 -->
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">상단 고정</span>
                                    </label>
                                </div>

                                <!-- 댓글 허용 -->
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="comments_enabled" value="1" {{ old('comments_enabled', true) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">댓글 허용</span>
                                    </label>
                                </div>
                            </div>

                            <!-- 카테고리 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">카테고리</h3>
                                <select name="category_id" id="category_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">카테고리 선택</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 태그 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">태그</h3>
                                <input type="text" name="tags" id="tags" value="{{ old('tags') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="태그를 쉼표로 구분하여 입력하세요">
                            </div>

                            <!-- 썸네일 이미지 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">썸네일 이미지</h3>
                                <input type="file" name="featured_image" id="featured_image" accept="image/*"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>

                            <!-- OG 이미지 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">OG 이미지</h3>
                                <input type="file" name="og_image" id="og_image" accept="image/*"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>

                            <!-- 저장 버튼 -->
                            <div class="flex space-x-2">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    저장
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/post-editor.js')
    <script>
        // 제목 입력 시 자동으로 슬러그 생성
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title
                .toLowerCase()
                .replace(/[^a-z0-9가-힣\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.getElementById('slug').value = slug;
        });
    </script>
@endpush
