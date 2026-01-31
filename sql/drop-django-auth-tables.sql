-- =============================================================================
-- schema.sql에 없는 테이블 삭제 (Django auth + django 내부 테이블)
-- schema.sql에는 users, categories, products 등만 정의됨.
-- auth_*, django_* 테이블은 Django가 자동 생성한 것이므로 제거 시 관리자 로그인 불가.
-- =============================================================================
-- 실행: docker compose exec -T mariadb mariadb -u harushop -pharushop harushop < docs/drop-django-auth-tables.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Django admin 로그 (auth_user, django_content_type 참조)
DROP TABLE IF EXISTS django_admin_log;
-- Django auth 권한/그룹
DROP TABLE IF EXISTS auth_user_user_permissions;
DROP TABLE IF EXISTS auth_user_groups;
DROP TABLE IF EXISTS auth_group_permissions;
-- Django 세션 (auth_user 참조)
DROP TABLE IF EXISTS django_session;
-- auth 권한 (django_content_type 참조)
DROP TABLE IF EXISTS auth_permission;
DROP TABLE IF EXISTS auth_user;
DROP TABLE IF EXISTS auth_group;
DROP TABLE IF EXISTS django_content_type;
-- Django 마이그레이션 기록
DROP TABLE IF EXISTS django_migrations;

SET FOREIGN_KEY_CHECKS = 1;
