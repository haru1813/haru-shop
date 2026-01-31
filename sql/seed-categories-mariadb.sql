-- 카테고리 시드 (schema.sql 테이블명 categories = Django db_table 'categories')
-- 실행: docker compose exec -T mariadb mariadb -u harushop -pharushop harushop < docs/seed-categories-mariadb.sql
-- 또는: ./docs/seed-categories-run.sh (haru-shop 루트에서)

INSERT IGNORE INTO categories (name, slug, icon, sort_order) VALUES
('상의',   'tops',      'shirt',        1),
('하의',   'bottoms',   'layout-grid',  2),
('원피스', 'dress',     'circle-dot',   3),
('스커트', 'skirt',     'diamond',      4),
('주얼리', 'jewelry',   'gem',          5),
('가방',   'bag',       'shopping-bag', 6),
('신발',   'shoes',     'footprints',   7);
