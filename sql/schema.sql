-- =============================================================================
-- Haru Shop DB 스키마 (haru-shop-next 화면 기준)
-- MySQL / MariaDB
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 사용자 (소셜 로그인, 마이페이지)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '이메일 (로그인 ID)',
    name VARCHAR(100) COMMENT '이름',
    picture VARCHAR(500) COMMENT '프로필 이미지 URL',
    provider VARCHAR(50) NOT NULL DEFAULT 'google' COMMENT '소셜 로그인 제공자 (google, naver, kakao 등)',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '역할: user, seller, admin',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '가입일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 (소셜 로그인, 마이페이지)';

-- -----------------------------------------------------------------------------
-- 카테고리 (홈·상품 카테고리)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    name VARCHAR(100) NOT NULL COMMENT '카테고리명 (예: 상의, 하의)',
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL용 슬러그',
    icon VARCHAR(50) COMMENT '아이콘 식별자',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서 (작을수록 앞)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='카테고리 (홈·상품 카테고리)';

-- =============================================================================
-- 배송·물류 정책 (요구사항: 배송비 템플릿, 출고지/반품지 주소 코드, 배송 방법)
-- 상품·주문 테이블에서 FK 참조하므로 카테고리 다음에 정의
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 배송비 템플릿 (Template ID)
-- 요구사항: 상품별로 일일이 설정하지 않고 규정 묶음(템플릿) 관리번호로 연결
-- ESM API 연동 시 선설정 후 발급된 ID(숫자)를 상품 등록 시 전송
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS delivery_fee_templates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    name VARCHAR(100) NOT NULL COMMENT '템플릿명 (예: 기본배송, 무료배송)',
    fee_type VARCHAR(20) NOT NULL COMMENT '유형: free(무료), paid(유료), conditional_free(조건부 무료), per_quantity(수량별 부과)',
    base_fee DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '유료 시 고정 배송비 (원). 수량별 시 1단위당 금액',
    free_over_amount DECIMAL(12, 0) COMMENT '조건부 무료 시 기준 금액 (원). 이 금액 이상 구매 시 무료',
    fee_per_quantity DECIMAL(12, 0) COMMENT '수량별 부과 시 N개당 배송비 (원)',
    quantity_unit INT COMMENT '수량별 부과 시 단위 (예: 2개당 3,000원이면 2)',
    shipping_method VARCHAR(20) NOT NULL DEFAULT 'parcel' COMMENT '배송방법: parcel(택배), direct(직배송/화물), pickup(방문수령), quick(퀵/당일배송)',
    external_id VARCHAR(50) COMMENT 'ESM 등 API 연동 시 발급된 템플릿 ID (숫자)',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '사용 여부 (1:사용, 0:미사용)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배송비 템플릿 (무료/유료/조건부무료/수량별)';

-- -----------------------------------------------------------------------------
-- 주소 코드 (출고지 / 반품·교환지)
-- 요구사항: 텍스트 주소가 아닌 시스템 등록 고유 주소 코드 사용.
-- 주소 코드가 정확해야 제주/도서산간 추가 배송비가 시스템에서 자동 계산됨.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS address_codes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '고유 주소 코드 (시스템/ESM 연동용. 제주·도서산간 추가비 계산용)',
    address_type VARCHAR(20) NOT NULL COMMENT '용도: warehouse(출고지), return(반품/교환지)',
    name VARCHAR(100) COMMENT '관리용 명칭 (예: 본사 창고, 3PL A센터)',
    recipient_name VARCHAR(100) COMMENT '수령인/담당자',
    phone VARCHAR(20) COMMENT '연락처',
    postal_code VARCHAR(20) COMMENT '우편번호',
    address VARCHAR(500) NOT NULL COMMENT '기본 주소',
    address_detail VARCHAR(200) COMMENT '상세 주소',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '사용 여부 (1:사용, 0:미사용)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='출고지/반품지 주소 코드';

-- -----------------------------------------------------------------------------
-- 상품
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    category_id BIGINT NOT NULL COMMENT '카테고리 FK',
    name VARCHAR(200) NOT NULL COMMENT '상품명',
    slug VARCHAR(200) COMMENT 'URL용 슬러그',
    price DECIMAL(12, 0) NOT NULL COMMENT '판매가 (원)',
    description TEXT COMMENT '상품 소개 문구',
    image_url VARCHAR(500) COMMENT '대표 이미지 URL',
    stock INT NOT NULL DEFAULT 0 COMMENT '재고 수량 (단독형·옵션없음 상품용. 조합형은 product_skus 사용)',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '노출 여부 (1:노출, 0:미노출)',
    delivery_fee_template_id BIGINT COMMENT '배송비 템플릿 FK. NULL이면 기본 템플릿 또는 상위 정책 적용',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_products_delivery_template FOREIGN KEY (delivery_fee_template_id) REFERENCES delivery_fee_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품';

-- -----------------------------------------------------------------------------
-- 상품 이미지 (여러 장, 상품 상세·캐러셀)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_images (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    image_url VARCHAR(500) NOT NULL COMMENT '이미지 URL',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서 (작을수록 앞)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 이미지 (여러 장, 상품 상세·캐러셀)';

-- -----------------------------------------------------------------------------
-- 옵션 그룹 (Option Master): 단독형/조합형/직접입력형
-- 요구사항: 옵션 그룹명(예: 사이즈, 색상, 증정품, 각인 문구) 저장
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS option_masters (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    name VARCHAR(100) NOT NULL COMMENT '옵션 그룹명 (예: 색상, 사이즈, 증정품, 각인 문구)',
    option_type VARCHAR(20) NOT NULL DEFAULT 'combination' COMMENT '유형: simple(단독형), combination(조합형), text(직접입력형)',
    option_key VARCHAR(50) COMMENT '조합형 SKU 키 생성용 (예: color, size). 조합형일 때만 사용',
    is_required TINYINT(1) NOT NULL DEFAULT 0 COMMENT '필수 여부 (1:필수, 0:선택)',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서 (작을수록 앞)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_option_masters_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='옵션 그룹 (Option Master)';

-- -----------------------------------------------------------------------------
-- 옵션 항목 (Option Item): 세부 항목 - 단독형·조합형용
-- 요구사항: 세부 항목(예: 빨강, 노랑, 95, 100, 키링 증정) 저장
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS option_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    option_master_id BIGINT NOT NULL COMMENT '옵션 그룹 FK',
    name VARCHAR(100) NOT NULL COMMENT '표시명 (예: 블랙, XL, 키링 증정)',
    value VARCHAR(100) COMMENT 'SKU 키 생성용 값 (조합형, 예: black, XL). 미입력 시 name 사용',
    option_price DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '추가 금액 (원). 조합형에서 옵션별 차등 가능',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서 (작을수록 앞)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    CONSTRAINT fk_option_items_master FOREIGN KEY (option_master_id) REFERENCES option_masters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='옵션 항목 (Option Item)';

-- -----------------------------------------------------------------------------
-- 상품 SKU (조합형 전용)
-- 요구사항: 조합된 최종 결과물(예: 빨강-100)별 추가금액, 현재재고, 판매상태
-- ESM 등 연동 시 option_price는 전체 판매가 대비 ±50% 이내 권장
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_skus (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    option_key VARCHAR(200) NOT NULL COMMENT '조합 키 (예: color:black,size:XL). 옵션 그룹 정렬 순서로 결합',
    option_price DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '추가 금액 (원)',
    stock INT NOT NULL DEFAULT 0 COMMENT '현재 재고',
    sell_status VARCHAR(20) NOT NULL DEFAULT 'on_sale' COMMENT '판매상태: on_sale(판매중), out_of_stock(품절), hidden(미노출)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    UNIQUE KEY uk_product_skus_option (product_id, option_key) COMMENT '상품별 옵션 조합 유일',
    CONSTRAINT fk_product_skus_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 SKU (조합형 옵션별 재고·가격)';

-- -----------------------------------------------------------------------------
-- 직접입력형 옵션 스펙 (각인 문구 등)
-- 상품별 직접입력 필드 라벨·placeholder·최대 길이 (화면용)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_text_option_specs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    option_master_id BIGINT NOT NULL COMMENT '옵션 그룹 FK (type=text인 option_masters)',
    label VARCHAR(200) NOT NULL COMMENT '화면 라벨 (예: 각인 문구)',
    placeholder VARCHAR(200) COMMENT '입력 필드 placeholder',
    max_length INT COMMENT '최대 글자 수',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    CONSTRAINT fk_text_option_specs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_text_option_specs_master FOREIGN KEY (option_master_id) REFERENCES option_masters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직접입력형 옵션 스펙 (각인 등)';

-- -----------------------------------------------------------------------------
-- 상품 상세 설명 줄 (상품 정보 탭: 이미지-설명 반복용)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_detail_lines (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '표시 순서 (이미지1-설명1-이미지2-설명2 순)',
    line_text VARCHAR(500) NOT NULL COMMENT '설명 한 줄 (소재, 세탁, 사이즈 등)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    CONSTRAINT fk_product_detail_lines_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 상세 설명 줄 (상품 정보 탭)';

-- -----------------------------------------------------------------------------
-- 배너 (메인 캐러셀)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banners (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    image_url VARCHAR(500) NOT NULL COMMENT '이미지 URL',
    link_url VARCHAR(500) COMMENT '클릭 시 이동 URL',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '슬라이드 순서 (작을수록 앞)',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '노출 여부 (1:노출, 0:미노출)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배너 (메인 캐러셀)';

-- -----------------------------------------------------------------------------
-- 주문
-- 배송비: 주문 시 적용된 템플릿·출고지/반품지 코드 스냅샷 (정산·CS용)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT COMMENT '주문자(사용자) FK, 비회원 주문 시 NULL',
    order_number VARCHAR(50) NOT NULL UNIQUE COMMENT '주문번호 (예: ORD-2025-001)',
    status VARCHAR(20) NOT NULL DEFAULT 'payment_complete' COMMENT '상태: payment_complete(결제완료), preparing(배송준비중), shipping(배송중), delivered(배송완료)',
    total_amount DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '상품 금액 합계 (원)',
    delivery_fee DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '배송비 (원, 계산 결과)',
    delivery_fee_template_id BIGINT COMMENT '주문 시 적용된 배송비 템플릿 FK (스냅샷)',
    warehouse_address_code_id BIGINT COMMENT '출고지 주소 코드 FK. 택배 출발지',
    return_address_code_id BIGINT COMMENT '반품/교환지 주소 코드 FK. 출고지와 다를 경우 별도 설정',
    shipping_method VARCHAR(20) NOT NULL DEFAULT 'parcel' COMMENT '배송방법: parcel(택배), direct(직배송), pickup(방문수령), quick(퀵/당일)',
    receiver_name VARCHAR(100) COMMENT '수령인 이름',
    receiver_phone VARCHAR(20) COMMENT '수령인 연락처',
    receiver_address VARCHAR(500) COMMENT '수령 주소',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '주문일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_delivery_template FOREIGN KEY (delivery_fee_template_id) REFERENCES delivery_fee_templates(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_warehouse_address FOREIGN KEY (warehouse_address_code_id) REFERENCES address_codes(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_return_address FOREIGN KEY (return_address_code_id) REFERENCES address_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문';

-- -----------------------------------------------------------------------------
-- 주문 상품
-- 조합형: sku_id로 품목 식별. 단독형: selected_options(JSON). 직접입력형: option_text에 저장
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    order_id BIGINT NOT NULL COMMENT '주문 FK',
    product_id BIGINT COMMENT '상품 FK (상품 삭제 시에도 주문 내역 유지 위해 NULL 허용)',
    sku_id BIGINT COMMENT '조합형일 때 상품 SKU FK. NULL이면 단독형/옵션없음',
    product_name VARCHAR(200) NOT NULL COMMENT '주문 시점 상품명 (스냅샷)',
    price DECIMAL(12, 0) NOT NULL COMMENT '주문 시점 단가 (원, 옵션 추가금 포함)',
    quantity INT NOT NULL DEFAULT 1 COMMENT '수량',
    option_text VARCHAR(500) COMMENT '직접입력형 값 (예: 각인 문구). 요구사항: 주문 상세 테이블에 저장',
    selected_options JSON COMMENT '단독형 선택값 스냅샷 (예: {"증정품":"키링 증정"})',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT fk_order_items_sku FOREIGN KEY (sku_id) REFERENCES product_skus(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주문 상품';

-- -----------------------------------------------------------------------------
-- 장바구니
-- 조합형: sku_id. 단독형: selected_options(JSON). 직접입력형: option_text. 동일 상품이라도 옵션별로 행 분리 가능
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT NOT NULL COMMENT '사용자 FK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    sku_id BIGINT COMMENT '조합형일 때 상품 SKU FK. NULL이면 단독형/옵션없음',
    quantity INT NOT NULL DEFAULT 1 COMMENT '수량',
    option_text VARCHAR(500) COMMENT '직접입력형 값 (예: 각인 문구)',
    selected_options JSON COMMENT '단독형 선택값 (예: {"증정품":"키링 증정"})',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '담은 일시',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_cart_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_sku FOREIGN KEY (sku_id) REFERENCES product_skus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='장바구니';

-- -----------------------------------------------------------------------------
-- 찜
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlists (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT NOT NULL COMMENT '사용자 FK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '찜한 일시',
    UNIQUE KEY uk_wishlist_user_product (user_id, product_id) COMMENT '동일 사용자·상품 1행만 허용',
    CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='찜';

-- -----------------------------------------------------------------------------
-- 리뷰 (상품 상세 탭)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    user_id BIGINT NOT NULL COMMENT '작성자(사용자) FK',
    rating TINYINT NOT NULL DEFAULT 5 COMMENT '별점 1~5',
    content TEXT COMMENT '리뷰 내용',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일',
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='리뷰 (상품 상세 탭)';

-- -----------------------------------------------------------------------------
-- 문의 (상품 상세 탭)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inquiries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    product_id BIGINT NOT NULL COMMENT '상품 FK',
    user_id BIGINT NOT NULL COMMENT '문의자(사용자) FK',
    title VARCHAR(200) COMMENT '문의 제목',
    content TEXT NOT NULL COMMENT '문의 내용',
    answer TEXT COMMENT '답변 내용',
    answered_at DATETIME COMMENT '답변 일시',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '문의일',
    CONSTRAINT fk_inquiries_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_inquiries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='문의 (상품 상세 탭)';

-- =============================================================================
-- 마이페이지 전용 (next.haru.company/mypage)
-- 주문내역(orders)·찜(wishlists)·장바구니(cart_items)는 위에 정의됨
-- 아래: 쿠폰, 배송지 관리, 저장 결제수단
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 쿠폰 (마이페이지 · 쿠폰)
-- 관리자 발급 쿠폰 정의. 할인 유형·조건·유효기간
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '쿠폰 코드 (회원에게 전달용. 예: WELCOME10)',
    name VARCHAR(200) NOT NULL COMMENT '쿠폰명 (노출용)',
    discount_type VARCHAR(20) NOT NULL COMMENT '할인 유형: percent(%), fixed(원)',
    discount_value DECIMAL(12, 0) NOT NULL COMMENT '할인값 (% 또는 원)',
    min_order_amount DECIMAL(12, 0) NOT NULL DEFAULT 0 COMMENT '최소 주문 금액 (원). 이 금액 이상 구매 시 사용 가능',
    max_discount_amount DECIMAL(12, 0) COMMENT '최대 할인 금액 (원). percent일 때 상한',
    valid_from DATETIME NOT NULL COMMENT '사용 가능 시작일',
    valid_until DATETIME NOT NULL COMMENT '사용 가능 종료일',
    total_quantity INT COMMENT '총 발급 수량. NULL이면 무제한',
    used_quantity INT NOT NULL DEFAULT 0 COMMENT '사용된 수량',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '노출/사용 가능 여부',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='쿠폰 (마이페이지 쿠폰)';

-- -----------------------------------------------------------------------------
-- 사용자 쿠폰 (마이페이지 · 쿠폰)
-- 사용자별 보유 쿠폰. 발급일·사용 여부·사용한 주문
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_coupons (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT NOT NULL COMMENT '사용자 FK',
    coupon_id BIGINT NOT NULL COMMENT '쿠폰 FK',
    used_at DATETIME COMMENT '사용 일시. NULL이면 미사용',
    order_id BIGINT COMMENT '사용한 주문 FK. used_at이 NULL이면 NULL',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '발급/다운로드 일시',
    CONSTRAINT fk_user_coupons_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_coupons_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_coupons_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 쿠폰 (마이페이지 쿠폰)';

-- -----------------------------------------------------------------------------
-- 사용자 배송지 (마이페이지 · 배송지 관리)
-- 로그인 사용자가 저장하는 배송지 목록. 주문 시 선택
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_addresses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT NOT NULL COMMENT '사용자 FK',
    label VARCHAR(50) COMMENT '배송지 별칭 (예: 집, 회사)',
    recipient_name VARCHAR(100) NOT NULL COMMENT '수령인',
    phone VARCHAR(20) NOT NULL COMMENT '연락처',
    postal_code VARCHAR(20) COMMENT '우편번호',
    address VARCHAR(500) NOT NULL COMMENT '기본 주소',
    address_detail VARCHAR(200) COMMENT '상세 주소',
    is_default TINYINT(1) NOT NULL DEFAULT 0 COMMENT '기본 배송지 여부 (1:기본)',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 배송지 (마이페이지 배송지 관리)';

-- -----------------------------------------------------------------------------
-- 저장 결제수단 (마이페이지 · 결제수단)
-- 카드 번호 등 실제 결제 정보는 저장하지 않고, PG사 토큰/식별자만 저장
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_payment_methods (
    id BIGINT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id BIGINT NOT NULL COMMENT '사용자 FK',
    pg_provider VARCHAR(50) NOT NULL COMMENT '결제 PG사 식별자 (예: toss, nice, kakao)',
    display_name VARCHAR(100) COMMENT '사용자 지정 별칭 (예: 개인 카드)',
    masked_info VARCHAR(100) COMMENT '마스킹된 표시 정보 (예: ****-****-****-1234)',
    billing_key_or_token VARCHAR(500) COMMENT 'PG사 빌링키/토큰 (암호화 저장 권장)',
    is_default TINYINT(1) NOT NULL DEFAULT 0 COMMENT '기본 결제수단 여부',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    CONSTRAINT fk_user_payment_methods_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='저장 결제수단 (마이페이지 결제수단)';

-- =============================================================================
-- Django 내부·관리자 (auth, django_*)
-- Django가 자동 생성하는 테이블. /admin/ 로그인·권한·마이그레이션·세션·관리 로그용
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 콘텐츠 타입 (앱·모델별 ID. 권한·admin 로그에서 참조)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS django_content_type (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    app_label VARCHAR(100) NOT NULL COMMENT '앱 이름 (예: shop, auth)',
    model VARCHAR(100) NOT NULL COMMENT '모델 이름 (소문자)',
    UNIQUE KEY uk_django_content_type_app_model (app_label, model) COMMENT '앱+모델 유일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 콘텐츠 타입 (앱·모델 식별)';

-- -----------------------------------------------------------------------------
-- Django 관리자 사용자 (/admin/ 로그인용. schema.sql의 users와 별도)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_user (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    password VARCHAR(128) NOT NULL COMMENT '해시된 비밀번호',
    last_login DATETIME(6) COMMENT '마지막 로그인 시각',
    is_superuser TINYINT(1) NOT NULL DEFAULT 0 COMMENT '슈퍼유저 여부 (1:전체 권한)',
    username VARCHAR(150) NOT NULL COMMENT '로그인 ID',
    first_name VARCHAR(150) NOT NULL DEFAULT '' COMMENT '이름',
    last_name VARCHAR(150) NOT NULL DEFAULT '' COMMENT '성',
    email VARCHAR(254) NOT NULL DEFAULT '' COMMENT '이메일',
    is_staff TINYINT(1) NOT NULL DEFAULT 0 COMMENT '스태프 여부 (1:admin 접근 가능)',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 여부 (0:비활성)',
    date_joined DATETIME(6) NOT NULL COMMENT '가입일',
    UNIQUE KEY uk_auth_user_username (username) COMMENT '로그인 ID 유일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 관리자 사용자 (/admin/ 로그인)';

-- -----------------------------------------------------------------------------
-- 권한 그룹 (역할 묶음)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_group (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    name VARCHAR(150) NOT NULL COMMENT '그룹명',
    UNIQUE KEY uk_auth_group_name (name) COMMENT '그룹명 유일'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 권한 그룹 (역할)';

-- -----------------------------------------------------------------------------
-- 권한 (모델별 add/change/delete/view. content_type 참조)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_permission (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    name VARCHAR(255) NOT NULL COMMENT '권한 표시명',
    content_type_id INT NOT NULL COMMENT 'django_content_type FK (대상 모델)',
    codename VARCHAR(100) NOT NULL COMMENT '권한 코드 (예: add_category)',
    UNIQUE KEY uk_auth_permission_content_codename (content_type_id, codename) COMMENT '모델+코드 유일',
    CONSTRAINT fk_auth_permission_content_type FOREIGN KEY (content_type_id) REFERENCES django_content_type(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 권한 (모델별 CRUD 등)';

-- -----------------------------------------------------------------------------
-- 그룹-권한 M:N (그룹에 부여된 권한 목록)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_group_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    group_id INT NOT NULL COMMENT 'auth_group FK',
    permission_id INT NOT NULL COMMENT 'auth_permission FK',
    UNIQUE KEY uk_auth_group_permissions_group_perm (group_id, permission_id) COMMENT '그룹+권한 유일',
    CONSTRAINT fk_auth_group_permissions_group FOREIGN KEY (group_id) REFERENCES auth_group(id) ON DELETE CASCADE,
    CONSTRAINT fk_auth_group_permissions_permission FOREIGN KEY (permission_id) REFERENCES auth_permission(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 그룹-권한 M:N';

-- -----------------------------------------------------------------------------
-- 사용자-그룹 M:N (사용자가 속한 그룹)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_user_groups (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id INT NOT NULL COMMENT 'auth_user FK',
    group_id INT NOT NULL COMMENT 'auth_group FK',
    UNIQUE KEY uk_auth_user_groups_user_group (user_id, group_id) COMMENT '사용자+그룹 유일',
    CONSTRAINT fk_auth_user_groups_user FOREIGN KEY (user_id) REFERENCES auth_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_auth_user_groups_group FOREIGN KEY (group_id) REFERENCES auth_group(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 사용자-그룹 M:N';

-- -----------------------------------------------------------------------------
-- 사용자-권한 M:N (사용자에게 직접 부여한 권한)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_user_user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    user_id INT NOT NULL COMMENT 'auth_user FK',
    permission_id INT NOT NULL COMMENT 'auth_permission FK',
    UNIQUE KEY uk_auth_user_user_permissions_user_perm (user_id, permission_id) COMMENT '사용자+권한 유일',
    CONSTRAINT fk_auth_user_user_permissions_user FOREIGN KEY (user_id) REFERENCES auth_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_auth_user_user_permissions_permission FOREIGN KEY (permission_id) REFERENCES auth_permission(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 사용자-권한 M:N';

-- -----------------------------------------------------------------------------
-- 마이그레이션 기록 (migrate 적용 이력. Django가 자동 관리)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS django_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    app VARCHAR(255) NOT NULL COMMENT '앱 이름',
    name VARCHAR(255) NOT NULL COMMENT '마이그레이션 파일명',
    applied DATETIME(6) NOT NULL COMMENT '적용 시각'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 마이그레이션 적용 이력';

-- -----------------------------------------------------------------------------
-- 세션 (로그인 세션·중간 데이터 저장)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS django_session (
    session_key VARCHAR(40) NOT NULL PRIMARY KEY COMMENT '세션 키',
    session_data LONGTEXT NOT NULL COMMENT '세션 데이터 (직렬화)',
    expire_date DATETIME(6) NOT NULL COMMENT '만료 시각'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 세션 (로그인·세션 데이터)';

-- -----------------------------------------------------------------------------
-- 관리자 동작 로그 (/admin/에서 추가·수정·삭제 이력)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS django_admin_log (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK',
    action_time DATETIME(6) NOT NULL COMMENT '동작 시각',
    object_id LONGTEXT COMMENT '대상 객체 ID',
    object_repr VARCHAR(200) NOT NULL COMMENT '대상 객체 표시 문자열',
    action_flag SMALLINT UNSIGNED NOT NULL COMMENT '동작 구분 (1:추가, 2:변경, 3:삭제)',
    change_message LONGTEXT NOT NULL DEFAULT '' COMMENT '변경 내용 요약',
    content_type_id INT COMMENT 'django_content_type FK (대상 모델). NULL이면 전체 삭제 등',
    user_id INT NOT NULL COMMENT 'auth_user FK (동작한 관리자)',
    CONSTRAINT fk_django_admin_log_content_type FOREIGN KEY (content_type_id) REFERENCES django_content_type(id) ON DELETE SET NULL,
    CONSTRAINT fk_django_admin_log_user FOREIGN KEY (user_id) REFERENCES auth_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Django 관리자 동작 로그 (/admin/ 변경 이력)';

-- =============================================================================
-- 인덱스 (조회 성능)
-- =============================================================================
CREATE INDEX idx_delivery_fee_templates_active ON delivery_fee_templates(is_active) COMMENT '사용 중인 배송비 템플릿';
CREATE INDEX idx_address_codes_type ON address_codes(address_type) COMMENT '출고지/반품지 구분 조회';
CREATE INDEX idx_products_category ON products(category_id) COMMENT '카테고리별 상품 조회';
CREATE INDEX idx_products_delivery_template ON products(delivery_fee_template_id) COMMENT '상품별 배송비 템플릿';
CREATE INDEX idx_products_is_active ON products(is_active) COMMENT '노출 상품 조회';
CREATE INDEX idx_product_images_product ON product_images(product_id) COMMENT '상품별 이미지 조회';
CREATE INDEX idx_option_masters_product ON option_masters(product_id) COMMENT '상품별 옵션 그룹 조회';
CREATE INDEX idx_option_items_master ON option_items(option_master_id) COMMENT '옵션 그룹별 항목 조회';
CREATE INDEX idx_product_skus_product ON product_skus(product_id) COMMENT '상품별 SKU 조회';
CREATE INDEX idx_product_skus_status ON product_skus(sell_status) COMMENT '판매상태별 SKU 조회';
CREATE INDEX idx_product_text_option_specs_product ON product_text_option_specs(product_id) COMMENT '상품별 직접입력 스펙 조회';
CREATE INDEX idx_orders_user ON orders(user_id) COMMENT '사용자별 주문 조회';
CREATE INDEX idx_orders_status ON orders(status) COMMENT '상태별 주문 조회';
CREATE INDEX idx_orders_created ON orders(created_at) COMMENT '주문일 기준 조회';
CREATE INDEX idx_orders_delivery_template ON orders(delivery_fee_template_id) COMMENT '주문별 배송비 템플릿';
CREATE INDEX idx_orders_warehouse_address ON orders(warehouse_address_code_id) COMMENT '주문별 출고지';
CREATE INDEX idx_orders_return_address ON orders(return_address_code_id) COMMENT '주문별 반품지';
CREATE INDEX idx_order_items_order ON order_items(order_id) COMMENT '주문별 상품 조회';
CREATE INDEX idx_order_items_sku ON order_items(sku_id) COMMENT '주문 상품-SKU 조회';
CREATE INDEX idx_cart_items_user ON cart_items(user_id) COMMENT '사용자별 장바구니 조회';
CREATE INDEX idx_cart_items_product ON cart_items(product_id) COMMENT '장바구니 상품 조회';
CREATE INDEX idx_wishlists_user ON wishlists(user_id) COMMENT '사용자별 찜 조회';
CREATE INDEX idx_reviews_product ON reviews(product_id) COMMENT '상품별 리뷰 조회';
CREATE INDEX idx_reviews_user ON reviews(user_id) COMMENT '마이페이지 리뷰 관리';
CREATE INDEX idx_inquiries_product ON inquiries(product_id) COMMENT '상품별 문의 조회';
CREATE INDEX idx_inquiries_user ON inquiries(user_id) COMMENT '마이페이지 1:1 문의';
CREATE INDEX idx_coupons_code ON coupons(code) COMMENT '쿠폰 코드 조회';
CREATE INDEX idx_coupons_valid ON coupons(valid_from, valid_until) COMMENT '쿠폰 유효기간 조회';
CREATE INDEX idx_user_coupons_user ON user_coupons(user_id) COMMENT '마이페이지 쿠폰';
CREATE INDEX idx_user_coupons_coupon ON user_coupons(coupon_id) COMMENT '쿠폰별 사용자';
CREATE INDEX idx_user_addresses_user ON user_addresses(user_id) COMMENT '마이페이지 배송지 관리';
CREATE INDEX idx_user_payment_methods_user ON user_payment_methods(user_id) COMMENT '마이페이지 결제수단';
-- Django auth / django_* (선택. Django migrate 시 자동 생성되는 인덱스 보완)
CREATE INDEX idx_django_session_expire ON django_session(expire_date) COMMENT '세션 만료 조회';
CREATE INDEX idx_django_admin_log_user ON django_admin_log(user_id) COMMENT '관리자별 로그 조회';
CREATE INDEX idx_django_admin_log_content_type ON django_admin_log(content_type_id) COMMENT '모델별 로그 조회';
CREATE INDEX idx_django_admin_log_action_time ON django_admin_log(action_time) COMMENT '시각별 로그 조회';
