-- 사용자 테이블 (소셜 로그인)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(100),
    picture VARCHAR(500),
    provider VARCHAR(50) NOT NULL DEFAULT 'google',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT 'user, seller, admin 등',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
