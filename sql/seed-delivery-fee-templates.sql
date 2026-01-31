-- =============================================================================
-- 배송비 템플릿 시드 데이터
-- delivery_fee_templates: fee_type별 샘플 (무료/유료/조건부무료/수량별)
-- =============================================================================

INSERT INTO delivery_fee_templates (
    name,
    fee_type,
    base_fee,
    free_over_amount,
    fee_per_quantity,
    quantity_unit,
    shipping_method,
    external_id,
    sort_order,
    is_active
) VALUES
-- 유료: 고정 3,000원 (택배)
('기본배송', 'paid', 3000, NULL, NULL, NULL, 'parcel', NULL, 1, 1),
-- 무료
('무료배송', 'free', 0, NULL, NULL, NULL, 'parcel', NULL, 2, 1),
-- 조건부 무료: 30,000원 이상 구매 시 무료, 미만 시 3,000원
('조건부 무료배송', 'conditional_free', 3000, 30000, NULL, NULL, 'parcel', NULL, 3, 1),
-- 수량별 부과: 2개당 3,000원 (2개당 3,000원 추가)
('수량별 배송비', 'per_quantity', 0, NULL, 3000, 2, 'parcel', NULL, 4, 1),
-- 퀵/당일배송 (유료 5,000원)
('당일배송', 'paid', 5000, NULL, NULL, NULL, 'quick', NULL, 5, 1),
-- 방문수령 (무료)
('방문수령', 'free', 0, NULL, NULL, NULL, 'pickup', NULL, 6, 1);
