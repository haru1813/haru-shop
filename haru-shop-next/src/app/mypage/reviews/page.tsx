"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import toast from "react-hot-toast";
import { getMyReviews, deleteReview, type ReviewListItemDto } from "@/lib/api";

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

export default function MypageReviewsPage() {
  const [list, setList] = useState<ReviewListItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    setError(null);
    getMyReviews()
      .then(setList)
      .catch((e) => {
        setError(e instanceof Error ? e.message : "리뷰 목록을 불러오지 못했습니다.");
        setList([]);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("이 리뷰를 삭제할까요?")) return;
    try {
      await deleteReview(id);
      toast.success("삭제되었어요.");
      load();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "삭제에 실패했어요.");
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
      <div className="d-flex align-items-center gap-2 mb-3">
        <Link href="/mypage" className="btn btn-link btn-sm text-secondary p-0">
          <i className="bi bi-chevron-left" />
        </Link>
        <h1 className="h4 mb-0 fw-semibold">리뷰 관리</h1>
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {list.length === 0 ? (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5 px-4">
            <i className="bi bi-chat-square-text fs-1 text-secondary mb-3 d-block" />
            <p className="text-secondary mb-4">작성한 리뷰가 없어요.</p>
            <Link href="/" className="btn btn-outline-primary rounded-pill px-4">
              상품 보러 가기
            </Link>
          </div>
        </div>
      ) : (
        <div className="d-flex flex-column gap-3">
          {list.map((r) => (
            <div key={r.id} className="card border-0 shadow-sm rounded-3 overflow-hidden">
              <div className="card-body py-3 px-3">
                <div className="d-flex justify-content-between align-items-start gap-2">
                  <div className="flex-grow-1 min-w-0">
                    <Link
                      href={`/product/${r.productId}`}
                      className="fw-semibold small text-dark text-decoration-none"
                    >
                      {r.productName}
                    </Link>
                    <div className="mt-1">
                      <span className="text-warning small">
                        {"★".repeat(Math.min(5, r.rating))}
                        {"☆".repeat(5 - Math.min(5, r.rating))}
                      </span>
                      <span className="text-secondary small ms-2">{formatDate(r.createdAt)}</span>
                    </div>
                    {r.content && (
                      <p className="small text-secondary mb-0 mt-2">{r.content}</p>
                    )}
                  </div>
                  <button
                    type="button"
                    className="btn btn-outline-danger btn-sm flex-shrink-0"
                    onClick={() => handleDelete(r.id)}
                  >
                    삭제
                  </button>
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
