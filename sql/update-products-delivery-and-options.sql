-- =============================================================================
-- 모든 상품에 배송비 템플릿 + 옵션 보강
-- 1) 배송비 템플릿이 NULL인 상품 → 첫 번째 배송비 템플릿으로 UPDATE
-- 2) 옵션/이미지/상세가 없는 상품 → INSERT (option_masters, option_items, product_skus, product_images, product_detail_lines)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. 배송비 템플릿: NULL인 상품 전부 첫 번째 템플릿으로 설정
-- -----------------------------------------------------------------------------
UPDATE products p
SET p.delivery_fee_template_id = (
    SELECT id FROM (
        SELECT id FROM delivery_fee_templates ORDER BY sort_order, id LIMIT 1
    ) AS t
)
WHERE p.delivery_fee_template_id IS NULL;

-- -----------------------------------------------------------------------------
-- 2. 상품 이미지 (이미지가 없는 상품에만 2장 추가, picsum)
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
-- 3. 옵션 그룹 (옵션이 없는 상품에만 1개: 색상, 조합형)
-- -----------------------------------------------------------------------------
INSERT INTO option_masters (product_id, name, option_type, option_key, is_required, sort_order)
SELECT p.id, '색상', 'combination', 'color', 1, 0
FROM products p
WHERE NOT EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id);

-- -----------------------------------------------------------------------------
-- 4. 옵션 항목 (항목이 0개인 옵션 그룹에만 3개: 블랙, 화이트, 네이비)
-- -----------------------------------------------------------------------------
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '블랙', 'black', 0, 0 FROM option_masters om
WHERE NOT EXISTS (SELECT 1 FROM option_items oi WHERE oi.option_master_id = om.id);
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '화이트', 'white', 0, 1 FROM option_masters om
WHERE (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 1;
INSERT INTO option_items (option_master_id, name, value, option_price, sort_order)
SELECT om.id, '네이비', 'navy', 0, 2 FROM option_masters om
WHERE (SELECT COUNT(*) FROM option_items oi WHERE oi.option_master_id = om.id) = 2;

-- -----------------------------------------------------------------------------
-- 5. 상품 SKU (색상 옵션은 있으나 SKU가 없는 상품에만 3개)
-- -----------------------------------------------------------------------------
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, 'color:black', 0, 15, 'on_sale' FROM products p
WHERE EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id AND om.option_key = 'color')
  AND NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id);
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, 'color:white', 0, 15, 'on_sale' FROM products p
WHERE EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id AND om.option_key = 'color')
  AND EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id AND ps.option_key = 'color:black')
  AND NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id AND ps.option_key = 'color:white');
INSERT INTO product_skus (product_id, option_key, option_price, stock, sell_status)
SELECT p.id, 'color:navy', 0, 15, 'on_sale' FROM products p
WHERE EXISTS (SELECT 1 FROM option_masters om WHERE om.product_id = p.id AND om.option_key = 'color')
  AND EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id AND ps.option_key = 'color:white')
  AND NOT EXISTS (SELECT 1 FROM product_skus ps WHERE ps.product_id = p.id AND ps.option_key = 'color:navy');

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
