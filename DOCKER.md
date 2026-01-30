# Haru Shop - Docker Compose

## 포트

| 서비스             | 호스트 포트 | 컨테이너 포트 | 접속 URL              |
|--------------------|-------------|---------------|------------------------|
| haru-shop-next     | 501         | 3000          | http://localhost:501   |
| haru-shop-spring   | 502         | 8080          | http://localhost:502   |
| MariaDB            | 503         | 3306          | localhost:503          |
| haru-shop-nuxt     | 504         | 3000          | http://localhost:504   |
| haru-shop-django   | 505         | 8000          | http://localhost:505   |

## 실행

```bash
# HaruShop 루트에서
docker compose up -d --build
```

## 중지

```bash
docker compose down
```

## MariaDB 접속 정보

- **Database**: harushop
- **User**: harushop
- **Password**: harushop
- **Root Password**: rootpass
- **Host** (다른 컨테이너에서): `mariadb`, **Port**: 3306

## 환경 변수 (선택)

Spring Boot(Google/Naver/Kakao 로그인) 및 Next.js 빌드 시 다음 변수를 `.env`에 두면 사용됩니다.

- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- `NEXT_PUBLIC_GOOGLE_CLIENT_ID` (Next.js 빌드 시)
- `NAVER_CLIENT_ID`, `NAVER_CLIENT_SECRET`, `KAKAO_CLIENT_ID`, `KAKAO_CLIENT_SECRET` 등 (선택)
