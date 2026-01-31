"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import toast from "react-hot-toast";
import {
  getMyInquiries,
  createInquiry,
  getProducts,
  type InquiryListItemDto,
} from "@/lib/api";

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

export default function MypageInquiryPage() {
  const [list, setList] = useState<InquiryListItemDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [productId, setProductId] = useState<number | "">("");
  const [productSearch, setProductSearch] = useState("");
  const [productOptions, setProductOptions] = useState<{ id: number; name: string }[]>([]);
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const load = () => {
    setLoading(true);
    setError(null);
    getMyInquiries()
      .then(setList)
      .catch((e) => {
        setError(e instanceof Error ? e.message : "문의 목록을 불러오지 못했습니다.");
        setList([]);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    if (!productSearch.trim()) {
      setProductOptions([]);
      return;
    }
    const t = setTimeout(() => {
      getProducts({ search: productSearch, limit: 10 })
        .then((arr) => setProductOptions(arr.map((p) => ({ id: p.id, name: p.name }))))
        .catch(() => setProductOptions([]));
    }, 300);
    return () => clearTimeout(t);
  }, [productSearch]);

  const openForm = () => {
    setProductId("");
    setProductSearch("");
    setProductOptions([]);
    setTitle("");
    setContent("");
    setShowForm(true);
  };

  const closeForm = () => {
    setShowForm(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const pid = productId === "" ? undefined : Number(productId);
    if (pid == null || isNaN(pid)) {
      toast.error("상품을 선택해 주세요.");
      return;
    }
    if (!content.trim()) {
      toast.error("문의 내용을 입력해 주세요.");
      return;
    }
    setSubmitting(true);
    try {
      await createInquiry({
        productId: pid,
        title: title.trim() || null,
        content: content.trim(),
      });
      toast.success("문의가 등록되었어요.");
      closeForm();
      load();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "등록에 실패했어요.");
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

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      <div className="d-flex align-items-center justify-content-between mb-3">
        <div className="d-flex align-items-center gap-2">
          <Link href="/mypage" className="btn btn-link btn-sm text-secondary p-0">
            <i className="bi bi-chevron-left" />
          </Link>
          <h1 className="h4 mb-0 fw-semibold">1:1 문의</h1>
        </div>
        <button type="button" className="btn btn-primary btn-sm rounded-pill" onClick={openForm}>
          <i className="bi bi-plus-lg me-1" />
          문의하기
        </button>
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {showForm && (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
          <div className="card-body py-3 px-3">
            <h2 className="h6 mb-3">문의 등록</h2>
            <form onSubmit={handleSubmit} className="d-flex flex-column gap-2">
              <label className="form-label small mb-0">관련 상품 *</label>
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="상품명 검색"
                value={productSearch}
                onChange={(e) => {
                  setProductSearch(e.target.value);
                  setProductId("");
                }}
              />
              {productOptions.length > 0 && (
                <select
                  className="form-select form-select-sm"
                  value={productId}
                  onChange={(e) => setProductId(e.target.value === "" ? "" : Number(e.target.value))}
                >
                  <option value="">상품 선택</option>
                  {productOptions.map((p) => (
                    <option key={p.id} value={p.id}>
                      {p.name}
                    </option>
                  ))}
                </select>
              )}
              <label className="form-label small mb-0">제목 (선택)</label>
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="제목"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
              />
              <label className="form-label small mb-0">문의 내용 *</label>
              <textarea
                className="form-control form-control-sm"
                rows={4}
                placeholder="문의 내용을 입력해 주세요."
                value={content}
                onChange={(e) => setContent(e.target.value)}
                required
              />
              <div className="d-flex gap-2 mt-2">
                <button
                  type="button"
                  className="btn btn-outline-secondary btn-sm"
                  onClick={closeForm}
                >
                  취소
                </button>
                <button type="submit" className="btn btn-primary btn-sm" disabled={submitting}>
                  {submitting ? "등록 중…" : "등록"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {list.length === 0 && !showForm ? (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5 px-4">
            <i className="bi bi-question-circle fs-1 text-secondary mb-3 d-block" />
            <p className="text-secondary mb-4">등록한 문의가 없어요.</p>
            <button type="button" className="btn btn-primary rounded-pill px-4" onClick={openForm}>
              문의하기
            </button>
          </div>
        </div>
      ) : (
        <div className="d-flex flex-column gap-3">
          {list.map((q) => (
            <div key={q.id} className="card border-0 shadow-sm rounded-3 overflow-hidden">
              <div className="card-body py-3 px-3">
                <div className="d-flex justify-content-between align-items-start gap-2">
                  <div className="flex-grow-1 min-w-0">
                    <div className="fw-semibold small">
                      {q.title || "(제목 없음)"}
                    </div>
                    <Link
                      href={`/product/${q.productId}`}
                      className="text-secondary small text-decoration-none"
                    >
                      {q.productName}
                    </Link>
                    <p className="small mb-0 mt-1">{q.content}</p>
                    <div className="text-secondary small mt-2">{formatDate(q.createdAt)}</div>
                    {q.answer && (
                      <div className="mt-2 p-2 bg-light rounded small">
                        <span className="text-primary fw-semibold">답변</span>
                        {q.answeredAt && (
                          <span className="text-secondary ms-2">{formatDate(q.answeredAt)}</span>
                        )}
                        <p className="mb-0 mt-1">{q.answer}</p>
                      </div>
                    )}
                  </div>
                  <span
                    className={`badge rounded-pill small flex-shrink-0 ${
                      q.answer ? "bg-success" : "bg-secondary"
                    }`}
                  >
                    {q.answer ? "답변완료" : "대기중"}
                  </span>
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
