-- =============================================================================
-- 카테고리 시드 데이터 (next.haru.company 홈 화면 카테고리와 동일)
-- categories 테이블: name, slug(UNIQUE), icon, sort_order
-- =============================================================================

INSERT INTO categories (name, slug, icon, sort_order) VALUES
('상의',   'tops',      'shirt',        1),
('하의',   'bottoms',   'layout-grid',  2),
('원피스', 'dress',     'circle-dot',   3),
('스커트', 'skirt',     'diamond',      4),
('주얼리', 'jewelry',   'gem',          5),
('가방',   'bag',       'shopping-bag', 6),
('신발',   'shoes',     'footprints',   7);
