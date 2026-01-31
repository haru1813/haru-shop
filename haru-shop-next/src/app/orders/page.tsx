"use client";

import { useState, useEffect, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import toast from "react-hot-toast";
import { getOrders, addCart, type OrderListItemDto, type OrderItemDto } from "@/lib/api";

const STATUS_MAP: Record<string, string> = {
  payment_complete: "결제완료",
  preparing: "배송준비중",
  shipping: "배송중",
  delivered: "배송완료",
};

function formatPrice(n: number) {
  return n.toLocaleString();
}

function formatDate(iso: string) {
  try {
    const d = new Date(iso);
    return d.toLocaleDateString("ko-KR", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  } catch {
    return iso;
  }
}

function formatDateTime(iso: string) {
  try {
    const d = new Date(iso);
    return d.toLocaleString("ko-KR", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return iso;
  }
}

function OrdersContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const orderedNumber = searchParams.get("ordered");
  const [orders, setOrders] = useState<OrderListItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  /** 상세보기 모달에 표시할 주문 */
  const [detailOrder, setDetailOrder] = useState<OrderListItemDto | null>(null);
  /** 재구매 진행 중인 주문 ID */
  const [reorderingId, setReorderingId] = useState<number | null>(null);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError(null);
    getOrders()
      .then((data) => {
        if (mounted) setOrders(data);
      })
      .catch((e) => {
        if (mounted) {
          setError(e instanceof Error ? e.message : "주문 목록을 불러오지 못했습니다.");
          setOrders([]);
        }
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, []);

  const isEmpty = orders.length === 0;

  const handleReorder = async (order: OrderListItemDto) => {
    const items = order.items ?? [];
    const validItems = items.filter((item): item is OrderItemDto & { productId: number } => item.productId != null);
    if (validItems.length === 0) {
      toast.error("담을 수 있는 상품이 없습니다.");
      return;
    }
    setReorderingId(order.id);
    try {
      for (const item of validItems) {
        await addCart({
          productId: item.productId,
          quantity: item.quantity,
          optionText: item.optionText ?? undefined,
          selectedOptions: item.selectedOptions ?? undefined,
        });
      }
      toast.success(`${validItems.length}개 상품을 장바구니에 담았어요.`);
      router.push("/cart");
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "장바구니 담기에 실패했습니다.");
    } finally {
      setReorderingId(null);
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

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <div className="d-flex align-items-center justify-content-between mb-3">
        <h1 className="h4 mb-0 fw-semibold">주문목록</h1>
        {!isEmpty && (
          <span className="text-secondary small">최근 3개월</span>
        )}
      </div>

      {orderedNumber && (
        <div className="alert alert-success py-2 mb-3 small" role="alert">
          주문이 완료되었습니다. (주문번호: {orderedNumber})
        </div>
      )}
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
              <i className="bi bi-receipt fs-1 text-secondary" />
            </div>
            <h2 className="h5 fw-semibold mb-2">주문 내역이 없어요</h2>
            <p className="text-secondary small mb-4">
              주문한 상품은 여기에서 확인할 수 있어요.
            </p>
            <Link href="/" className="btn btn-primary rounded-pill px-4">
              쇼핑하러 가기
            </Link>
          </div>
        </div>
      ) : (
        <div className="d-flex flex-column gap-3">
          {orders.map((order) => (
            <div
              key={order.id}
              className="card border-0 shadow-sm rounded-3 overflow-hidden"
            >
              <div className="card-body py-2 px-3 border-bottom bg-light bg-opacity-50">
                <div className="d-flex flex-wrap align-items-center justify-content-between gap-2">
                  <span className="fw-semibold small">
                    {order.orderNumber}
                  </span>
                  <span className="badge bg-primary bg-opacity-90 rounded-pill small fw-normal">
                    {STATUS_MAP[order.status] ?? order.status}
                  </span>
                </div>
                <span className="text-secondary small">
                  {formatDate(order.createdAt)}
                </span>
              </div>
              <div className="card-body py-3 px-3">
                <div className="d-flex gap-2 mb-3 overflow-auto flex-wrap">
                  {(order.items ?? []).map((item, idx) => (
                    <div
                      key={item.id ?? idx}
                      className="d-flex align-items-center gap-2 flex-shrink-0"
                      style={{ minWidth: 0 }}
                    >
                      <div
                        className="rounded overflow-hidden bg-secondary bg-opacity-10 flex-shrink-0 d-flex align-items-center justify-content-center text-secondary"
                        style={{ width: 56, height: 56 }}
                      >
                        {item.imageUrl ? (
                          <img
                            src={item.imageUrl}
                            alt=""
                            className="w-100 h-100"
                            style={{ objectFit: "cover" }}
                          />
                        ) : (
                          <i className="bi bi-image" />
                        )}
                      </div>
                      <div
                        className="small text-truncate"
                        style={{ maxWidth: 140 }}
                      >
                        {item.productName}
                        {item.quantity > 1 && (
                          <span className="text-secondary">
                            {" "}
                            × {item.quantity}
                          </span>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
                <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <span className="fw-semibold">
                    결제금액{" "}
                    <span className="text-primary">
                      {formatPrice(
                        Number(order.totalAmount) + Number(order.deliveryFee ?? 0)
                      )}
                      원
                    </span>
                  </span>
                  <div className="d-flex gap-2">
                    <button
                      type="button"
                      className="btn btn-outline-secondary btn-sm rounded-pill py-1 px-3 small"
                      onClick={() => setDetailOrder(order)}
                    >
                      상세보기
                    </button>
                    <button
                      type="button"
                      className="btn btn-outline-primary btn-sm rounded-pill py-1 px-3 small"
                      onClick={() => handleReorder(order)}
                      disabled={reorderingId === order.id}
                    >
                      {reorderingId === order.id ? "담는 중…" : "재구매"}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* 주문 상세 모달 */}
      {detailOrder && (
        <div
          className="modal d-block bg-black bg-opacity-50"
          tabIndex={-1}
          role="dialog"
          aria-labelledby="orderDetailModalLabel"
          aria-modal="true"
          onClick={() => setDetailOrder(null)}
        >
          <div
            className="modal-dialog modal-dialog-centered modal-dialog-scrollable"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="modal-content rounded-3 overflow-hidden">
              <div className="modal-header border-0 pb-0">
                <h2 id="orderDetailModalLabel" className="h5 fw-semibold mb-0">
                  주문 상세
                </h2>
                <button
                  type="button"
                  className="btn-close"
                  aria-label="닫기"
                  onClick={() => setDetailOrder(null)}
                />
              </div>
              <div className="modal-body pt-0">
                <div className="mb-3 pb-2 border-bottom">
                  <div className="d-flex justify-content-between align-items-center gap-2 mb-1">
                    <span className="fw-semibold small">{detailOrder.orderNumber}</span>
                    <span className="badge bg-primary bg-opacity-90 rounded-pill small fw-normal">
                      {STATUS_MAP[detailOrder.status] ?? detailOrder.status}
                    </span>
                  </div>
                  <span className="text-secondary small">{formatDateTime(detailOrder.createdAt)}</span>
                </div>
                {detailOrder.receiverName && (
                  <div className="mb-3 small">
                    <span className="text-secondary">수령인</span> {detailOrder.receiverName}
                  </div>
                )}
                <div className="mb-3">
                  <span className="text-secondary small d-block mb-2">주문 상품</span>
                  <ul className="list-unstyled mb-0">
                    {(detailOrder.items ?? []).map((item, idx) => (
                      <li
                        key={item.id ?? idx}
                        className="d-flex align-items-center gap-2 py-2 border-bottom"
                      >
                        <div
                          className="rounded overflow-hidden bg-secondary bg-opacity-10 flex-shrink-0 d-flex align-items-center justify-content-center text-secondary"
                          style={{ width: 48, height: 48 }}
                        >
                          {item.imageUrl ? (
                            <img
                              src={item.imageUrl}
                              alt=""
                              className="w-100 h-100"
                              style={{ objectFit: "cover" }}
                            />
                          ) : (
                            <i className="bi bi-image small" />
                          )}
                        </div>
                        <div className="flex-grow-1 min-w-0">
                          <span className="small">{item.productName}</span>
                          {item.quantity > 1 && (
                            <span className="text-secondary small"> × {item.quantity}</span>
                          )}
                          {(item.optionText || item.selectedOptions) && (
                            <div className="text-secondary small text-truncate">
                              {[item.optionText, item.selectedOptions].filter(Boolean).join(" / ")}
                            </div>
                          )}
                        </div>
                        <span className="small fw-semibold flex-shrink-0">
                          {formatPrice(Number(item.price) * item.quantity)}원
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
                <div className="small">
                  <div className="d-flex justify-content-between text-secondary mb-1">
                    <span>상품금액</span>
                    <span>{formatPrice(Number(detailOrder.totalAmount))}원</span>
                  </div>
                  <div className="d-flex justify-content-between text-secondary mb-1">
                    <span>배송비</span>
                    <span>
                      {Number(detailOrder.deliveryFee ?? 0) === 0
                        ? "무료"
                        : `${formatPrice(Number(detailOrder.deliveryFee))}원`}
                    </span>
                  </div>
                  <hr className="my-2" />
                  <div className="d-flex justify-content-between fw-semibold">
                    <span>총 결제금액</span>
                    <span className="text-primary">
                      {formatPrice(
                        Number(detailOrder.totalAmount) + Number(detailOrder.deliveryFee ?? 0)
                      )}
                      원
                    </span>
                  </div>
                </div>
              </div>
              <div className="modal-footer border-0 pt-0">
                <button
                  type="button"
                  className="btn btn-outline-secondary rounded-pill"
                  onClick={() => setDetailOrder(null)}
                >
                  닫기
                </button>
                <button
                  type="button"
                  className="btn btn-primary rounded-pill"
                  onClick={() => {
                    setDetailOrder(null);
                    handleReorder(detailOrder);
                  }}
                  disabled={reorderingId === detailOrder.id}
                >
                  {reorderingId === detailOrder.id ? "담는 중…" : "재구매"}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}

export default function OrdersPage() {
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
      <OrdersContent />
    </Suspense>
  );
}
