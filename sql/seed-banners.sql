-- =============================================================================
-- 배너 시드 데이터 (메인 캐러셀)
-- banners: 이미지 URL은 picsum.photos 플레이스홀더 사용
-- =============================================================================

INSERT INTO banners (
    image_url,
    link_url,
    sort_order,
    is_active
) VALUES
('https://picsum.photos/1200/400?random=1', '/category?slug=tops', 1, 1),
('https://picsum.photos/1200/400?random=2', '/category?slug=bottoms', 2, 1),
('https://picsum.photos/1200/400?random=3', '/category?slug=dress', 3, 1),
('https://picsum.photos/1200/400?random=4', NULL, 4, 1),
('https://picsum.photos/1200/400?random=5', '/', 5, 1);
