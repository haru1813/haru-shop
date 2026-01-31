"use client";

import { useState, useEffect, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import { getProducts, type ProductListItemDto } from "@/lib/api";

const PRODUCT_IMG_SIZE = 300;

function SearchContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const q = searchParams.get("q") ?? "";
  const [inputValue, setInputValue] = useState(q);
  const [products, setProducts] = useState<ProductListItemDto[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setInputValue(q);
  }, [q]);

  useEffect(() => {
    if (!q.trim()) {
      setProducts([]);
      setError(null);
      return;
    }
    setLoading(true);
    setError(null);
    getProducts({ search: q.trim(), limit: 50, offset: 0 })
      .then(setProducts)
      .catch((e) => {
        setError(e instanceof Error ? e.message : "검색에 실패했습니다.");
        setProducts([]);
      })
      .finally(() => setLoading(false));
  }, [q]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const v = (e.target as HTMLFormElement).querySelector<HTMLInputElement>("input[name=q]")?.value?.trim();
    if (v) router.push(`/search?q=${encodeURIComponent(v)}`);
  };

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <h1 className="h4 mb-0">검색</h1>

      <form onSubmit={handleSearch} className="d-flex gap-2 mt-3 mb-3">
        <input
          type="search"
          name="q"
          className="form-control rounded-pill"
          placeholder="상품명 검색"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          aria-label="검색어"
        />
        <button type="submit" className="btn btn-primary rounded-pill px-4">
          검색
        </button>
      </form>

      {q.trim() ? (
        <p className="text-secondary small mb-3">
          &quot;{q}&quot; 검색 결과 {loading ? "조회 중…" : `${products.length}건`}
        </p>
      ) : (
        <p className="text-secondary small mb-3">검색어를 입력한 뒤 검색 버튼을 눌러 주세요.</p>
      )}

      {error && (
        <div className="alert alert-warning py-2 small" role="alert">
          {error}
        </div>
      )}

      {loading && (
        <div className="d-flex align-items-center justify-content-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">로딩 중</span>
          </div>
        </div>
      )}

      {!loading && q.trim() && products.length === 0 && !error && (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5">
            <p className="text-secondary small mb-0">검색 결과가 없습니다.</p>
            <Link href="/" className="btn btn-outline-primary btn-sm mt-3 rounded-pill">
              홈으로
            </Link>
          </div>
        </div>
      )}

      {!loading && products.length > 0 && (
        <div className="row g-3 g-md-4">
          {products.map((product) => (
            <div key={product.id} className="col-6 col-md-4 col-lg-3">
              <Link href={`/product/${product.id}`} className="text-decoration-none text-dark d-block h-100">
                <article className="card border-0 h-100 rounded-3 overflow-hidden shadow-sm">
                  <div className="position-relative overflow-hidden" style={{ aspectRatio: "1" }}>
                    <img
                      src={product.imageUrl || `https://picsum.photos/seed/p${product.id}/${PRODUCT_IMG_SIZE}/${PRODUCT_IMG_SIZE}`}
                      className="w-100 h-100"
                      alt={product.name}
                      style={{ objectFit: "cover" }}
                    />
                  </div>
                  <div className="card-body p-3">
                    <h2 className="card-title small mb-1 text-truncate fw-normal" title={product.name}>
                      {product.name}
                    </h2>
                    <p className="small fw-semibold text-dark mb-0">
                      {Number(product.price).toLocaleString()}원
                    </p>
                  </div>
                </article>
              </Link>
            </div>
          ))}
        </div>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}

export default function SearchPage() {
  return (
    <Suspense
      fallback={
        <div className="container-fluid px-3 py-3">
          <h1 className="h4 mb-0">검색</h1>
          <p className="text-secondary small mt-2">로딩 중…</p>
        </div>
      }
    >
      <SearchContent />
    </Suspense>
  );
}
