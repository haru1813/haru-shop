"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import toast from "react-hot-toast";
import {
  getWishlist,
  removeWishlist,
  addCart,
  type WishlistItemDto,
} from "@/lib/api";

const IMG_SIZE = 400;

function formatPrice(n: number) {
  return n.toLocaleString();
}

export default function WishlistPage() {
  const [items, setItems] = useState<WishlistItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [removing, setRemoving] = useState<number | null>(null);
  const [addingCart, setAddingCart] = useState<number | null>(null);

  const loadWishlist = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await getWishlist();
      setItems(data);
    } catch (e) {
      setError(e instanceof Error ? e.message : "찜 목록을 불러오지 못했습니다.");
      setItems([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadWishlist();
  }, []);

  const removeItem = async (productId: number) => {
    setRemoving(productId);
    try {
      await removeWishlist(productId);
      setItems((prev) => prev.filter((p) => p.productId !== productId));
      toast.success("찜 해제했어요");
    } catch (e) {
      const msg = e instanceof Error ? e.message : "찜 해제에 실패했습니다.";
      setError(msg);
      toast.error(msg);
    } finally {
      setRemoving(null);
    }
  };

  const addToCart = async (productId: number) => {
    setAddingCart(productId);
    try {
      await addCart({ productId, quantity: 1 });
      setError(null);
      toast.success("장바구니에 담았어요");
    } catch (e) {
      const msg = e instanceof Error ? e.message : "장바구니 담기에 실패했습니다.";
      setError(msg);
      toast.error(msg);
    } finally {
      setAddingCart(null);
    }
  };

  const isEmpty = items.length === 0;

  if (loading) {
    return (
      <div className="container-fluid px-3 py-3 pb-4">
        <div className="d-flex align-items-center justify-content-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">로딩 중</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <div className="d-flex align-items-center justify-content-between mb-3">
        <h1 className="h4 mb-0 fw-semibold">찜</h1>
        {!isEmpty && (
          <span className="text-secondary small">{items.length}개 상품</span>
        )}
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {isEmpty ? (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5 px-4">
            <div
              className="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
              style={{ width: 80, height: 80 }}
              aria-hidden
            >
              <i className="bi bi-heart fs-1 text-secondary" />
            </div>
            <h2 className="h5 fw-semibold mb-2">찜한 상품이 없어요</h2>
            <p className="text-secondary small mb-4">
              마음에 드는 상품을 찜해 보세요.
            </p>
            <Link href="/" className="btn btn-primary rounded-pill px-4">
              쇼핑하러 가기
            </Link>
          </div>
        </div>
      ) : (
        <div className="row g-3">
          {items.map((product) => (
            <div key={product.id} className="col-6 col-md-4 col-lg-3">
              <div className="card border-0 shadow-sm h-100 rounded-3 overflow-hidden">
                <Link
                  href={`/product/${product.productId}`}
                  className="position-relative overflow-hidden bg-secondary bg-opacity-10 text-decoration-none d-block"
                  style={{ aspectRatio: "1" }}
                >
                  {product.imageUrl ? (
                    <img
                      src={product.imageUrl}
                      className="w-100 h-100"
                      alt={product.productName}
                      style={{ objectFit: "cover" }}
                    />
                  ) : (
                    <div
                      className="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"
                      style={{ aspectRatio: "1" }}
                    >
                      <i className="bi bi-image fs-1" />
                    </div>
                  )}
                  <button
                    type="button"
                    onClick={(e) => {
                      e.preventDefault();
                      removeItem(product.productId);
                    }}
                    disabled={removing === product.productId}
                    className="position-absolute top-0 end-0 m-2 p-2 rounded-circle bg-white border-0 shadow-sm d-flex align-items-center justify-content-center"
                    aria-label="찜 해제"
                  >
                    <i className="bi bi-heart-fill text-danger fs-6" />
                  </button>
                </Link>
                <div className="card-body p-3">
                  <Link
                    href={`/product/${product.productId}`}
                    className="text-dark text-decoration-none"
                  >
                    <h3
                      className="card-title small mb-1 text-truncate fw-normal"
                      title={product.productName}
                    >
                      {product.productName}
                    </h3>
                  </Link>
                  <div className="d-flex align-items-center justify-content-between">
                    <span className="fw-semibold text-dark">
                      {formatPrice(product.price)}원
                    </span>
                    <button
                      type="button"
                      className="btn btn-outline-primary btn-sm rounded-pill py-1 px-2 small"
                      onClick={() => addToCart(product.productId)}
                      disabled={addingCart === product.productId}
                    >
                      {addingCart === product.productId ? "담는 중…" : "장바구니"}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
