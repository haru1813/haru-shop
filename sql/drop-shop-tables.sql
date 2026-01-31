-- =============================================================================
-- Django 기본명으로 생성된 shop_* 테이블 삭제
-- schema.sql 테이블(categories, products 등)로 통일한 뒤 불필요한 shop_* 제거용
-- =============================================================================
-- 실행: docker compose exec -T mariadb mariadb -u harushop -pharushop harushop < docs/drop-shop-tables.sql

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS shop_orderitem;
DROP TABLE IF EXISTS shop_order;
DROP TABLE IF EXISTS shop_usercoupon;
DROP TABLE IF EXISTS shop_userpaymentmethod;
DROP TABLE IF EXISTS shop_useraddress;
DROP TABLE IF EXISTS shop_producttextoptionspec;
DROP TABLE IF EXISTS shop_productdetailline;
DROP TABLE IF EXISTS shop_productsku;
DROP TABLE IF EXISTS shop_optionitem;
DROP TABLE IF EXISTS shop_optionmaster;
DROP TABLE IF EXISTS shop_productimage;
DROP TABLE IF EXISTS shop_product;
DROP TABLE IF EXISTS shop_banner;
DROP TABLE IF EXISTS shop_coupon;
DROP TABLE IF EXISTS shop_addresscode;
DROP TABLE IF EXISTS shop_deliveryfeetemplate;
DROP TABLE IF EXISTS shop_category;

SET FOREIGN_KEY_CHECKS = 1;
