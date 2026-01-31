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

## 📁 프로젝트 구조

```
haru-shop/
├── docker-compose.yml      # 메인 Docker Compose
├── haru-shop-next/         # Next.js 프론트엔드
├── haru-shop-spring/       # Spring Boot API
├── haru-shop-php/          # PHP 관리자
├── sql/                     # DB 스키마·시드·유틸 SQL
├── docs/                    # 기타 문서·스크립트
└── resources/               # 공용 이미지 등
```

---

## 🏗️ 서비스 구조도 및 DB 설계도

### 서비스 구조

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  haru-shop-next │────▶│ haru-shop-spring│────▶│     MariaDB     │
│  (포트 501)     │     │  (포트 502)     │     │   (포트 503)    │
│  쇼핑몰 화면    │     │  REST API + JWT │     │   공용 DB       │
└─────────────────┘     └────────┬────────┘     └─────────────────┘
                                 │
┌─────────────────┐              │
│  haru-shop-php  │──────────────┘
│  (포트 809)     │
│  관리자 /admin/ │
└─────────────────┘
```

- **Next**: 브라우저 → Spring API 호출 (JWT Bearer). 쇼핑몰 회원·상품·장바구니·주문·마이페이지.
- **Spring**: 인증(Google/Naver/Kakao), 상품·카테고리·배너·장바구니·찜·주문·마이페이지(쿠폰·배송지·리뷰·문의·프로필). MyBatis로 MariaDB 접근.
- **PHP**: 관리자 로그인 후 카테고리·상품·배너·쿠폰·주문·주소코드·배송비템플릿 등 CRUD. 동일 MariaDB 사용.

### 기술 선택 이유 (Why)

- **Next.js**: SSR·개발 생산성과 SEO를 위해 React 기반 Next.js로 쇼핑몰 프론트를 구성.
- **Spring Boot**: API 일원화와 Java 생태계·유지보수성을 고려해 REST API 서버로 선택.
- **PHP (관리자)**: 관리 업무의 생산성을 위해 익숙한 PHP 환경으로 관리자 페이지를 구축.
- **MariaDB**: 오픈소스의 확장성과 MySQL 호환성을 고려한 MariaDB 선택.

> 상세 구조·API 예시·전체 테이블 정의서·트러블슈팅 스토리는 [docs/haru-shop-guide.html](docs/haru-shop-guide.html) 참고.

### DB 설계 개요

| 영역 | 테이블 | 설명 |
|------|--------|------|
| **회원** | `users` | 소셜 로그인, 프로필 |
| **상품** | `categories`, `products`, `product_images`, `option_masters`, `option_items`, `product_skus`, `product_text_option_specs`, `product_detail_lines` | 카테고리·상품·옵션·SKU·상세 |
| **배송·물류** | `delivery_fee_templates`, `address_codes` | 배송비 템플릿, 출고지/반품지 |
| **주문** | `orders`, `order_items` | 주문·주문상품 |
| **쇼핑** | `cart_items`, `wishlists` | 장바구니, 찜 |
| **콘텐츠** | `banners` | 메인 배너 |
| **마이페이지** | `reviews`, `inquiries`, `coupons`, `user_coupons`, `user_addresses` | 리뷰·문의·쿠폰·배송지 |

전체 스키마는 `sql/schema.sql` 참고.

---

## 📡 API 명세서 (Spring Boot)

Base URL: `http://localhost:502/api` (인증 필요 API는 `Authorization: Bearer <JWT>`)

### 인증

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/auth/google` | Google 로그인 리다이렉트 |
| GET | `/auth/google/callback` | Google 콜백 |
| POST | `/auth/google` | Google idToken으로 로그인 (JSON body) |
| GET | `/auth/kakao` | 카카오 로그인 리다이렉트 |
| GET | `/auth/kakao/callback` | 카카오 콜백 |
| GET | `/auth/naver` | 네이버 로그인 리다이렉트 |
| GET | `/auth/naver/callback` | 네이버 콜백 |

### 공개

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/categories` | 카테고리 목록 |
| GET | `/categories/slug/{slug}` | slug로 카테고리 조회 |
| GET | `/products` | 상품 목록 (categoryId, search, limit, offset) |
| GET | `/products/{id}` | 상품 상세 |
| GET | `/banners` | 배너 목록 |

### 인증 필요

| 메서드 | 경로 | 설명 |
|--------|------|------|
| GET | `/cart` | 장바구니 목록 |
| POST | `/cart` | 장바구니 담기 |
| PUT | `/cart/{id}` | 장바구니 수량 변경 |
| DELETE | `/cart/{id}` | 장바구니 삭제 |
| GET | `/wishlist` | 찜 목록 |
| POST | `/wishlist` | 찜 추가 |
| DELETE | `/wishlist/{productId}` | 찜 삭제 |
| GET | `/orders` | 주문 목록 |
| POST | `/orders` | 주문 생성 |
| GET | `/mypage/coupons` | 내 쿠폰 |
| GET | `/mypage/addresses` | 배송지 목록 |
| POST | `/mypage/addresses` | 배송지 등록 |
| PUT | `/mypage/addresses/{id}` | 배송지 수정 |
| DELETE | `/mypage/addresses/{id}` | 배송지 삭제 |
| GET | `/mypage/reviews` | 내 리뷰 목록 |
| POST | `/mypage/reviews` | 리뷰 등록 |
| DELETE | `/mypage/reviews/{id}` | 리뷰 삭제 |
| GET | `/mypage/inquiries` | 내 문의 목록 |
| POST | `/mypage/inquiries` | 문의 등록 |
| GET | `/mypage/profile` | 내 프로필 |
| PATCH | `/mypage/profile` | 프로필 수정 |

---

## 🔧 트러블슈팅

### Docker

| 현상 | 확인·조치 |
|------|------------|
| 컨테이너가 기동하지 않음 | `docker compose up -d` 후 `docker compose ps`, `docker compose logs <서비스명>` 로 로그 확인 |
| 포트 충돌 | 501, 502, 503, 809 사용 중인 프로세스 확인 후 종료 또는 `docker-compose.yml`에서 포트 변경 |
| Spring 기동 실패 | MariaDB가 먼저 healthy 될 때까지 대기. `depends_on: mariadb: condition: service_healthy` 확인 |

### DB

| 현상 | 확인·조치 |
|------|------------|
| 테이블 없음 / 스키마 오류 | `sql/schema.sql` 적용. 도커 사용 시: `docker exec -i haru-shop-mariadb mariadb -u harushop -pharushop harushop < sql/schema.sql` |
| 사용하지 않는 테이블 정리 | `sql/drop-unused-tables.sql` 실행. 도커: `docker exec -i haru-shop-mariadb mariadb -u harushop -pharushop harushop < sql/drop-unused-tables.sql` |
| 로컬에 mysql 클라이언트 없음 | 위처럼 `docker exec`로 컨테이너 안의 `mariadb` 클라이언트 사용 |

### 인증·API

| 현상 | 확인·조치 |
|------|------------|
| 401 Unauthorized | 로그인 후 JWT를 `Authorization: Bearer <token>` 으로 전달. 토큰은 로그인 콜백 후 localStorage 등에 저장 |
| 403 Forbidden | 해당 API는 로그인 필요. 프론트에서 로그인 페이지로 유도 |
| Google/소셜 로그인 실패 | `.env`에 `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `FRONTEND_REDIRECT_URI` 등 콜백 URL 설정 확인 |

### 프론트(Next)

| 현상 | 확인·조치 |
|------|------------|
| API 호출 실패 (CORS/네트워크) | Next는 브라우저에서 Spring으로 직접 요청. Spring `CorsResponseFilter` 및 API Base URL(`getApiBaseUrl`) 확인 |
| 로그인 후 토큰이 안 남음 | 로그인 콜백 페이지에서 `localStorage.setItem('harushop_token', ...)` 호출 여부 확인 |

### 스토리: Spring과 MariaDB 기동 순서

- **문제**: `docker compose up` 시 Spring이 DB 연결 오류로 반복 재시작.
- **원인**: Spring이 MariaDB보다 먼저 기동해, DB 준비 전에 연결 시도. `depends_on`만으로는 "실제 accept 가능 시점"이 보장되지 않음.
- **해결**: MariaDB에 `healthcheck` 추가, Spring에 `depends_on: mariadb: condition: service_healthy` 설정.
- **결과**: MariaDB healthy 이후에만 Spring 기동되어 안정적으로 시작.

---
