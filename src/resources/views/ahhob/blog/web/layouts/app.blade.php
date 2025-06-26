<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', config('app.name', 'Blog'))</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    
    <!-- Additional Meta Tags -->
    @stack('meta')
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    
    <!-- Google Tag Manager -->
    @include('blog.shared.analytics.google-tag-manager')
    
    <!-- Google Analytics -->
    @include('blog.shared.analytics.google-analytics')
</head>
<body class="bg-gray-50 text-gray-900">
    <!-- Google Tag Manager (noscript) -->
    @include('blog.shared.analytics.google-tag-manager-noscript')
    
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 hover:text-blue-600">
                        {{ config('app.name', 'Blog') }}
                    </a>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 {{ request()->routeIs('home') ? 'text-blue-600 font-medium' : '' }}">
                        홈
                    </a>
                    <a href="{{ route('posts.index') }}" class="text-gray-700 hover:text-blue-600 {{ request()->routeIs('posts.*') ? 'text-blue-600 font-medium' : '' }}">
                        블로그
                    </a>
                    <a href="{{ route('categories.index') }}" class="text-gray-700 hover:text-blue-600 {{ request()->routeIs('categories.*') ? 'text-blue-600 font-medium' : '' }}">
                        카테고리
                    </a>
                    <a href="{{ route('about') }}" class="text-gray-700 hover:text-blue-600 {{ request()->routeIs('about') ? 'text-blue-600 font-medium' : '' }}">
                        소개
                    </a>
                    <a href="{{ route('contact') }}" class="text-gray-700 hover:text-blue-600 {{ request()->routeIs('contact') ? 'text-blue-600 font-medium' : '' }}">
                        연락처
                    </a>
                </div>
                
                <!-- Search -->
                <div class="flex items-center">
                    <form action="{{ route('posts.search') }}" method="GET" class="hidden sm:flex items-center">
                        <input type="text" name="q" value="{{ request('q') }}" 
                               placeholder="검색..." 
                               class="w-64 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" class="ml-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </form>
                    
                    <!-- Auth Links -->
                    <div class="ml-4 flex items-center space-x-4">
                        @auth
                            <a href="{{ route('auth.profile.edit') }}" class="text-gray-700 hover:text-blue-600">
                                프로필
                            </a>
                            <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-700 hover:text-blue-600">
                                    로그아웃
                                </button>
                            </form>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-gray-700 hover:text-blue-600">
                                로그인
                            </a>
                            <a href="{{ route('auth.register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                회원가입
                            </a>
                        @endauth
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" class="text-gray-700 hover:text-gray-900" id="mobile-menu-button">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
                <a href="{{ route('home') }}" class="block px-3 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('home') ? 'text-blue-600 font-medium' : '' }}">
                    홈
                </a>
                <a href="{{ route('posts.index') }}" class="block px-3 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('posts.*') ? 'text-blue-600 font-medium' : '' }}">
                    블로그
                </a>
                <a href="{{ route('categories.index') }}" class="block px-3 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('categories.*') ? 'text-blue-600 font-medium' : '' }}">
                    카테고리
                </a>
                <a href="{{ route('about') }}" class="block px-3 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('about') ? 'text-blue-600 font-medium' : '' }}">
                    소개
                </a>
                <a href="{{ route('contact') }}" class="block px-3 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('contact') ? 'text-blue-600 font-medium' : '' }}">
                    연락처
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="min-h-screen">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mx-4 mt-4 rounded" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-4 mt-4 rounded" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-4 mt-4 rounded" role="alert">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <!-- Page Content -->
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">{{ config('app.name', 'Blog') }}</h3>
                    <p class="text-gray-300 mb-4">
                        Laravel 기반의 현대적인 블로그 플랫폼입니다. 
                        최신 기술과 사용자 친화적인 디자인으로 구축되었습니다.
                    </p>
                    <div class="flex space-x-4">
                        <!-- Social Links -->
                        <a href="#" class="text-gray-300 hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.024-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.098.119.112.224.083.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001.012.001z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">빠른 링크</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white">홈</a></li>
                        <li><a href="{{ route('posts.index') }}" class="text-gray-300 hover:text-white">블로그</a></li>
                        <li><a href="{{ route('categories.index') }}" class="text-gray-300 hover:text-white">카테고리</a></li>
                        <li><a href="{{ route('about') }}" class="text-gray-300 hover:text-white">소개</a></li>
                        <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white">연락처</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">법적 고지</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('pages.show', 'privacy-policy') }}" class="text-gray-300 hover:text-white">개인정보 처리방침</a></li>
                        <li><a href="{{ route('pages.show', 'terms-of-service') }}" class="text-gray-300 hover:text-white">이용약관</a></li>
                        <li><a href="{{ route('sitemap') }}" class="text-gray-300 hover:text-white">사이트맵</a></li>
                        <li><a href="{{ route('feed') }}" class="text-gray-300 hover:text-white">RSS 피드</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-700 text-center">
                <p class="text-gray-300">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Blog') }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Cookie Consent -->
    @include('blog.shared.analytics.cookie-consent')
    
    <!-- Scripts -->
    @stack('scripts')
    
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Search form submission tracking
        document.querySelector('form[action*="search"]')?.addEventListener('submit', function(e) {
            const searchTerm = e.target.querySelector('input[name="q"]').value;
            if (window.trackSearch) {
                window.trackSearch(searchTerm);
            }
            if (window.gtmTrackSearch) {
                window.gtmTrackSearch(searchTerm);
            }
        });

        // Close flash messages
        document.querySelectorAll('[role="alert"]').forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    </script>
</body>
</html>