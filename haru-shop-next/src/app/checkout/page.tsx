"use client";

import { useState, useEffect, Suspense } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import {
  getCart,
  createOrder,
  type CartItemDto,
} from "@/lib/api";

const DELIVERY_FREE_THRESHOLD = 50000;
const DELIVERY_FEE = 3000;

function formatPrice(n: number) {
  return n.toLocaleString();
}

/** 상품 상세 "구매하기" → cartItemId만 결제 / 장바구니 진입 → fromProduct(해당 상품만) 또는 전체 */
function CheckoutContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const cartItemIdParam = searchParams.get("cartItemId");
  const singleCartItemId = cartItemIdParam ? Number(cartItemIdParam) : null;
  const fromProductId = searchParams.get("fromProduct")
    ? Number(searchParams.get("fromProduct"))
    : null;

  const [cartItems, setCartItems] = useState<CartItemDto[]>([]);
  /** 결제에 포함할 장바구니 항목 ID 집합 */
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [receiverName, setReceiverName] = useState("");
  const [receiverPhone, setReceiverPhone] = useState("");
  const [receiverAddress, setReceiverAddress] = useState("");

  /** 목록에 보여줄 항목: 상세 구매하기(cartItemId) → 해당 한 건만, fromProduct → 해당 상품만, 아니면 전체 */
  const displayItems =
    singleCartItemId != null
      ? cartItems.filter((item) => item.id === singleCartItemId)
      : fromProductId != null
        ? cartItems.filter((item) => item.productId === fromProductId)
        : cartItems;

  const isSingleItemFromDetail = singleCartItemId != null;

  /** 선택된 항목만 주문에 포함 */
  const orderItems = cartItems.filter((item) => selectedIds.has(item.id));

  const subtotal = orderItems.reduce(
    (sum, p) => sum + p.price * p.quantity,
    0
  );
  const deliveryFee =
    subtotal >= DELIVERY_FREE_THRESHOLD ? 0 : DELIVERY_FEE;
  const totalAmount = subtotal;
  const total = subtotal + deliveryFee;

  const toggleSelect = (id: number) => {
    setSelectedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const toggleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedIds(new Set(displayItems.map((item) => item.id)));
    } else {
      setSelectedIds(new Set());
    }
  };

  const allSelected =
    displayItems.length > 0 && displayItems.every((item) => selectedIds.has(item.id));
  const someSelected = displayItems.some((item) => selectedIds.has(item.id));

  useEffect(() => {
    if (typeof window !== "undefined" && !localStorage.getItem("harushop_token")) {
      const q = singleCartItemId != null ? `cartItemId=${singleCartItemId}` : fromProductId != null ? `fromProduct=${fromProductId}` : "";
      const next = q ? `/checkout?${q}` : "/checkout";
      router.replace(`/login?next=${encodeURIComponent(next)}`);
      return;
    }
    let mounted = true;
    setLoading(true);
    getCart()
      .then((data) => {
        if (mounted) {
          setCartItems(data);
          if (data.length === 0) {
            router.replace("/cart");
          } else if (singleCartItemId != null) {
            const exists = data.some((i) => i.id === singleCartItemId);
            setSelectedIds(exists ? new Set([singleCartItemId]) : new Set());
          } else if (fromProductId != null) {
            const ids = data.filter((i) => i.productId === fromProductId).map((i) => i.id);
            setSelectedIds(new Set(ids));
          } else {
            setSelectedIds(new Set(data.map((item) => item.id)));
          }
        }
      })
      .catch((e) => {
        if (mounted) {
          setError(e instanceof Error ? e.message : "장바구니를 불러오지 못했습니다.");
          setCartItems([]);
        }
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, [router, singleCartItemId, fromProductId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (orderItems.length === 0) {
      setError("주문할 상품을 하나 이상 선택해 주세요.");
      return;
    }
    if (!receiverName.trim() || !receiverPhone.trim() || !receiverAddress.trim()) {
      setError("수령인 이름, 연락처, 주소를 모두 입력해 주세요.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const itemsForOrder = orderItems.map((item) => ({
        productId: item.productId,
        skuId: item.skuId ?? undefined,
        productName: item.productName,
        price: item.price,
        quantity: item.quantity,
        optionText: item.optionText ?? undefined,
        selectedOptions: item.selectedOptions ?? undefined,
      }));
      const result = await createOrder({
        items: itemsForOrder,
        totalAmount,
        deliveryFee,
        receiverName: receiverName.trim(),
        receiverPhone: receiverPhone.trim(),
        receiverAddress: receiverAddress.trim(),
      });
      router.push(`/orders?ordered=${encodeURIComponent(result.orderNumber)}`);
    } catch (e) {
      setError(e instanceof Error ? e.message : "주문에 실패했습니다.");
    } finally {
      setSubmitting(false);
    }
  };

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

  if (cartItems.length === 0 && !error) {
    return null;
  }

  if ((singleCartItemId != null || fromProductId != null) && displayItems.length === 0) {
    return (
      <div className="container-fluid px-3 py-3 pb-4">
        <h1 className="h4 mb-3 fw-semibold">주문/결제</h1>
        <div className="alert alert-info py-3 small">
          해당 상품이 장바구니에 없습니다. 상품 상세에서 다시 &quot;구매하기&quot;를 눌러 주세요.
        </div>
        <Link href="/cart" className="btn btn-primary rounded-pill">
          장바구니로 가기
        </Link>
      </div>
    );
  }

  if (cartItems.length === 0) {
    return (
      <div className="container-fluid px-3 py-3 pb-4">
        <div className="alert alert-warning">{error}</div>
        <Link href="/cart" className="btn btn-primary">
          장바구니로 가기
        </Link>
      </div>
    );
  }

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <h1 className="h4 mb-3 fw-semibold">주문/결제</h1>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* 주문 상품 선택 */}
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
          <div className="card-body py-2 px-3 border-bottom bg-light d-flex align-items-center justify-content-between">
            <span className="fw-semibold small">
              주문 상품
              {isSingleItemFromDetail && (
                <span className="text-primary ms-2">(상품 상세에서 선택한 상품만)</span>
              )}
            </span>
            {!isSingleItemFromDetail && fromProductId == null && (
              <label className="form-check form-check-inline mb-0 small">
                <input
                  type="checkbox"
                  className="form-check-input"
                  checked={allSelected}
                  ref={(el) => {
                    if (el) (el as HTMLInputElement).indeterminate = someSelected && !allSelected;
                  }}
                  onChange={(e) => toggleSelectAll(e.target.checked)}
                />
                <span className="form-check-label">전체 선택</span>
              </label>
            )}
          </div>
          <ul className="list-group list-group-flush">
            {displayItems.map((item) => {
              const checked = selectedIds.has(item.id);
              return (
                <li
                  key={item.id}
                  className="list-group-item border-0 border-bottom px-3 py-2 d-flex align-items-center gap-2"
                >
                  {!isSingleItemFromDetail && fromProductId == null ? (
                    <>
                      <input
                        type="checkbox"
                        className="form-check-input flex-shrink-0 mt-0"
                        id={`check-${item.id}`}
                        checked={checked}
                        onChange={() => toggleSelect(item.id)}
                      />
                      <label
                        htmlFor={`check-${item.id}`}
                        className="small text-truncate flex-grow-1 mb-0 me-2 cursor-pointer"
                        style={{ cursor: "pointer" }}
                      >
                        {item.productName} × {item.quantity}
                      </label>
                    </>
                  ) : (
                    <span className="small text-truncate flex-grow-1 me-2">
                      {item.productName} × {item.quantity}
                    </span>
                  )}
                  <span className="small fw-semibold flex-shrink-0">
                    {formatPrice(item.price * item.quantity)}원
                  </span>
                </li>
              );
            })}
          </ul>
          {!isSingleItemFromDetail && fromProductId == null && (
            <div className="card-body py-2 px-3 bg-light small text-secondary">
              결제할 상품만 선택하세요. 선택한 상품만 주문됩니다. ({orderItems.length}건 선택)
            </div>
          )}
          {(isSingleItemFromDetail || fromProductId != null) && (
            <div className="card-body py-2 px-3 bg-light small">
              <Link href="/checkout" className="text-primary text-decoration-none">
                장바구니 전체에서 선택하기
              </Link>
            </div>
          )}
        </div>

        {/* 수령인 정보 */}
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
          <div className="card-body">
            <h2 className="h6 fw-semibold mb-3">수령인 정보</h2>
            <div className="mb-2">
              <label className="form-label small mb-1">이름 *</label>
              <input
                type="text"
                className="form-control form-control-sm"
                value={receiverName}
                onChange={(e) => setReceiverName(e.target.value)}
                placeholder="수령인 이름"
                required
              />
            </div>
            <div className="mb-2">
              <label className="form-label small mb-1">연락처 *</label>
              <input
                type="tel"
                className="form-control form-control-sm"
                value={receiverPhone}
                onChange={(e) => setReceiverPhone(e.target.value)}
                placeholder="010-0000-0000"
                required
              />
            </div>
            <div className="mb-0">
              <label className="form-label small mb-1">주소 *</label>
              <textarea
                className="form-control form-control-sm"
                rows={3}
                value={receiverAddress}
                onChange={(e) => setReceiverAddress(e.target.value)}
                placeholder="배송 받을 주소"
                required
              />
            </div>
          </div>
        </div>

        {/* 결제 금액 */}
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
            <hr className="my-2" />
            <div className="d-flex justify-content-between fw-semibold">
              <span>총 결제금액</span>
              <span className="text-primary">{formatPrice(total)}원</span>
            </div>
          </div>
        </div>

        <div className="d-flex gap-2">
          <Link
            href="/cart"
            className="btn btn-outline-secondary rounded-pill py-2 flex-grow-1"
          >
            장바구니로
          </Link>
          <button
            type="submit"
            className="btn btn-primary rounded-pill py-2 flex-grow-1 fw-semibold"
            disabled={submitting}
          >
            {submitting ? "처리 중…" : "주문하기"}
          </button>
        </div>
      </form>

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}

export default function CheckoutPage() {
  return (
    <Suspense
      fallback={
        <div className="container-fluid px-3 py-3 pb-4">
          <div className="d-flex align-items-center justify-content-center py-5">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">로딩 중</span>
            </div>
          </div>
        </div>
      }
    >
      <CheckoutContent />
    </Suspense>
  );
}
