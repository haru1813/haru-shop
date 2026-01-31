-- =============================================================================
-- 상품 상세 시드 (product_images, option_masters, option_items, product_skus,
--                 product_text_option_specs, product_detail_lines)
-- ※ seed-products.sql 실행 후 실행
-- ※ 옵션/이미지/상세가 없는 모든 상품에 적용 (중복 방지: NOT EXISTS 사용)
--
-- 옵션 유형 다양화 (product id % 6 기준):
--   0: 조합형 색상만(4종)  1: 조합형 사이즈만(4종)  2: 조합형 색상+사이즈(16조합)
--   3: 단독형 증정품(키링/스티커/없음)  4: 직접입력형 각인 문구만
--   5: 조합형 색상 + 직접입력형 각인
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. 상품 이미지 (이미지가 없는 상품에만 상품당 2장 추가, picsum)
-- -----------------------------------------------------------------------------
INSERT INTO product_images (product_id, image_url, sort_order)
SELECT p.id, CONCAT('https://picsum.photos/400/400?random=', p.id * 20 + 1), 0
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id);
INSERT INTO product_images (product_id, image_url, sort_order)
SELECT p.id, CONCAT('https://picsum.photos/400/400?random=', p.id * 20 + 2), 1
FROM products p
WHERE (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) = 1;

-- -----------------------------------------------------------------------------
-- 2. 옵션 그룹 (옵션이 없는 상품에만) — 조합형/단독형/직접입력형 혼합
-- -----------------------------------------------------------------------------
-- 조합형: 색상 (id%6 IN (0, 2, 5))
INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order)
SELECT p.id, '색상', 'combination', 'color', 1, 0
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id)
  AND (p.id % 6 = 0 OR p.id % 6 = 2 OR p.id % 6 = 5);

-- 조합형: 사이즈 (id%6 IN (1, 2))
INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order)
SELECT p.id, '사이즈', 'combination', 'size', 1, 1
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id)
  AND (p.id % 6 = 1 OR p.id % 6 = 2);

-- 단독형: 증정품 (id%6 = 3)
INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order)
SELECT p.id, '증정품', 'simple', NULL, 0, 0
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id)
  AND p.id % 6 = 3;

-- 직접입력형: 각인 문구 (id%6 IN (4, 5))
INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order)
SELECT p.id, '각인 문구', 'text', NULL, 0, 2
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id)
  AND (p.id % 6 = 4 OR p.id % 6 = 5);

-- -----------------------------------------------------------------------------
-- 3. 옵션 항목 — 조합형(색상/사이즈) + 단독형(증정품)
-- -----------------------------------------------------------------------------
-- 색상 4종
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '블랙', 'black', 0, 0 FROM option_masters om WHERE om.option_key = 'color'
AND NOT EXISTS (SELECT 1 FROM option_items oi WHERE oi.option_master_id = om.id);
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '화이트', 'white', 0, 1 FROM option_masters om WHERE om.option_key = 'color'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 1;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '네이비', 'navy', 0, 2 FROM option_masters om WHERE om.option_key = 'color'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 2;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '그레이', 'gray', 0, 3 FROM option_masters om WHERE om.option_key = 'color'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 3;

-- 사이즈 4종
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, 'S', 'S', 0, 0 FROM option_masters om WHERE om.option_key = 'size'
AND NOT EXISTS (SELECT 1 FROM option_items oi WHERE oi.option_master_id = om.id);
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, 'M', 'M', 0, 1 FROM option_masters om WHERE om.option_key = 'size'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 1;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, 'L', 'L', 0, 2 FROM option_masters om WHERE om.option_key = 'size'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 2;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, 'XL', 'XL', 0, 3 FROM option_masters om WHERE om.option_key = 'size'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 3;

-- 단독형 증정품 3종 (키링 증정, 스티커 증정, 없음)
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '키링 증정', 'keyring', 0, 0 FROM option_masters om WHERE om.option_type = 'simple' AND om.name = '증정품'
AND NOT EXISTS (SELECT 1 FROM option_items oi WHERE oi.option_master_id = om.id);
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '스티커 증정', 'sticker', 0, 1 FROM option_masters om WHERE om.option_type = 'simple' AND om.name = '증정품'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 1;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '없음', 'none', 0, 2 FROM option_masters om WHERE om.option_type = 'simple' AND om.name = '증정품'
AND (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 2;

-- -----------------------------------------------------------------------------
-- 4. 직접입력형 스펙 (각인 문구 — label, placeholder, max_length)
-- -----------------------------------------------------------------------------
INSERT INTO product_text_option_specs (product_id, option_master_id, label, placeholder, max_length, sort_order)
SELECT om.product_id, om.id, '각인 문구', '원하시는 문구를 입력하세요 (최대 20자)', 20, 0
FROM option_masters om
WHERE om.option_type = 'text' AND om.name = '각인 문구'
  AND NOT EXISTS (SELECT 1 FROM product_text_option_specs pt WHERE pt.option_master_id = om.id);

-- -----------------------------------------------------------------------------
-- 5. 상품 SKU (조합형 전용) — 색상만 / 사이즈만 / 색상+사이즈
-- -----------------------------------------------------------------------------
-- 색상만 있는 상품 (사이즈 마스터 없음) → id%6=0 또는 id%6=5
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, CONCAT('color:', oi.value), 0, 15, 'on_sale'
FROM products p
JOIN option_masters om ON om.product_id = p.id AND om.option_key = 'color'
JOIN option_items oi ON oi.option_master_id = om.id
WHERE NOT EXISTS (SELECT 1 FROM option_masters om2 WHERE om2.product_id = p.id AND om2.option_key = 'size')
  AND NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id);

-- 사이즈만 있는 상품 (색상 마스터 없음) → id%6=1
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, CONCAT('size:', oi.value), 0, 15, 'on_sale'
FROM products p
JOIN option_masters om ON om.product_id = p.id AND om.option_key = 'size'
JOIN option_items oi ON oi.option_master_id = om.id
WHERE NOT EXISTS (SELECT 1 FROM option_masters om2 WHERE om2.product_id = p.id AND om2.option_key = 'color')
  AND NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id);

-- 색상+사이즈 있는 상품 → id%6=2
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, CONCAT('color:', c.value, ',size:', s.value), 0, 15, 'on_sale'
FROM products p
JOIN option_masters om_c ON om_c.product_id = p.id AND om_c.option_key = 'color'
JOIN option_items c ON c.option_master_id = om_c.id
JOIN option_masters om_s ON om_s.product_id = p.id AND om_s.option_key = 'size'
JOIN option_items s ON s.option_master_id = om_s.id
WHERE NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id);

-- -----------------------------------------------------------------------------
-- 6. 상품 상세 설명 줄 (상세 줄이 없는 상품에만 4줄)
-- -----------------------------------------------------------------------------
INSERT INTO product_detail_lines (product_id, sort_order, line_text)
SELECT p.id, 0, '소재: 순면 100% (상품에 따라 소재가 다를 수 있습니다.)' FROM products p
WHERE NOT EXISTS (SELECT 1 FROM product_detail_lines pd WHERE pd.product_id = p.id);
INSERT INTO product_detail_lines (product_id, sort_order, line_text)
SELECT p.id, 1, '세탁: 손세탁 권장, 염소표백 불가' FROM products p
WHERE (SELECT COUNT(*) FROM product_detail_lines pd WHERE pd.product_id = p.id) = 1;
INSERT INTO product_detail_lines (product_id, sort_order, line_text)
SELECT p.id, 2, '사이즈: 상세페이지 사이즈 가이드 참조' FROM products p
WHERE (SELECT COUNT(*) FROM product_detail_lines pd WHERE pd.product_id = p.id) = 2;
INSERT INTO product_detail_lines (product_id, sort_order, line_text)
SELECT p.id, 3, '제조국: 대한민국' FROM products p
WHERE (SELECT COUNT(*) FROM product_detail_lines pd WHERE pd.product_id = p.id) = 3;
