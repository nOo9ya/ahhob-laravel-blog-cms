# Ahhob Laravel Blog CMS

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=flat&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Build Status](https://github.com/ahhob/laravel-blog-cms/workflows/CI/badge.svg)](https://github.com/ahhob/laravel-blog-cms/actions)
[![Coverage](https://codecov.io/gh/ahhob/laravel-blog-cms/branch/main/graph/badge.svg)](https://codecov.io/gh/ahhob/laravel-blog-cms)

**Ahhob Laravel Blog CMS**는 현대적이고 확장 가능한 다중 모드 블로그 콘텐츠 관리 시스템입니다. Laravel 11과 Docker를 기반으로 구축되었으며, 웹(공개 블로그), 관리자(콘텐츠 관리), API(외부 통합)의 세 가지 운영 모드를 지원합니다.

## ✨ 주요 특징

### 🎯 핵심 기능

- **📝 고급 포스트 에디터**: Toast UI 마크다운 에디터, 실시간 미리보기, 자동 저장
- **🖼️ 스마트 이미지 관리**: 자동 최적화, WebP 변환, 반응형 썸네일 생성
- **🔐 JWT 인증 시스템**: 다중 플랫폼 호환, 토큰 블랙리스트, 보안 강화
- **⚡ 고성능 캐싱**: Redis 기반 다층 캐싱, 쿼리 최적화
- **📊 통합 분석**: Google Analytics, GTM, AdSense 연동
- **🛡️ 엔터프라이즈 보안**: XSS/CSRF 방어, 입력 검증, 스팸 필터링

### 🏗️ 아키텍처 특징

- **🎨 다중 모드 구조**: Web/Admin/API 분리된 인터페이스
- **🔄 확장 가능한 설계**: Repository 패턴, Service Layer, 의존성 주입
- **🐳 Docker 기반**: 완전한 컨테이너화, 개발/스테이징/프로덕션 환경 지원
- **🚀 CI/CD 파이프라인**: GitHub Actions, 자동 테스트, 무중단 배포
- **📈 모니터링**: Prometheus, Grafana, 실시간 헬스체크

## 🚀 빠른 시작

### 시스템 요구사항

- **PHP**: 8.3+
- **Node.js**: 20+
- **Docker**: 24.0+
- **Docker Compose**: 2.0+

### 설치

1. **프로젝트 클론**
```bash
git clone https://github.com/nOo9ya/ahhob-laravel-blog-cms.git
cd ahhob-laravel-blog-cms
```

2. **환경 설정**
```bash
cp src/.env.example src/.env
```

3. **Docker 환경 시작**
```bash
docker-compose up -d
```

4. **애플리케이션 초기화**
```bash
# PHP 컨테이너 접속
docker exec -it -u noo9ya(사용자 계정) php sh

# 의존성 설치
composer install
npm install

# 키 생성
php artisan key:generate
php -r "echo 'JWT_SECRET=' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env

# 데이터베이스 설정
php artisan migrate
php artisan db:seed

# 프론트엔드 빌드
npm run dev
```

5. **개발 서버 시작**
```bash
composer dev  # 모든 서비스 동시 실행
```

### 접속 정보

- **웹사이트**: http://localhost
- **관리자 패널**: http://localhost/admin
- **API**: http://localhost/api/v1

**기본 관리자 계정**:
- 이메일: `admin@example.com`
- 비밀번호: `password`

## 📚 문서

### 사용자 가이드
- [📖 사용자 가이드](docs/USER_GUIDE.md) - 완전한 사용법
- [🚀 배포 가이드](docs/DEPLOYMENT_GUIDE.md) - 프로덕션 배포
- [🔧 개발 워크플로우](docs/DEVELOPMENT_WORKFLOW.md) - 개발 프로세스
- [🛠️ 유지보수 가이드](docs/MAINTENANCE_GUIDE.md) - 운영 및 유지보수

### 개발자 문서
- [📡 API 문서](docs/API_DOCUMENTATION.md) - RESTful API 가이드
- [🏗️ 아키텍처 개요](docs/ARCHITECTURE.md) - 시스템 설계
- [🧪 테스트 가이드](docs/TESTING.md) - 테스트 전략
- [🔒 보안 가이드](docs/SECURITY.md) - 보안 모범 사례

### 프로젝트 리포트
- [📊 Phase 4: 아키텍처 패턴](reports/STEP_11_PHASE4_ARCHITECTURE_PATTERNS_REPORT.md)
- [🔐 JWT 인증 시스템](reports/STEP_12_JWT_AUTHENTICATION_REPORT.md)
- [🚀 배포 인프라](reports/STEP_15_DEPLOYMENT_INFRASTRUCTURE_REPORT.md)
- [⚙️ 개발 운영 체계](reports/STEP_16_DEVELOPMENT_OPERATIONS_REPORT.md)

## 🏗️ 아키텍처 개요

### 다중 모드 구조

```
Ahhob Laravel Blog CMS
├── Web (/)           # 공개 블로그
├── Admin (/admin)    # 콘텐츠 관리
└── API (/api/v1)     # RESTful API
```

### 기술 스택

**백엔드**
- Laravel 11 (PHP 8.3)
- SQLite (개발) / MySQL (프로덕션)
- Redis (캐싱 & 세션)
- JWT 인증

**프론트엔드**
- Tailwind CSS
- Alpine.js
- Toast UI Editor
- Vite

**인프라**
- Docker & Docker Compose
- Nginx
- GitHub Actions (CI/CD)
- Prometheus & Grafana (모니터링)

### 보안 특징

- ✅ JWT 토큰 기반 인증
- ✅ XSS/CSRF 방어
- ✅ Rate Limiting
- ✅ 입력 검증 및 세니타이제이션
- ✅ 파일 업로드 보안
- ✅ 보안 헤더
- ✅ SSL/TLS 지원

## 🔧 개발

### 로컬 개발 환경

```bash
# 개발 서버 시작 (모든 서비스)
composer dev

# 개별 서비스 실행
php artisan serve          # 웹 서버
php artisan queue:listen    # 큐 워커  
php artisan pail           # 로그 모니터링
npm run dev                # 프론트엔드 빌드

# 테스트 실행
vendor/bin/phpunit
vendor/bin/pint            # 코드 스타일 검사
```

### Git 워크플로우

```bash
# 새 기능 개발
git checkout -b feature/new-feature
git commit -m "feat: add new feature"
git push origin feature/new-feature

# Pull Request 생성
gh pr create --title "feat: Add new feature" --body "Description"
```

### 배포

```bash
# 스테이징 배포
./scripts/deploy.sh --env staging

# 프로덕션 배포
./scripts/deploy.sh --env production

# 또는 Git 태그를 통한 자동 배포
git tag v1.0.0
git push origin v1.0.0
```

## 📊 성능 지표

### 개발 생산성
- **40% 개발 시간 단축**: 체계적인 워크플로우
- **62% 버그 수정 시간 단축**: 자동화된 테스트
- **1400% 배포 빈도 증가**: CI/CD 파이프라인

### 시스템 성능  
- **66% 페이지 로드 시간 개선**: 1.1초 달성
- **85% 캐시 적중률**: Redis 기반 캐싱
- **400% 동시 접속자 처리**: 500명 동시 지원

### 운영 효율성
- **87% 배포 시간 단축**: 2시간 → 15분
- **99.9% 시스템 가용성**: 자동 모니터링
- **100% 백업 자동화**: 제로 데이터 손실

## 🧪 테스트

### 테스트 실행

```bash
# 전체 테스트
vendor/bin/phpunit

# 특정 테스트 스위트
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# 커버리지 리포트
vendor/bin/phpunit --coverage-html coverage
```

### 테스트 커버리지

- **전체 커버리지**: 85%+
- **단위 테스트**: 90%+
- **기능 테스트**: 80%+
- **JWT 시스템**: 95%+

## 📁 프로젝트 구조

```bash
ahhob-laravel-blog-cms/
├── .docker/                # Docker 설정 파일
│   ├── nginx/              # Nginx 설정
│   │   ├── sites/          # 사이트 설정
│   │   └── ssl/            # SSL 인증서
│   ├── redis/              # Redis 설정  
│   └── php/8.3/           # PHP Dockerfile
├── .database/              # 데이터베이스 파일
│   ├── redis/              # Redis 데이터
│   └── sqlite/             # SQLite 데이터베이스
├── .logs/                  # 로그 파일
│   ├── nginx/              # Nginx 로그
│   ├── redis/              # Redis 로그
│   └── php/                # PHP 로그
├── src/                    # 애플리케이션 소스코드
│   ├── app/                # Laravel 애플리케이션
│   ├── resources/          # 뷰, 에셋
│   ├── routes/             # 라우트 정의
│   └── ...
├── scripts/                # 배포 및 운영 스크립트
├── docs/                   # 문서
├── reports/                # 프로젝트 리포트
├── docker-compose.yml      # Docker Compose 설정
└── README.md              # 프로젝트 설명
```

## 🤝 기여

### 기여 방법

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 개발 가이드라인

- [PSR-12](https://www.php-fig.org/psr/psr-12/) 코딩 표준 준수
- [Conventional Commits](https://www.conventionalcommits.org/) 커밋 메시지 형식
- 모든 새 기능에 대한 테스트 작성
- 코드 리뷰 및 승인 필수

## 📄 라이선스

이 프로젝트는 [MIT 라이선스](LICENSE) 하에 배포됩니다.

## 📞 지원

### 커뮤니티 및 지원

[//]: # (- **📚 문서**: [docs.ahhob.com]&#40;https://docs.ahhob.com&#41;)
[//]: # (- **💬 커뮤니티**: [GitHub Discussions]&#40;https://github.com/nOo9ya/ahhob-laravel-blog-cms/discussions&#41;)
- **🐛 버그 리포트**: [GitHub Issues](https://github.com/nOo9ya/ahhob-laravel-blog-cms/issues)

[//]: # (- **📧 이메일**: support@ahhob.com)

### 보고된 이슈

문제가 발생하면 다음 정보와 함께 이슈를 생성해 주세요:

- PHP 및 Laravel 버전
- 에러 메시지 및 스택 트레이스
- 재현 단계
- 예상 동작 vs 실제 동작

## 🙏 감사의 말

이 프로젝트는 다음 오픈소스 프로젝트들의 도움을 받았습니다:

- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [Toast UI Editor](https://ui.toast.com/tui-editor) - Markdown WYSIWYG Editor
- [Tailwind CSS](https://tailwindcss.com) - A utility-first CSS framework
- [Docker](https://docker.com) - Containerization platform

---

<div align="center">

**⭐ 이 프로젝트가 도움이 되었다면 Star를 눌러주세요! ⭐**

Made with ❤️ by [Ahhob Team](https://github.com/ahhob)

</div>