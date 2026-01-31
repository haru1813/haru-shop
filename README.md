# 🛒 Haru Shop

쇼핑몰 풀스택 프로젝트 — Next.js 프론트엔드, Spring Boot API, PHP 관리자, MariaDB.

---

## 📦 구성

| 서비스 | 기술 스택 | 포트 | 설명 |
|--------|-----------|------|------|
| **haru-shop-next** | Next.js 16, React 19, TypeScript | **501** | 쇼핑몰 프론트엔드 (상품·장바구니·주문·마이페이지) |
| **haru-shop-spring** | Spring Boot 3.2, MyBatis, JWT | **502** | REST API (인증·상품·장바구니·주문·마이페이지) |
| **haru-shop-php** | PHP, Apache | **809** | 관리자 화면 (`/admin/`) |
| **mariadb** | MariaDB 11.2 | **503** | 공용 DB |

---

## 🚀 빠른 시작

### 요구사항

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose

### 실행

```bash
# 저장소 클론 후
cd haru-shop

# 전체 스택 실행
docker compose up -d

# 로그 확인
docker compose logs -f
```

### 접속

| 용도 | URL |
|------|-----|
| 쇼핑몰 (Next) | http://localhost:501 |
| API (Spring) | http://localhost:502/api |
| 관리자 (PHP) | http://localhost:809/admin |

---

## ⚙️ 환경 변수

`.env` 파일을 프로젝트 루트에 두고 사용할 수 있습니다.

| 변수 | 설명 | 기본값 |
|------|------|--------|
| `GOOGLE_CLIENT_ID` | Google 로그인 클라이언트 ID | - |
| `GOOGLE_CLIENT_SECRET` | Google 로그인 시크릿 | - |
| `NEXT_PUBLIC_GOOGLE_CLIENT_ID` | Next.js용 Google 클라이언트 ID | - |
| `FRONTEND_REDIRECT_URI` | 로그인 콜백 URL | (Spring 기본값) |
| `ADMIN_EMAIL` | PHP 관리자 초기 이메일 | admin@haru.local |
| `ADMIN_PASSWORD` | PHP 관리자 초기 비밀번호 | admin123! |

---

## 📁 프로젝트 구조

```
haru-shop/
├── docker-compose.yml      # 메인 Docker Compose
├── haru-shop-next/        # Next.js 프론트엔드
├── haru-shop-spring/      # Spring Boot API
├── haru-shop-php/         # PHP 관리자
├── sql/                    # DB 스키마·시드·유틸 SQL
│   ├── schema.sql
│   ├── seed-*.sql
│   └── ...
├── docs/                   # 기타 문서·스크립트
└── resources/              # 공용 이미지 등
```

---

## 🗄️ SQL 스크립트

`sql/` 폴더에 스키마·시드·유틸리티 SQL이 있습니다.

| 파일 | 용도 |
|------|------|
| `schema.sql` | 테이블 생성 (전체 스키마) |
| `seed-categories.sql` | 카테고리 시드 |
| `seed-products.sql` | 상품 시드 |
| `seed-banners.sql` | 배너 시드 |
| `seed-coupons.sql` | 쿠폰 시드 |
| 기타 `seed-*.sql` | 주소·배송비 템플릿 등 |
| `drop-*.sql` | 테이블/데이터 정리용 |

MariaDB에 접속한 뒤 `source sql/schema.sql` 등으로 실행하면 됩니다.

---

## 📜 라이선스

Private project.
