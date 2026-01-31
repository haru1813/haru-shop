-- =============================================================================
-- 사용하지 않는 테이블 DROP (Haru Shop - Spring + PHP 기준)
-- Django 제거 후 남은 auth_*, django_* 및 미사용 user_payment_methods 제거
--
-- 실행 (haru-shop 루트에서, MariaDB 컨테이너 실행 중일 때):
--   docker exec -i haru-shop-mariadb mariadb -u harushop -pharushop harushop < sql/drop-unused-tables.sql
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS django_admin_log;
DROP TABLE IF EXISTS auth_user_user_permissions;
DROP TABLE IF EXISTS auth_user_groups;
DROP TABLE IF EXISTS auth_group_permissions;
DROP TABLE IF EXISTS auth_permission;
DROP TABLE IF EXISTS auth_user;
DROP TABLE IF EXISTS auth_group;
DROP TABLE IF EXISTS django_content_type;
DROP TABLE IF EXISTS django_migrations;
DROP TABLE IF EXISTS django_session;
DROP TABLE IF EXISTS user_payment_methods;

SET FOREIGN_KEY_CHECKS = 1;
