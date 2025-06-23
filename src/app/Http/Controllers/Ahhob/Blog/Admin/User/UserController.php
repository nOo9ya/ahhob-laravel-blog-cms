<?php

namespace App\Http\Controllers\Ahhob\Blog\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $query = User::query();

        // 역할 필터
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // 상태 필터
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // 검색
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('username', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // 정렬
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'email':
                $query->orderBy('email');
                break;
            case 'role':
                $query->orderBy('role');
                break;
            case 'last_login':
                $query->orderBy('last_login_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $users = $query->withCount(['posts', 'comments'])->paginate(20);

        $filters = $request->only(['role', 'status', 'search', 'sort']);

        // 통계
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'writers' => User::where('role', 'writer')->count(),
            'users' => User::where('role', 'user')->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.user.index', compact('users', 'filters', 'stats'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.user.create');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => 'required|in:user,writer,admin',
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        return redirect()->route('admin.users.show', $user)
            ->with('success', '사용자가 성공적으로 생성되었습니다.');
    }

    /**
     * @param User $user
     * @return View
     */
    public function show(User $user): View
    {
        $user->load(['posts.category', 'comments.post']);

        // 사용자 활동 통계
        $stats = [
            'total_posts' => $user->posts()->count(),
            'published_posts' => $user->posts()->where('status', 'published')->count(),
            'draft_posts' => $user->posts()->where('status', 'draft')->count(),
            'total_comments' => $user->comments()->count(),
            'approved_comments' => $user->comments()->where('status', 'approved')->count(),
            'total_views' => $user->posts()->sum('views_count'),
            'total_likes' => $user->posts()->sum('likes_count'),
        ];

        // 최근 게시물
        $recentPosts = $user->posts()
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 최근 댓글
        $recentComments = $user->comments()
            ->with('post')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.user.show', compact('user', 'stats', 'recentPosts', 'recentComments'));
    }

    /**
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        return view('admin.user.edit', compact('user'));
    }

    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id . '|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => 'required|in:user,writer,admin',
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'social_twitter' => 'nullable|string|max:50',
            'social_github' => 'nullable|string|max:50',
            'social_linkedin' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->except(['password', 'password_confirmation']);

        // 비밀번호 변경이 있는 경우
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        return redirect()->route('admin.users.show', $user)
            ->with('success', '사용자 정보가 성공적으로 업데이트되었습니다.');
    }

    /**
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        // 자기 자신은 삭제할 수 없음
        if ($user->id === auth()->id()) {
            return back()->with('error', '자기 자신은 삭제할 수 없습니다.');
        }

        // 게시물이 있는 사용자는 삭제 확인
        if ($user->posts()->count() > 0) {
            return back()->with('error', '게시물이 있는 사용자는 삭제할 수 없습니다. 먼저 게시물을 삭제하거나 다른 사용자에게 이전하세요.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "사용자 '{$name}'이(가) 성공적으로 삭제되었습니다.");
    }
}

