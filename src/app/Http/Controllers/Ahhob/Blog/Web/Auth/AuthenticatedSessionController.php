<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Web\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login / Logout Session Controller
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     * (기존 showLogin() 메소드)
     */
    public function create(): View
    {
        return view('web.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * (기존 login() 메소드)
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // 최종 로그인 시간 업데이트
            Auth::user()->update(['last_login_at' => now()]);

            return redirect()->intended(route('home'))
                ->with('success', '로그인되었습니다.');
        }

        return back()->withErrors([
            'email' => '이메일 또는 비밀번호가 올바르지 않습니다.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     * (기존 logout() 메소드)
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', '로그아웃되었습니다.');
    }
}
