# Ahhob Laravel blog CMS

## Docker + Laravel 11

### docker start
```bash
docker-compose up -d

# docker exec : 실행 중인 컨테이너에 명령을 실행합니다.
# -it : 대화형(interactive) 모드로 터미널에 접속합니다.
# php8.3 : 컨테이너 이름(당신의 docker-compose.yml에서 지정한 이름)
# sh : 쉘을 실행합니다. Alpine 기반 PHP 컨테이너는 bash 대신 sh가 일반적입니다.
docker exec -it php8.3 sh
```

### 폴더 구조

아래는 제공된 `docker-compose.yml` 파일을 기준으로 한 프로젝트 폴더 구조 예시입니다.

```bash
프로젝트-루트/
├── .docker/
│   ├── nginx/
│   │   ├── sites/
│   │   │   └── dev.default.conf
│   │   └── ssl/
│   ├── redis/
│   │   └── redis.conf
│   └── php/
│       └── 8.3/
│           └── Dockerfile
├── .database/
│   ├── redis/
│   └── sqlite/
│       └── database.sqlite
├── .logs/
│   ├── nginx/
│   ├── redis/
│   └── php/
├── src/
│   └── ... (소스코드)
├── docker-compose.yml
└── README.md
└── ...
```

### 폴더 설명

- **.docker/**
    - Docker 관련 설정 파일을 보관합니다.
    - `nginx/` : nginx용 설정과 SSL 인증서 폴더
    - `redis/` : redis용 설정
    - `php/8.3/` : php용 Dockerfile

- **.database/**
    - 서비스별 데이터베이스 파일 저장소
    - `redis/` : redis 데이터
    - `sqlite/` : sqlite 데이터베이스 파일(`database.sqlite`)

- **.logs/**
    - 컨테이너별 로그 파일 저장소
    - `nginx/`, `redis/`, `php/` 하위에 각 서비스 로그가 저장됨

- **src/**
    - 실제 웹 애플리케이션 소스코드가 위치

- **docker-compose.yml**
    - 도커 컴포즈 설정 파일

- **README.md**
    - (선택) 프로젝트 설명 파일



## Git Repository Description

### …or create a new repository on the command line

```bash
echo "# ahhob-laravel-blog-cms" >> README.md
git init
git add README.md
git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/nOo9ya/ahhob-laravel-blog-cms.git
git push -u origin main
```

### …or push an existing repository from the command line

```bash
git remote add origin https://github.com/nOo9ya/ahhob-laravel-blog-cms.git
git branch -M main
git push -u origin main
```

## git clone 과 git pull의 차이점 및 사용 방법

### 차이점

- **git clone**
    - 원격 저장소(Repository)를 처음 내 컴퓨터로 복제할 때 사용합니다.
    - 저장소 전체(파일, 커밋 이력, 브랜치 등)를 새로 복사합니다.
    - 보통 프로젝트를 처음 시작할 때 1회만 실행합니다.

- **git pull**
    - 이미 내 컴퓨터에 복제된 저장소의 변경 사항을 원격 저장소로부터 가져와서 병합할 때 사용합니다.
    - 코드, 커밋 등 최신 상태로 업데이트할 때 여러 번 사용합니다.

---

## 사용 예제

### 1. git clone 사용법

처음 레포지토리를 복제할 때:

```sh
git clone https://github.com/nOo9ya/ahhob-laravel-blog-cms.git
```

→ 실행 결과:  
`ahhob-laravel-blog-cms` 폴더가 생성되고, 해당 저장소의 모든 코드와 이력이 내려받아집니다.

---

### 2. git pull 사용법

이미 clone 한 폴더로 이동 후 최신 코드로 업데이트할 때:

```sh
cd ahhob-laravel-blog-cms
git pull
```

→ 실행 결과:  
원격 저장소의 변경사항이 내 로컬 저장소로 내려받아지고 자동으로 병합됩니다.

---

### 요약

| 명령어       | 용도                               | 실행 시점              |
|--------------|------------------------------------|------------------------|
| git clone    | 원격 저장소 전체를 처음 복제        | 프로젝트 최초 1회      |
| git pull     | 원격 저장소의 변경사항을 동기화     | 작업 중 여러 번        |

> **참고:**
> - `git clone`은 새로운 디렉토리를 만든 뒤, 그 안에 저장소 전체를 복사합니다.
> - `git pull`은 이미 clone 받은 폴더에서만 실행 가능합니다.