<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Admin\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // 관리자 권한 확인
            if (!$user->isWriter()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => '관리자 권한이 없습니다.',
                ]);
            }

            $request->session()->regenerate();

            // 최종 로그인 시간 업데이트
            $user->update(['last_login_at' => now()]);

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', '관리자 페이지에 로그인되었습니다.');
        }

        return back()->withErrors([
            'email' => '이메일 또는 비밀번호가 올바르지 않습니다.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.auth.login')
            ->with('success', '로그아웃되었습니다.');
    }
}
