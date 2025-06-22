<?php

namespace App\Http\Controllers\Ahhob\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Auth\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Profile Controller
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     * (기존 profile() 메소드)
     */
    public function edit(): View
    {
        return view('web.auth.profile', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Update the user's profile information.
     * (기존 updateProfile() 메소드)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // 아바타 이미지 업로드 처리
        if ($request->hasFile('avatar')) {
            // 기존 아바타 삭제 (Storage Facade 사용을 권장하지만 기존 로직 유지)
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $avatarPath = $request->file('avatar')->store('uploads/avatars', 'public');
            $data['avatar'] = 'storage/' . $avatarPath;
        }

        // 비밀번호 변경이 있는 경우
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', '프로필이 업데이트되었습니다.');
    }
}
