<?php

namespace App\Http\Controllers\Ahhob\Blog\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ahhob\Blog\Web\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Registration Controller
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * (기존 showRegister() 메소드)
     */
    public function create(): View
    {
        return view('web.auth.register');
    }

    /**
     * Handle an incoming registration request.
     * (기존 register() 메소드)
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'is_active' => true,
        ]);

        Auth::login($user);

        return redirect()->route('home')
            ->with('success', '회원가입이 완료되었습니다.');
    }
}
