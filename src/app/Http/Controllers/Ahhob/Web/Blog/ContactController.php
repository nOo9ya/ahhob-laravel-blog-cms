<?php

namespace App\Http\Controllers\Ahhob\Web\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('web.home.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        // todo : send email logic
        // Mail::to(config('mail.contact_email'))->send(new ContactMail($request->all()));

        return back()->with('success', '문의사항이 성공적으로 전송되었습니다.');
    }
}
