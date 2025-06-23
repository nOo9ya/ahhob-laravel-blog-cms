<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Password Reset Link Controller
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     * (기존 showForgotPassword() 메소드)
     */
    public function create(): View
    {
        return view('web.auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     * (기존 sendResetLink() 메소드)
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
