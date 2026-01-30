-- users 테이블에 role 컬럼 추가 (user, seller, admin 등 확장 가능)
-- 기존 DB에 적용 시 이 스크립트를 실행하세요.
ALTER TABLE users
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'
COMMENT 'user, seller, admin 등';
