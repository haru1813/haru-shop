"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { getMyCoupons, type UserCouponItemDto } from "@/lib/api";

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

function formatPrice(n: number) {
  return n.toLocaleString();
}

function discountText(c: UserCouponItemDto) {
  if (c.discountType === "percent") {
    const max = c.maxDiscountAmount != null ? ` (최대 ${formatPrice(c.maxDiscountAmount)}원)` : "";
    return `${c.discountValue}% 할인${max}`;
  }
  return `${formatPrice(c.discountValue)}원 할인`;
}

export default function MypageCouponsPage() {
  const [list, setList] = useState<UserCouponItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError(null);
    getMyCoupons()
      .then((data) => {
        if (mounted) setList(data);
      })
      .catch((e) => {
        if (mounted) {
          setError(e instanceof Error ? e.message : "쿠폰 목록을 불러오지 못했습니다.");
          setList([]);
        }
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, []);

  const now = new Date().toISOString();
  const available = list.filter((c) => !c.usedAt && c.validUntil >= now && c.validFrom <= now);
  const usedOrExpired = list.filter((c) => c.usedAt || c.validUntil < now);

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
      <div className="d-flex align-items-center gap-2 mb-3">
        <Link href="/mypage" className="btn btn-link btn-sm text-secondary p-0">
          <i className="bi bi-chevron-left" />
        </Link>
        <h1 className="h4 mb-0 fw-semibold">쿠폰</h1>
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {list.length === 0 ? (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5 px-4">
            <i className="bi bi-ticket-perforated fs-1 text-secondary mb-3 d-block" />
            <p className="text-secondary mb-4">보유한 쿠폰이 없어요.</p>
            <Link href="/mypage" className="btn btn-outline-primary rounded-pill px-4">
              마이페이지로
            </Link>
          </div>
        </div>
      ) : (
        <div className="d-flex flex-column gap-3">
          {available.length > 0 && (
            <>
              <h2 className="h6 text-secondary mb-2">사용 가능 ({available.length})</h2>
              {available.map((c) => (
                <div
                  key={c.id}
                  className="card border-0 shadow-sm rounded-3 overflow-hidden border-start border-4 border-primary"
                >
                  <div className="card-body py-3 px-3">
                    <div className="d-flex justify-content-between align-items-start gap-2">
                      <div>
                        <div className="fw-semibold small">{c.name}</div>
                        <div className="text-primary small">{discountText(c)}</div>
                        <div className="text-secondary small mt-1">
                          {c.minOrderAmount > 0 && `최소 주문 ${formatPrice(c.minOrderAmount)}원 · `}
                          {formatDate(c.validFrom)} ~ {formatDate(c.validUntil)}
                        </div>
                      </div>
                      <span className="badge bg-primary rounded-pill small">사용가능</span>
                    </div>
                  </div>
                </div>
              ))}
            </>
          )}
          {usedOrExpired.length > 0 && (
            <>
              <h2 className="h6 text-secondary mb-2 mt-2">사용 완료 / 만료</h2>
              {usedOrExpired.map((c) => (
                <div
                  key={c.id}
                  className="card border-0 shadow-sm rounded-3 overflow-hidden bg-light bg-opacity-50"
                >
                  <div className="card-body py-3 px-3">
                    <div className="d-flex justify-content-between align-items-start gap-2">
                      <div>
                        <div className="fw-semibold small text-secondary">{c.name}</div>
                        <div className="text-secondary small">{discountText(c)}</div>
                        <div className="text-secondary small mt-1">
                          {c.usedAt ? `사용일: ${formatDate(c.usedAt)}` : `만료: ${formatDate(c.validUntil)}`}
                        </div>
                      </div>
                      <span className="badge bg-secondary rounded-pill small">
                        {c.usedAt ? "사용완료" : "만료"}
                      </span>
                    </div>
                  </div>
                </div>
              ))}
            </>
          )}
        </div>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
