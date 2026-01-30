# Haru Shop - Docker Compose

## 포트

| 서비스           | 호스트 포트 | 컨테이너 포트 | 접속 URL           |
|------------------|-------------|---------------|---------------------|
| haru-shop-next   | 201         | 3000          | http://localhost:201 |
| haru-shop-spring | 202         | 8080          | http://localhost:202 |
| MariaDB          | 203         | 3306          | localhost:203       |

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
