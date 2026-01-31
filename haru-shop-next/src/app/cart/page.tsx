"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import {
  getCart,
  updateCartQuantity,
  removeCart,
  type CartItemDto,
} from "@/lib/api";

const IMG_SIZE = 120;
const DELIVERY_FREE_THRESHOLD = 50000;
const DELIVERY_FEE = 3000;

function formatPrice(n: number) {
  return n.toLocaleString();
}

export default function CartPage() {
  const [items, setItems] = useState<CartItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [updating, setUpdating] = useState<number | null>(null);

  const loadCart = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await getCart();
      setItems(data);
    } catch (e) {
      setError(e instanceof Error ? e.message : "장바구니를 불러오지 못했습니다.");
      setItems([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadCart();
  }, []);

  const updateQuantity = async (id: number, delta: number) => {
    const item = items.find((p) => p.id === id);
    if (!item) return;
    const nextQty = Math.max(1, item.quantity + delta);
    if (nextQty === item.quantity) return;
    setUpdating(id);
    try {
      await updateCartQuantity(id, nextQty);
      setItems((prev) =>
        prev.map((p) => (p.id === id ? { ...p, quantity: nextQty } : p))
      );
    } catch (e) {
      setError(e instanceof Error ? e.message : "수량 변경에 실패했습니다.");
    } finally {
      setUpdating(null);
    }
  };

  const removeItem = async (id: number) => {
    setUpdating(id);
    try {
      await removeCart(id);
      setItems((prev) => prev.filter((p) => p.id !== id));
    } catch (e) {
      setError(e instanceof Error ? e.message : "삭제에 실패했습니다.");
    } finally {
      setUpdating(null);
    }
  };

  const isEmpty = items.length === 0;
  const subtotal = items.reduce(
    (sum, p) => sum + p.price * p.quantity,
    0
  );
  const deliveryFee =
    subtotal >= DELIVERY_FREE_THRESHOLD ? 0 : DELIVERY_FEE;
  const total = subtotal + deliveryFee;

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
        <h1 className="h4 mb-0 fw-semibold">장바구니</h1>
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
              <i className="bi bi-cart3 fs-1 text-secondary" />
            </div>
            <h2 className="h5 fw-semibold mb-2">장바구니가 비어 있어요</h2>
            <p className="text-secondary small mb-4">
              담긴 상품이 없어요. 마음에 드는 상품을 담아 보세요.
            </p>
            <Link href="/" className="btn btn-primary rounded-pill px-4">
              쇼핑하러 가기
            </Link>
          </div>
        </div>
      ) : (
        <>
          <div className="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
            <ul className="list-group list-group-flush">
              {items.map((item) => (
                <li
                  key={item.id}
                  className="list-group-item border-0 border-bottom px-3 py-3 d-flex gap-3 align-items-center"
                >
                  <Link
                    href={`/product/${item.productId}`}
                    className="rounded overflow-hidden bg-secondary bg-opacity-10 flex-shrink-0 text-decoration-none"
                    style={{ width: 72, height: 72 }}
                  >
                    {item.imageUrl ? (
                      <img
                        src={item.imageUrl}
                        alt=""
                        className="w-100 h-100"
                        style={{ objectFit: "cover" }}
                      />
                    ) : (
                      <div
                        className="w-100 h-100 d-flex align-items-center justify-content-center text-secondary"
                        style={{ objectFit: "cover" }}
                      >
                        <i className="bi bi-image" />
                      </div>
                    )}
                  </Link>
                  <div className="flex-grow-1 min-w-0">
                    <Link
                      href={`/product/${item.productId}`}
                      className="text-dark text-decoration-none"
                    >
                      <h3
                        className="small fw-normal mb-1 text-truncate"
                        title={item.productName}
                      >
                        {item.productName}
                      </h3>
                    </Link>
                    {(item.selectedOptions || item.optionText) && (
                      <p className="small text-secondary mb-1">
                        {[item.selectedOptions, item.optionText]
                          .filter(Boolean)
                          .join(" / ")}
                      </p>
                    )}
                    <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div className="d-flex align-items-center gap-1">
                        <button
                          type="button"
                          className="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                          style={{ width: 28, height: 28 }}
                          onClick={() => updateQuantity(item.id, -1)}
                          disabled={updating === item.id || item.quantity <= 1}
                          aria-label="수량 감소"
                        >
                          <i className="bi bi-dash small" />
                        </button>
                        <span
                          className="small px-2"
                          style={{ minWidth: 24, textAlign: "center" }}
                        >
                          {item.quantity}
                        </span>
                        <button
                          type="button"
                          className="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                          style={{ width: 28, height: 28 }}
                          onClick={() => updateQuantity(item.id, 1)}
                          disabled={updating === item.id}
                          aria-label="수량 증가"
                        >
                          <i className="bi bi-plus small" />
                        </button>
                      </div>
                      <span className="fw-semibold small">
                        {formatPrice(item.price * item.quantity)}원
                      </span>
                    </div>
                  </div>
                  <button
                    type="button"
                    className="btn btn-link text-secondary p-0 flex-shrink-0"
                    onClick={() => removeItem(item.id)}
                    disabled={updating === item.id}
                    aria-label="삭제"
                  >
                    <i className="bi bi-trash small" />
                  </button>
                </li>
              ))}
            </ul>
          </div>

          <div className="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
            <div className="card-body py-3">
              <div className="d-flex justify-content-between small text-secondary mb-2">
                <span>상품금액</span>
                <span>{formatPrice(subtotal)}원</span>
              </div>
              <div className="d-flex justify-content-between small text-secondary mb-2">
                <span>배송비</span>
                <span>
                  {deliveryFee === 0 ? (
                    <span className="text-success">무료</span>
                  ) : (
                    `${formatPrice(deliveryFee)}원`
                  )}
                </span>
              </div>
              {subtotal > 0 &&
                subtotal < DELIVERY_FREE_THRESHOLD && (
                  <p className="small text-secondary mb-0 mt-1">
                    {formatPrice(DELIVERY_FREE_THRESHOLD - subtotal)}원 더 담으면
                    무료배송
                  </p>
                )}
              <hr className="my-2" />
              <div className="d-flex justify-content-between fw-semibold">
                <span>총 결제금액</span>
                <span className="text-primary">{formatPrice(total)}원</span>
              </div>
            </div>
          </div>

          <Link
            href="/checkout"
            className="btn btn-primary w-100 rounded-pill py-3 fw-semibold"
          >
            주문하기
          </Link>
        </>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
