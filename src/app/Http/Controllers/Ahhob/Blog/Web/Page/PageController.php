<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Page;

use App\Http\Controllers\Controller;
use App\Models\Blog\Page;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * 페이지 상세 보기
     */
    public function show(Page $page): View|Response
    {
        // 발행되지 않은 페이지는 404
        if (!$page->isPublished()) {
            abort(404);
        }

        // 페이지 로드 (작성자 정보 포함)
        $page->load('user');

        return view('web.page.show', compact('page'));
    }
}