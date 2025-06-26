<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Page;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Admin\Page\PageRequest;
use App\Models\Blog\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * 페이지 목록 표시
     */
    public function index(Request $request): View
    {
        $query = Page::with('user');

        // 상태 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 검색
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // 정렬
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        if (in_array($sortBy, ['title', 'status', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $pages = $query->paginate(15);
        
        $filters = $request->only(['status', 'search', 'sort', 'sort_dir']);

        return view('admin.page.index', compact('pages', 'filters'));
    }

    /**
     * 새 페이지 생성 폼 표시
     */
    public function create(): View
    {
        return view('admin.page.create');
    }

    /**
     * 새 페이지 저장
     */
    public function store(PageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $page = Page::create($data);

        return redirect()->route('admin.pages.index')
            ->with('success', '페이지가 성공적으로 생성되었습니다.');
    }

    /**
     * 페이지 상세 보기
     */
    public function show(Page $page): View
    {
        $page->load('user');

        return view('admin.page.show', compact('page'));
    }

    /**
     * 페이지 수정 폼 표시
     */
    public function edit(Page $page): View
    {
        return view('admin.page.edit', compact('page'));
    }

    /**
     * 페이지 업데이트
     */
    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        
        $page->update($data);

        return redirect()->route('admin.pages.index')
            ->with('success', '페이지가 성공적으로 업데이트되었습니다.');
    }

    /**
     * 페이지 삭제
     */
    public function destroy(Page $page): RedirectResponse
    {
        $title = $page->title;
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', "페이지 '{$title}'이(가) 성공적으로 삭제되었습니다.");
    }

    /**
     * 페이지 상태 변경 (발행/임시저장)
     */
    public function toggleStatus(Page $page): RedirectResponse
    {
        $newStatus = $page->status === 'published' ? 'draft' : 'published';
        
        $page->update([
            'status' => $newStatus,
            'published_at' => $newStatus === 'published' ? now() : null,
        ]);

        $statusLabel = $newStatus === 'published' ? '발행' : '임시저장';
        
        return redirect()->back()
            ->with('success', "페이지 상태가 '{$statusLabel}'으로 변경되었습니다.");
    }
}