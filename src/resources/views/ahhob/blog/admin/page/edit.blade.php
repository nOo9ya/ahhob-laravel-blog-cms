@extends('blog.admin.layouts.master')

@section('title', '페이지 수정')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">페이지 수정</h1>
                    <div class="flex space-x-2">
                        <a href="{{ route('pages.show', $page->slug) }}" target="_blank"
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            미리보기
                        </a>
                        <a href="{{ route('admin.pages.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            목록으로
                        </a>
                    </div>
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

                <form action="{{ route('admin.pages.update', $page) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- 메인 콘텐츠 영역 -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- 제목 -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">제목 *</label>
                                <input type="text" name="title" id="title" value="{{ old('title', $page->title) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       required>
                            </div>

                            <!-- 슬러그 -->
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">슬러그</label>
                                <input type="text" name="slug" id="slug" value="{{ old('slug', $page->slug) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="자동 생성됩니다">
                                <p class="text-sm text-gray-500 mt-1">URL에 사용될 고유한 식별자입니다. 변경 시 기존 링크가 끊어질 수 있습니다.</p>
                            </div>

                            <!-- 마크다운 에디터 -->
                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">내용 *</label>
                                <textarea name="content" id="content" class="hidden">{{ old('content', $page->content) }}</textarea>
                                <div id="editor"></div>
                            </div>

                            <!-- SEO 섹션 -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO 설정</h3>

                                <!-- 메타 제목 -->
                                <div class="mb-4">
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">메타 제목</label>
                                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $page->meta_title) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="비워두면 제목을 사용합니다">
                                </div>

                                <!-- 메타 설명 -->
                                <div class="mb-4">
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">메타 설명</label>
                                    <textarea name="meta_description" id="meta_description" rows="2"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="검색 엔진에 표시될 설명을 입력하세요">{{ old('meta_description', $page->meta_description) }}</textarea>
                                </div>

                                <!-- 키워드 -->
                                <div class="mb-4">
                                    <label for="keywords" class="block text-sm font-medium text-gray-700 mb-2">키워드</label>
                                    <input type="text" name="keywords" id="keywords" value="{{ old('keywords', $page->keywords) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="키워드를 쉼표로 구분하여 입력하세요">
                                </div>

                                <!-- Open Graph 설정 -->
                                <div class="border-t pt-4">
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Open Graph 설정</h4>
                                    
                                    <div class="mb-4">
                                        <label for="og_title" class="block text-sm font-medium text-gray-700 mb-2">OG 제목</label>
                                        <input type="text" name="og_title" id="og_title" value="{{ old('og_title', $page->og_title) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                               placeholder="소셜 미디어에 표시될 제목">
                                    </div>

                                    <div class="mb-4">
                                        <label for="og_description" class="block text-sm font-medium text-gray-700 mb-2">OG 설명</label>
                                        <textarea name="og_description" id="og_description" rows="2"
                                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                  placeholder="소셜 미디어에 표시될 설명">{{ old('og_description', $page->og_description) }}</textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label for="og_image" class="block text-sm font-medium text-gray-700 mb-2">OG 이미지 URL</label>
                                        <input type="url" name="og_image" id="og_image" value="{{ old('og_image', $page->og_image) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                               placeholder="https://example.com/image.jpg">
                                    </div>
                                </div>

                                <!-- Canonical URL -->
                                <div class="border-t pt-4">
                                    <div>
                                        <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-2">Canonical URL</label>
                                        <input type="url" name="canonical_url" id="canonical_url" value="{{ old('canonical_url', $page->canonical_url) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                               placeholder="정규 URL (선택사항)">
                                        <p class="text-sm text-gray-500 mt-1">중복 콘텐츠 방지를 위한 정규 URL입니다.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 사이드바 -->
                        <div class="space-y-6">
                            <!-- 페이지 정보 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">페이지 정보</h3>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">생성일:</span>
                                        <span class="text-gray-900">{{ $page->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">수정일:</span>
                                        <span class="text-gray-900">{{ $page->updated_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">작성자:</span>
                                        <span class="text-gray-900">{{ $page->user->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">현재 상태:</span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $page->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $page->status_label }}
                                        </span>
                                    </div>
                                    @if($page->published_at)
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">발행일:</span>
                                            <span class="text-gray-900">{{ $page->published_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- 발행 설정 -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">발행 설정</h3>

                                <!-- 상태 -->
                                <div class="mb-4">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">상태</label>
                                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="draft" {{ old('status', $page->status) == 'draft' ? 'selected' : '' }}>임시저장</option>
                                        <option value="published" {{ old('status', $page->status) == 'published' ? 'selected' : '' }}>발행</option>
                                    </select>
                                </div>

                                <!-- 발행일 -->
                                <div class="mb-4">
                                    <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">발행일</label>
                                    <input type="datetime-local" name="published_at" id="published_at"
                                           value="{{ old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <p class="text-sm text-gray-500 mt-1">임시저장 상태에서는 무시됩니다.</p>
                                </div>
                            </div>

                            <!-- 저장 버튼 -->
                            <div class="flex space-x-2">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    수정
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
        // 제목 입력 시 자동으로 슬러그 생성 (수정 모드에서는 제한적으로)
        document.getElementById('title').addEventListener('input', function() {
            const slugField = document.getElementById('slug');
            
            // 슬러그가 비어있거나 기본값과 같을 때만 자동 생성
            if (!slugField.value || slugField.value === slugField.defaultValue) {
                const title = this.value;
                const slug = title
                    .toLowerCase()
                    .replace(/[^a-z0-9가-힣\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
                slugField.value = slug;
            }
        });

        // 상태 변경 시 발행일 필드 토글
        document.getElementById('status').addEventListener('change', function() {
            const publishedAtField = document.getElementById('published_at');
            const publishedAtContainer = publishedAtField.closest('.mb-4');
            
            if (this.value === 'draft') {
                publishedAtContainer.style.opacity = '0.5';
                publishedAtField.disabled = true;
            } else {
                publishedAtContainer.style.opacity = '1';
                publishedAtField.disabled = false;
                
                // 발행일이 비어있으면 현재 시간으로 설정
                if (!publishedAtField.value) {
                    publishedAtField.value = new Date().toISOString().slice(0, 16);
                }
            }
        });

        // 페이지 로드 시 초기 상태 설정
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('status').dispatchEvent(new Event('change'));
        });
    </script>
@endpush