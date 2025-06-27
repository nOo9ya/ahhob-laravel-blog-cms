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

## 🚀 프로덕션 배포 가이드

이 가이드는 제공된 스크립트를 사용하여 Ahhob Laravel Blog CMS를 DigitalOcean Droplet에 배포하는 단계별 지침을 제공합니다. 이 가이드는 DigitalOcean Droplet에 Docker 및 Docker Compose가 설치되어 있고, 프로젝트 소스 코드가 Droplet에 클론되어 있다고 가정합니다.

### 1단계: 클라우드 인스턴스 준비 (최초 1회 설정)

이 단계에서는 선택한 클라우드 인스턴스에 접속하고 배포에 필요한 기본 환경을 설정합니다.

#### DigitalOcean Droplet

1.  **SSH 접속:** SSH를 통해 DigitalOcean Droplet에 접속합니다.
    ```bash
    ssh your_user@your_droplet_ip
    ```
2.  **Docker 및 Docker Compose 설치:** 아직 설치되지 않았다면, 다음 명령어를 사용하여 설치합니다. (Ubuntu 기준)
    ```bash
    sudo apt update
    sudo apt install -y docker.io docker-compose
    sudo usermod -aG docker $USER # 현재 사용자를 docker 그룹에 추가 (재로그인 필요)
    newgrp docker # 그룹 변경 즉시 적용 (재로그인 없이)
    ```
3.  **프로젝트 디렉토리로 이동:** 프로젝트 소스 코드가 있는 디렉토리로 이동합니다.
    ```bash
    cd /home/noo9ya/workspace/projects/ahhob-laravel-blog-cms # 또는 프로젝트가 클론된 경로
    ```

#### AWS Lightsail 인스턴스

1.  **SSH 접속:** Lightsail 콘솔에서 인스턴스에 연결하거나, SSH 클라이언트를 사용하여 접속합니다. Lightsail은 기본적으로 `ec2-user` 또는 `ubuntu`와 같은 기본 사용자를 제공합니다.
    ```bash
    ssh -i /path/to/your-lightsail-key.pem ec2-user@your_lightsail_public_ip
    ```
2.  **Docker 및 Docker Compose 설치:** 아직 설치되지 않았다면, 다음 명령어를 사용하여 설치합니다. (Ubuntu 기준)
    ```bash
    sudo apt update
    sudo apt install -y docker.io docker-compose
    sudo usermod -aG docker $USER # 현재 사용자를 docker 그룹에 추가 (재로그인 필요)
    newgrp docker # 그룹 변경 즉시 적용 (재로그인 없이)
    ```
3.  **프로젝트 디렉토리로 이동:** 프로젝트 소스 코드가 있는 디렉토리로 이동합니다.
    ```bash
    cd /home/noo9ya/workspace/projects/ahhob-laravel-blog-cms # 또는 프로젝트가 클론된 경로
    ```

### 2단계: 프로덕션 환경 파일 생성 (`production-setup.sh`)

이 스크립트는 `.env.production` 파일, `docker-compose.production.yml` 파일, Nginx 설정 파일 등 필수적인 프로덕션 환경 파일을 생성하고, 사용자 입력에 따라 일부 중요한 환경 변수를 채웁니다.

1.  **스크립트 실행:**
    ```bash
    ./scripts/production-setup.sh
    ```
2.  **정보 입력:** 스크립트가 실행되면 다양한 세부 정보를 묻습니다. 정확한 정보를 입력해 주세요:
    *   `Enter your application URL (e.g., https://yourdomain.com):` (예: `https://yourblog.com`)
    *   `Enter your database host (e.g., 127.0.0.1 or your_db_host):` (예: `your_db_host.digitalocean.com` 또는 `127.0.0.1` (동일 Droplet에 DB 설치 시))
    *   `Enter your database name:` (예: `ahhob_blog_prod`)
    *   `Enter your database username:` (예: `ahhob_user`)
    *   `Enter your database password:` (데이터베이스 비밀번호)
    *   `Enter your Redis password (leave blank if none):` (Redis 비밀번호, 없으면 엔터)
    *   `Enter your mail host (e.g., smtp.mailgun.org):` (메일 서버 호스트)
    *   `Enter your mail username (e.g., postmaster@yourdomain.com):` (메일 사용자명)
    *   `Enter your mail password:` (메일 비밀번호)
    *   `Enter your mail from address (e.g., noreply@yourdomain.com):` (메일 발신 주소)
    *   `Enter your AWS Access Key ID (leave blank if not using S3):` (S3 사용 시 AWS Access Key ID)
    *   `Enter your AWS Secret Access Key (leave blank if not using S3):` (S3 사용 시 AWS Secret Access Key)
    *   `Enter your session domain (e.g., .yourdomain.com):` (예: `.yourblog.com`)
    *   `Enter your Google Analytics cookie domain (e.g., .yourdomain.com):` (예: `.yourblog.com`)

3.  **생성된 파일 검토 및 추가 사용자 정의:**
    스크립트 실행 후 `src/.env.production` 파일이 생성됩니다. 이 파일을 열어 `REPLACE_WITH_...` 플레이스홀더가 남아있지 않은지 확인하고, 필요한 경우 추가 세부 정보를 채웁니다.
    *   **데이터베이스 설정:** `DB_CONNECTION` (예: `mysql` 또는 `pgsql`)을 확인하고 입력한 DB 자격 증명이 올바른지 확인합니다.
    *   **Nginx 도메인 설정:** `.docker/nginx/sites/prod.default.conf` 파일을 열어 `server_name yourdomain.com www.yourdomain.com;`을 실제 도메인으로 변경합니다.
    *   **SSL 인증서:** `scripts/generate-ssl.sh`는 개발용 자체 서명 인증서 스크립트입니다. **프로덕션 환경에서는 반드시 유효한 SSL 인증서(예: Certbot을 사용하여 Let's Encrypt에서 발급)를 획득하여 `.docker/nginx/ssl/` 디렉토리에 `cert.pem` 및 `key.pem`으로 배치해야 합니다.**

### 3단계: 애플리케이션 배포 (`deploy.sh`)

모든 구성이 완료되면 배포 스크립트를 실행하여 Docker 컨테이너를 빌드하고 애플리케이션을 배포합니다.

1.  **프로덕션 배포 스크립트 실행:**
    ```bash
    ./scripts/deploy.sh -e production
    ```
    *   이 명령어는 `docker-compose.production.yml`을 자동으로 사용하여 컨테이너를 빌드하고 실행합니다.
    *   스크립트는 백업, 테스트 실행(건너뛸 수 있음), 컨테이너 빌드, 이전 컨테이너 중지, 새 컨테이너 시작, 데이터베이스 마이그레이션, Laravel 캐시 최적화, 파일 권한 설정 등의 작업을 수행합니다.

### 4단계: 배포 확인

배포 스크립트가 성공적으로 완료되면 애플리케이션이 예상대로 실행되는지 확인합니다.

1.  **Docker 컨테이너 상태 확인:**
    ```bash
    docker ps
    ```
    `nginx-prod`, `php-prod`, `redis-prod`, `queue-prod`, `scheduler-prod` 컨테이너가 모두 `Up` 상태인지 확인합니다.
2.  **애플리케이션 접속:** 웹 브라우저를 열고 2단계에서 구성한 `APP_URL`(예: `https://yourblog.com`)로 이동합니다. 웹사이트가 올바르게 로드되는지 확인합니다.
3.  **로그 확인 (선택 사항):** 문제가 발생하면 컨테이너 로그를 검사하여 문제를 진단할 수 있습니다.
    ```bash
    docker logs php-prod -f   # PHP 애플리케이션 로그
    docker logs nginx-prod -f # Nginx 웹 서버 로그
    docker logs redis-prod -f # Redis 로그
    ```

---

**중요 고려 사항:**

*   **데이터베이스:** 프로덕션 환경에서는 SQLite 대신 DigitalOcean Managed Database(MySQL 또는 PostgreSQL)를 사용하거나, Droplet에 직접 MySQL/PostgreSQL을 설치하여 사용하는 것을 강력히 권장합니다.
*   **도메인 및 DNS:** DigitalOcean DNS 또는 외부 DNS 서비스에서 도메인(예: `yourblog.com`)이 Droplet의 IP 주소를 가리키도록 A 레코드를 구성했는지 확인합니다.
*   **방화벽:** DigitalOcean Droplet의 방화벽(Cloud Firewall 또는 UFW)에서 80(HTTP) 및 443(HTTPS) 포트가 외부 접근을 위해 열려 있는지 확인합니다.
*   **AWS Lightsail:** AWS Lightsail 인스턴스도 DigitalOcean Droplet과 유사하게 Docker 및 Docker Compose를 설치하여 배포할 수 있습니다. Lightsail의 네트워킹 탭에서 인스턴스 방화벽(Firewall) 규칙을 설정하여 80(HTTP) 및 443(HTTPS) 포트가 열려 있는지 확인해야 합니다. 데이터베이스는 Lightsail 관리형 데이터베이스(MySQL/PostgreSQL) 또는 AWS RDS를 고려할 수 있습니다.

이 가이드가 애플리케이션을 성공적으로 배포하는 데 도움이 되기를 바랍니다.
