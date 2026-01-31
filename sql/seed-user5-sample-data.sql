-- =============================================================================
-- user_id=5 전용 샘플 데이터
-- 주문, 주문상품, 장바구니, 찜, 리뷰, 문의, 사용자 쿠폰, 사용자 배송지, 저장 결제수단
-- ※ users.id=5, products, coupons, delivery_fee_templates 시드 선행 실행 필요
-- ※ 주문번호는 ORD-2025-1xxx 대로 지정 (기존 데이터와 충돌 방지)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. 주문 (3건: 결제완료, 배송준비중, 배송완료)
-- -----------------------------------------------------------------------------
INSERT INTO orders (
    user_id, order_number, status, total_amount, delivery_fee, delivery_fee_template_id,
    shipping_method, receiver_name, receiver_phone, receiver_address, created_at
) VALUES
(5, 'ORD-2025-1001', 'delivered', 59800, 3000, 1, 'parcel', '홍길동', '010-1234-5678', '서울시 강남구 테헤란로 123', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'ORD-2025-1002', 'preparing', 85800, 0, 2, 'parcel', '홍길동', '010-1234-5678', '서울시 강남구 테헤란로 123', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'ORD-2025-1003', 'payment_complete', 24900, 3000, 1, 'parcel', '김받는', '010-9876-5432', '경기도 성남시 분당구 판교로 456', NOW());

-- -----------------------------------------------------------------------------
-- 2. 주문 상품 (각 주문당 1~2개 품목, product_skus 존재 시 sku_id 사용)
-- -----------------------------------------------------------------------------
-- 주문 ORD-2025-1001: 상품 1 (2개), 상품 2 (1개)
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 1, (SELECT id FROM product_skus WHERE product_id=1 LIMIT 1), '오버핏 코튼 티셔츠', 19900, 2, NULL, NULL
FROM orders o WHERE o.order_number = 'ORD-2025-1001' AND o.user_id = 5 LIMIT 1;
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 2, (SELECT id FROM product_skus WHERE product_id=2 LIMIT 1), '베이직 맨투맨', 29900, 1, NULL, NULL
FROM orders o WHERE o.order_number = 'ORD-2025-1001' AND o.user_id = 5 LIMIT 1;

-- 주문 ORD-2025-1002: 상품 5, 10 (조합형) + 상품 15 (단독형 증정품 예시)
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 5, (SELECT id FROM product_skus WHERE product_id=5 LIMIT 1), '린넨 블라우스', 35900, 1, NULL, NULL
FROM orders o WHERE o.order_number = 'ORD-2025-1002' AND o.user_id = 5 LIMIT 1;
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 10, (SELECT id FROM product_skus WHERE product_id=10 LIMIT 1), '데님 셔츠', 36900, 1, NULL, NULL
FROM orders o WHERE o.order_number = 'ORD-2025-1002' AND o.user_id = 5 LIMIT 1;
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 15, NULL, '알파카 니트', 59900, 1, NULL, '{"증정품":"키링 증정"}'
FROM orders o WHERE o.order_number = 'ORD-2025-1002' AND o.user_id = 5 LIMIT 1;

-- 주문 ORD-2025-1003: 상품 4 (1개, 직접입력형 각인만 있는 상품 → sku_id NULL)
INSERT INTO order_items (order_id, product_id, sku_id, product_name, price, quantity, option_text, selected_options)
SELECT o.id, 4, NULL, '스트라이프 긴팔티', 24900, 1, 'Thank You', NULL
FROM orders o WHERE o.order_number = 'ORD-2025-1003' AND o.user_id = 5 LIMIT 1;

-- -----------------------------------------------------------------------------
-- 3. 장바구니 (user_id=5, 4개 상품: 조합형/단독형/직접입력형 혼합)
-- -----------------------------------------------------------------------------
-- 장바구니: 조합형(3,7,8) + 단독형 증정품(21: id%6=3) + 직접입력형 각인(8번에 option_text)
INSERT INTO cart_items (user_id, product_id, sku_id, quantity, option_text, selected_options)
VALUES
(5, 3, (SELECT id FROM product_skus WHERE product_id=3 LIMIT 1), 1, NULL, NULL),
(5, 7, (SELECT id FROM product_skus WHERE product_id=7 LIMIT 1), 2, NULL, NULL),
(5, 21, NULL, 1, NULL, '{"증정품":"스티커 증정"}'),
(5, 8, (SELECT id FROM product_skus WHERE product_id=8 LIMIT 1), 1, 'Happy Day', NULL);

-- -----------------------------------------------------------------------------
-- 4. 찜 (user_id=5, 5개 상품)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO wishlists (user_id, product_id) VALUES
(5, 1), (5, 5), (5, 12), (5, 25), (5, 50);

-- -----------------------------------------------------------------------------
-- 5. 리뷰 (user_id=5, 5개 상품에 대해 별점·내용)
-- -----------------------------------------------------------------------------
INSERT INTO reviews (product_id, user_id, rating, content, created_at) VALUES
(1, 5, 5, '착용감 좋고 디자인도 깔끔해요. 다음에 또 구매할게요.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 5, 4, '맨투맨 두께감 적당하고 편합니다. 가성비 좋아요.', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 5, 5, '가디건 질 좋아요. 단정하게 잘 받았습니다.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(5, 5, 4, '린넨이라 시원해요. 여름에 입기 좋습니다.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(10, 5, 5, '데님 셔츠 완전 만족입니다. 추천해요.', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- -----------------------------------------------------------------------------
-- 6. 문의 (user_id=5, 4건: 답변 완료 2건 / 미답변 2건)
-- -----------------------------------------------------------------------------
INSERT INTO inquiries (product_id, user_id, title, content, answer, answered_at, created_at) VALUES
(1, 5, '사이즈 문의', '165cm에 M 사이즈 맞을까요?', '165cm 기준 M 사이즈 착용 가능합니다. 여유 있는 오버핏을 원하시면 L도 추천드립니다.', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY)),
(5, 5, '배송 일정', '주문 후 며칠 안에 오나요?', '결제 완료 후 1~2 영업일 내 출고되며, 택배사 사정에 따라 2~4일 소요될 수 있습니다.', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(12, 5, '색상 추가 재입고', '네이비 색상 재입고 예정 있을까요?', NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(20, 5, '교환 가능 여부', '착용 후 한 번도 안 빨았는데 교환 가능한가요?', NULL, NULL, NOW());

-- -----------------------------------------------------------------------------
-- 7. 사용자 쿠폰 (user_id=5, 쿠폰 1~5 중 5장 보유, 1장 사용)
-- ※ coupons.id 1~5 존재 가정 (seed-coupons.sql)
-- -----------------------------------------------------------------------------
-- 사용한 쿠폰 1건: coupon_id=3을 주문 ORD-2025-1001에서 사용
INSERT INTO user_coupons (user_id, coupon_id, used_at, order_id, created_at) VALUES
(5, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5, 2, NULL, NULL, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(5, 4, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(5, 5, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT INTO user_coupons (user_id, coupon_id, used_at, order_id, created_at)
SELECT 5, 3, DATE_SUB(NOW(), INTERVAL 10 DAY), o.id, DATE_SUB(NOW(), INTERVAL 20 DAY)
FROM orders o WHERE o.order_number = 'ORD-2025-1001' AND o.user_id = 5 LIMIT 1;

-- -----------------------------------------------------------------------------
-- 8. 사용자 배송지 (user_id=5, 3곳, 1곳 기본)
-- -----------------------------------------------------------------------------
INSERT INTO user_addresses (user_id, label, recipient_name, phone, postal_code, address, address_detail, is_default, created_at) VALUES
(5, '집', '홍길동', '010-1234-5678', '06134', '서울특별시 강남구 테헤란로 123', '101동 1001호', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
(5, '회사', '홍길동', '010-1234-5678', '13529', '경기도 성남시 분당구 판교로 456', 'A동 3층', 0, DATE_SUB(NOW(), INTERVAL 45 DAY)),
(5, '부모님 댁', '김받는', '010-9876-5432', '48058', '부산광역시 해운대구 센텀중로 79', '센텀빌딩 202호', 0, DATE_SUB(NOW(), INTERVAL 30 DAY));

-- -----------------------------------------------------------------------------
-- 9. 저장 결제수단 (user_id=5, 2개: 카드/간편결제, 1개 기본)
-- -----------------------------------------------------------------------------
INSERT INTO user_payment_methods (user_id, pg_provider, display_name, masked_info, billing_key_or_token, is_default, created_at) VALUES
(5, 'toss', '개인 카드', '****-****-****-1234', 'toss_billing_xxx_sample', 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
(5, 'kakao', '카카오페이', '카카오페이로 결제', 'kakao_pay_token_sample', 0, DATE_SUB(NOW(), INTERVAL 60 DAY));
