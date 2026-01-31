"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import toast from "react-hot-toast";
import {
  getMyAddresses,
  createAddress,
  updateAddress,
  deleteAddress,
  type UserAddressDto,
} from "@/lib/api";

const emptyForm = {
  label: "",
  recipientName: "",
  phone: "",
  postalCode: "",
  address: "",
  addressDetail: "",
  isDefault: false,
};

export default function MypageAddressesPage() {
  const [list, setList] = useState<UserAddressDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyForm);
  const [submitting, setSubmitting] = useState(false);

  const load = () => {
    setLoading(true);
    setError(null);
    getMyAddresses()
      .then(setList)
      .catch((e) => {
        setError(e instanceof Error ? e.message : "배송지 목록을 불러오지 못했습니다.");
        setList([]);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const openAdd = () => {
    setEditingId(null);
    setForm(emptyForm);
    setShowForm(true);
  };

  const openEdit = (a: UserAddressDto) => {
    setEditingId(a.id);
    setForm({
      label: a.label ?? "",
      recipientName: a.recipientName,
      phone: a.phone,
      postalCode: a.postalCode ?? "",
      address: a.address,
      addressDetail: a.addressDetail ?? "",
      isDefault: a.isDefault,
    });
    setShowForm(true);
  };

  const closeForm = () => {
    setShowForm(false);
    setEditingId(null);
    setForm(emptyForm);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.recipientName.trim() || !form.phone.trim() || !form.address.trim()) {
      toast.error("수령인, 연락처, 주소를 입력해 주세요.");
      return;
    }
    setSubmitting(true);
    try {
      if (editingId != null) {
        await updateAddress(editingId, {
          label: form.label || null,
          recipientName: form.recipientName.trim(),
          phone: form.phone.trim(),
          postalCode: form.postalCode || null,
          address: form.address.trim(),
          addressDetail: form.addressDetail || null,
          isDefault: form.isDefault,
        });
        toast.success("배송지가 수정되었어요.");
      } else {
        await createAddress({
          label: form.label || null,
          recipientName: form.recipientName.trim(),
          phone: form.phone.trim(),
          postalCode: form.postalCode || null,
          address: form.address.trim(),
          addressDetail: form.addressDetail || null,
          isDefault: form.isDefault,
        });
        toast.success("배송지가 등록되었어요.");
      }
      closeForm();
      load();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "저장에 실패했어요.");
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("이 배송지를 삭제할까요?")) return;
    try {
      await deleteAddress(id);
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
      <div className="d-flex align-items-center justify-content-between mb-3">
        <div className="d-flex align-items-center gap-2">
          <Link href="/mypage" className="btn btn-link btn-sm text-secondary p-0">
            <i className="bi bi-chevron-left" />
          </Link>
          <h1 className="h4 mb-0 fw-semibold">배송지 관리</h1>
        </div>
        <button type="button" className="btn btn-primary btn-sm rounded-pill" onClick={openAdd}>
          <i className="bi bi-plus-lg me-1" />
          추가
        </button>
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {list.length === 0 && !showForm ? (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
          <div className="card-body text-center py-5 px-4">
            <i className="bi bi-geo-alt fs-1 text-secondary mb-3 d-block" />
            <p className="text-secondary mb-4">등록된 배송지가 없어요.</p>
            <button type="button" className="btn btn-primary rounded-pill px-4" onClick={openAdd}>
              배송지 추가
            </button>
          </div>
        </div>
      ) : (
        <div className="d-flex flex-column gap-3">
          {list.map((a) => (
            <div key={a.id} className="card border-0 shadow-sm rounded-3 overflow-hidden">
              <div className="card-body py-3 px-3">
                <div className="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    {a.label && (
                      <span className="badge bg-light text-dark me-2 small">{a.label}</span>
                    )}
                    {a.isDefault && (
                      <span className="badge bg-primary me-2 small">기본</span>
                    )}
                    <div className="fw-semibold small">{a.recipientName}</div>
                    <div className="text-secondary small">{a.phone}</div>
                    <div className="small mt-1">
                      {a.postalCode && `${a.postalCode} `}
                      {a.address}
                      {a.addressDetail && ` ${a.addressDetail}`}
                    </div>
                  </div>
                  <div className="d-flex gap-1 flex-shrink-0">
                    <button
                      type="button"
                      className="btn btn-outline-secondary btn-sm"
                      onClick={() => openEdit(a)}
                    >
                      수정
                    </button>
                    <button
                      type="button"
                      className="btn btn-outline-danger btn-sm"
                      onClick={() => handleDelete(a.id)}
                    >
                      삭제
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showForm && (
        <div className="card border-0 shadow-sm rounded-3 overflow-hidden mt-3">
          <div className="card-body py-3 px-3">
            <h2 className="h6 mb-3">
              {editingId != null ? "배송지 수정" : "배송지 추가"}
            </h2>
            <form onSubmit={handleSubmit} className="d-flex flex-column gap-2">
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="배송지명 (선택)"
                value={form.label}
                onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))}
              />
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="수령인 *"
                value={form.recipientName}
                onChange={(e) => setForm((f) => ({ ...f, recipientName: e.target.value }))}
                required
              />
              <input
                type="tel"
                className="form-control form-control-sm"
                placeholder="연락처 *"
                value={form.phone}
                onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                required
              />
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="우편번호 (선택)"
                value={form.postalCode}
                onChange={(e) => setForm((f) => ({ ...f, postalCode: e.target.value }))}
              />
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="주소 *"
                value={form.address}
                onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                required
              />
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="상세주소 (선택)"
                value={form.addressDetail}
                onChange={(e) => setForm((f) => ({ ...f, addressDetail: e.target.value }))}
              />
              <div className="form-check">
                <input
                  type="checkbox"
                  className="form-check-input"
                  id="addr-default"
                  checked={form.isDefault}
                  onChange={(e) => setForm((f) => ({ ...f, isDefault: e.target.checked }))}
                />
                <label className="form-check-label small" htmlFor="addr-default">
                  기본 배송지로 설정
                </label>
              </div>
              <div className="d-flex gap-2 mt-2">
                <button
                  type="button"
                  className="btn btn-outline-secondary btn-sm"
                  onClick={closeForm}
                >
                  취소
                </button>
                <button type="submit" className="btn btn-primary btn-sm" disabled={submitting}>
                  {submitting ? "저장 중…" : editingId != null ? "수정" : "등록"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
