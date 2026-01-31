"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  Shirt,
  LayoutGrid,
  CircleDot,
  Diamond,
  Gem,
  ShoppingBag,
  Footprints,
  Heart,
  type LucideIcon,
} from "lucide-react";
import toast from "react-hot-toast";
import { getCategories, getProducts, getBanners, getWishlist, addWishlist, removeWishlist, type CategoryDto, type ProductListItemDto, type BannerDto } from "@/lib/api";

const CATEGORY_ICON_SIZE = 28;

const ICON_MAP: Record<string, LucideIcon> = {
  shirt: Shirt,
  "layout-grid": LayoutGrid,
  "circle-dot": CircleDot,
  diamond: Diamond,
  gem: Gem,
  "shopping-bag": ShoppingBag,
  footprints: Footprints,
};

function getCategoryIcon(icon: string | null): LucideIcon {
  if (!icon) return LayoutGrid;
  const key = (icon || "").toLowerCase().trim();
  return ICON_MAP[key] ?? LayoutGrid;
}

const PRODUCT_IMG_SIZE = 300;
const PRODUCT_LIMIT = 12;

export default function Home() {
  const router = useRouter();
  const [imageSeed, setImageSeed] = useState(() => Date.now());
  const [categories, setCategories] = useState<CategoryDto[]>([]);
  const [categoriesError, setCategoriesError] = useState<string | null>(null);
  const [banners, setBanners] = useState<BannerDto[]>([]);
  const [products, setProducts] = useState<ProductListItemDto[]>([]);
  const [productsError, setProductsError] = useState<string | null>(null);
  /** 찜한 상품 ID 목록 (홈 카드 하트 표시용) */
  const [wishlistProductIds, setWishlistProductIds] = useState<Set<number>>(new Set());

  useEffect(() => {
    getWishlist()
      .then((list) => setWishlistProductIds(new Set(list.map((w) => w.productId))))
      .catch(() => setWishlistProductIds(new Set()));
  }, []);

  async function handleWishClick(e: React.MouseEvent, productId: number) {
    e.preventDefault();
    e.stopPropagation();
    try {
      if (wishlistProductIds.has(productId)) {
        await removeWishlist(productId);
        setWishlistProductIds((prev) => {
          const next = new Set(prev);
          next.delete(productId);
          return next;
        });
        toast.success("찜 해제했어요");
      } else {
        await addWishlist(productId);
        setWishlistProductIds((prev) => new Set(prev).add(productId));
        toast.success("찜 목록에 담았어요");
      }
    } catch (err) {
      const msg = err instanceof Error ? err.message : "찜하기에 실패했습니다.";
      toast.error(msg);
      if (msg === "로그인이 필요합니다." && typeof window !== "undefined") {
        const go = window.confirm("로그인 페이지로 이동할까요?");
        if (go) router.push(`/login?next=${encodeURIComponent("/")}`);
      }
    }
  }

  useEffect(() => {
    const interval = setInterval(() => {
      setImageSeed(Date.now());
    }, 5000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    getCategories()
      .then(setCategories)
      .catch((err) => setCategoriesError(err instanceof Error ? err.message : "카테고리를 불러올 수 없습니다."));
  }, []);

  useEffect(() => {
    getBanners()
      .then(setBanners)
      .catch(() => setBanners([]));
  }, []);

  useEffect(() => {
    getProducts({ limit: PRODUCT_LIMIT, offset: 0 })
      .then(setProducts)
      .catch((err) => setProductsError(err instanceof Error ? err.message : "상품 목록을 불러올 수 없습니다."));
  }, []);

  return (
    <div className="container-fluid px-3 py-3 home-page">
      {/* 히어로 캐러셀 (Spring 배너 API) */}
      <section className="home-hero mb-4">
        <div
          id="homeCarousel"
          className="carousel slide home-carousel rounded-3 overflow-hidden shadow-sm"
          data-bs-ride="carousel"
        >
          <div className="carousel-inner">
            {(banners.length > 0 ? banners : [{ imageUrl: "https://picsum.photos/id/1/1200/400", linkUrl: null }]).map((b, i) => (
              <div
                key={i}
                className={`carousel-item ${i === 0 ? "active" : ""}`}
              >
                {b.linkUrl ? (
                  <a href={b.linkUrl}>
                    <img src={b.imageUrl} className="d-block w-100 carousel-img" alt="" />
                  </a>
                ) : (
                  <img src={b.imageUrl} className="d-block w-100 carousel-img" alt="" />
                )}
              </div>
            ))}
          </div>
          {banners.length > 1 && (
            <>
              <button className="carousel-control-prev carousel-btn" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev" aria-label="이전">
                <span className="carousel-control-prev-icon" aria-hidden="true" />
              </button>
              <button className="carousel-control-next carousel-btn" type="button" data-bs-target="#homeCarousel" data-bs-slide="next" aria-label="다음">
                <span className="carousel-control-next-icon" aria-hidden="true" />
              </button>
              <div className="carousel-indicators home-carousel-indicators">
                {banners.map((_, i) => (
                  <button key={i} type="button" data-bs-target="#homeCarousel" data-bs-slide-to={i} className={i === 0 ? "active" : ""} aria-label={`슬라이드 ${i + 1}`} />
                ))}
              </div>
            </>
          )}
        </div>
      </section>

      {/* 카테고리 (Spring API / DB) */}
      <section className="home-categories mb-4">
        <h2 className="home-section-title small text-uppercase text-secondary mb-3">
          카테고리
        </h2>
        {categoriesError && (
          <p className="small text-danger mb-2">{categoriesError}</p>
        )}
        <div className="d-flex flex-wrap justify-content-center gap-2 gap-md-3">
          {categories.length === 0 && !categoriesError && (
            <span className="small text-secondary">로딩 중...</span>
          )}
          {categories.map((cat) => {
            const Icon = getCategoryIcon(cat.icon);
            return (
              <Link
                key={cat.id}
                href={`/category?slug=${encodeURIComponent(cat.slug)}`}
                className="home-category-btn border-0 rounded-pill bg-white shadow-sm d-flex flex-column align-items-center justify-content-center text-secondary text-decoration-none"
                style={{ width: 72, height: 72 }}
              >
                <Icon
                  size={CATEGORY_ICON_SIZE}
                  className="d-block"
                  strokeWidth={1.5}
                />
                <span className="small mt-1">{cat.name}</span>
              </Link>
            );
          })}
        </div>
      </section>

      {/* 추천 상품 (Spring API) */}
      <section className="home-products">
        <h2 className="home-section-title small text-uppercase text-secondary mb-3">
          추천 상품
        </h2>
        {productsError && <p className="small text-danger mb-2">{productsError}</p>}
        {!productsError && products.length === 0 && <p className="small text-secondary">로딩 중...</p>}
        <div className="row g-3 g-md-4">
          {products.map((product) => (
            <div key={product.id} className="col-6 col-md-4 col-lg-3">
              <Link href={`/product/${product.id}`} className="text-decoration-none text-dark d-block h-100">
                <article className="home-product-card card border-0 h-100 rounded-3 overflow-hidden shadow-sm">
                  <div className="position-relative overflow-hidden home-product-img-wrap" style={{ aspectRatio: "1" }}>
                    <img
                      src={product.imageUrl || `https://picsum.photos/seed/p${product.id}/${PRODUCT_IMG_SIZE}/${PRODUCT_IMG_SIZE}`}
                      className="w-100 h-100 home-product-img"
                      alt={product.name}
                      style={{ objectFit: "cover" }}
                    />
                    <button
                      type="button"
                      className="product-wish-btn position-absolute bottom-0 end-0 m-2 p-2 rounded-circle bg-white border-0 shadow-sm d-flex align-items-center justify-content-center"
                      aria-label={wishlistProductIds.has(product.id) ? "찜 해제" : "찜하기"}
                      onClick={(e) => handleWishClick(e, product.id)}
                    >
                      <Heart
                        size={18}
                        strokeWidth={2}
                        className="text-danger"
                        fill={wishlistProductIds.has(product.id) ? "currentColor" : "none"}
                      />
                    </button>
                  </div>
                  <div className="card-body p-3">
                    <h3 className="home-product-name card-title small mb-2 text-truncate fw-normal" title={product.name}>
                      {product.name}
                    </h3>
                    <p className="home-product-price small fw-semibold text-dark mb-0">
                      {Number(product.price).toLocaleString()}원
                    </p>
                  </div>
                </article>
              </Link>
            </div>
          ))}
        </div>
      </section>
      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
