-- =============================================================================
-- 상품 옵션/상세 데이터 전부 삭제
-- ※ seed-product-details.sql 재실행 전에 실행
-- ※ FK 순서: 자식 테이블 → 부모 테이블 순으로 DELETE
-- =============================================================================

-- 직접입력형 옵션 스펙 (option_masters 참조)
DELETE FROM product_text_option_specs;

-- 옵션 항목 (option_masters 참조)
DELETE FROM option_items;

-- 상품 SKU (products 참조)
DELETE FROM product_skus;

-- 옵션 그룹 (products 참조)
DELETE FROM option_masters;

-- 상품 상세 설명 줄 (products 참조)
DELETE FROM product_detail_lines;

-- 상품 이미지 (products 참조)
DELETE FROM product_images;
