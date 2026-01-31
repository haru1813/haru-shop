"use client";

import { useState, useEffect, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { useRouter } from "next/navigation";
import toast from "react-hot-toast";
import {
  getCategoryBySlug,
  getProducts,
  getWishlist,
  addWishlist,
  removeWishlist,
  type CategoryDto,
  type ProductListItemDto,
} from "@/lib/api";

const PRODUCT_IMG_SIZE = 300;

function CategoryContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const slug = searchParams.get("slug") ?? "";
  const [category, setCategory] = useState<CategoryDto | null>(null);
  const [products, setProducts] = useState<ProductListItemDto[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [wishlistProductIds, setWishlistProductIds] = useState<Set<number>>(new Set());

  useEffect(() => {
    getWishlist()
      .then((list) => setWishlistProductIds(new Set(list.map((w) => w.productId))))
      .catch(() => setWishlistProductIds(new Set()));
  }, []);

  useEffect(() => {
    if (!slug) {
      setCategory(null);
      setProducts([]);
      setError("카테고리를 선택해 주세요.");
      return;
    }
    setError(null);
    getCategoryBySlug(slug)
      .then((cat) => {
        setCategory(cat);
        if (cat) {
          return getProducts({ categoryId: cat.id, limit: 50, offset: 0 });
        }
        setProducts([]);
        return [];
      })
      .then((list) => Array.isArray(list) && setProducts(list))
      .catch((err) => setError(err instanceof Error ? err.message : "목록을 불러오지 못했습니다."));
  }, [slug]);

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
        if (go) router.push(`/login?next=${encodeURIComponent(window.location.pathname + "?slug=" + encodeURIComponent(slug))}`);
      }
    }
  }

  if (!slug) {
    return (
      <div className="container-fluid px-3 py-3">
        <div className="d-flex align-items-center gap-2 mb-2">
          <Link href="/" className="btn btn-link btn-sm text-secondary p-0">
            <i className="bi bi-chevron-left" />
          </Link>
          <h1 className="h4 mb-0">카테고리</h1>
        </div>
        <p className="text-secondary small mt-2">상단 카테고리에서 선택해 주세요.</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container-fluid px-3 py-3">
        <div className="d-flex align-items-center gap-2 mb-2">
          <Link href="/" className="btn btn-link btn-sm text-secondary p-0">
            <i className="bi bi-chevron-left" />
          </Link>
          <h1 className="h4 mb-0">카테고리</h1>
        </div>
        <p className="text-danger small mt-2">{error}</p>
        <Link href="/" className="btn btn-outline-primary btn-sm mt-2">홈으로</Link>
      </div>
    );
  }

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <div className="d-flex align-items-center gap-2 mb-2">
        <Link href="/" className="btn btn-link btn-sm text-secondary p-0">
          <i className="bi bi-chevron-left" />
        </Link>
        <h1 className="h4 mb-0">{category?.name ?? "로딩 중..."}</h1>
      </div>
      {category && (
        <div className="row g-3 g-md-4 mt-2">
          {products.map((product) => (
            <div key={product.id} className="col-6 col-md-4 col-lg-3">
              <Link href={`/product/${product.id}`} className="text-decoration-none text-dark d-block h-100">
                <article className="card border-0 h-100 rounded-3 overflow-hidden shadow-sm position-relative">
                  <div className="position-relative overflow-hidden" style={{ aspectRatio: "1" }}>
                    <img
                      src={product.imageUrl || `https://picsum.photos/seed/p${product.id}/${PRODUCT_IMG_SIZE}/${PRODUCT_IMG_SIZE}`}
                      className="w-100 h-100"
                      alt={product.name}
                      style={{ objectFit: "cover" }}
                    />
                    <button
                      type="button"
                      className="position-absolute top-0 end-0 m-2 btn btn-light btn-sm rounded-circle p-2 shadow-sm"
                      style={{ width: 36, height: 36 }}
                      aria-label={wishlistProductIds.has(product.id) ? "찜 해제" : "찜하기"}
                      onClick={(e) => handleWishClick(e, product.id)}
                    >
                      <i
                        className={`bi ${wishlistProductIds.has(product.id) ? "bi-heart-fill text-danger" : "bi-heart"}`}
                        style={{ fontSize: "1.1rem" }}
                      />
                    </button>
                  </div>
                  <div className="card-body p-3">
                    <h2 className="card-title small mb-1 text-truncate fw-normal" title={product.name}>{product.name}</h2>
                    <p className="small fw-semibold text-dark mb-0">{Number(product.price).toLocaleString()}원</p>
                  </div>
                </article>
              </Link>
            </div>
          ))}
        </div>
      )}
      {category && products.length === 0 && <p className="text-secondary small mt-3">등록된 상품이 없습니다.</p>}
    </div>
  );
}

export default function CategoryPage() {
  return (
    <Suspense fallback={<div className="container-fluid px-3 py-3"><p className="small text-secondary">로딩 중...</p></div>}>
      <CategoryContent />
    </Suspense>
  );
}
