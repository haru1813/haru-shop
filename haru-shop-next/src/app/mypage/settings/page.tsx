"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import toast from "react-hot-toast";
import { getMyProfile, updateMyProfile } from "@/lib/api";

export default function MypageSettingsPage() {
  const [name, setName] = useState("");
  const [picture, setPicture] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const [profileLoaded, setProfileLoaded] = useState(false);

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError(null);
    getMyProfile()
      .then((p) => {
        if (mounted) {
          setProfileLoaded(!!p);
          if (p) {
            setName(p.name ?? "");
            setPicture(p.picture ?? "");
          }
        }
      })
      .catch((e) => {
        if (mounted) {
          setError(e instanceof Error ? e.message : "프로필을 불러오지 못했습니다.");
        }
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => {
      mounted = false;
    };
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await updateMyProfile({
        name: name.trim() || undefined,
        picture: picture.trim() || undefined,
      });
      const raw = localStorage.getItem("harushop_user");
      if (raw) {
        try {
          const u = JSON.parse(raw) as { name?: string; picture?: string };
          if (name.trim()) u.name = name.trim();
          if (picture.trim()) u.picture = picture.trim();
          localStorage.setItem("harushop_user", JSON.stringify(u));
        } catch {
          // ignore
        }
      }
      toast.success("설정이 저장되었어요.");
    } catch (err) {
      toast.error(err instanceof Error ? err.message : "저장에 실패했어요.");
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
      <div className="d-flex align-items-center gap-2 mb-3">
        <Link href="/mypage" className="btn btn-link btn-sm text-secondary p-0">
          <i className="bi bi-chevron-left" />
        </Link>
        <h1 className="h4 mb-0 fw-semibold">설정</h1>
      </div>

      {error && (
        <div className="alert alert-warning py-2 mb-3 small" role="alert">
          {error}
        </div>
      )}

      {!error && !loading && !profileLoaded && (
        <div className="alert alert-info py-2 mb-3 small" role="alert">
          로그인한 후 프로필을 수정할 수 있어요.
        </div>
      )}

      <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div className="card-body py-3 px-3">
          <h2 className="h6 mb-3">프로필 수정</h2>
          <form onSubmit={handleSubmit} className="d-flex flex-column gap-3">
            <div>
              <label className="form-label small mb-1">프로필 이미지 URL</label>
              <input
                type="url"
                className="form-control form-control-sm"
                placeholder="https://..."
                value={picture}
                onChange={(e) => setPicture(e.target.value)}
              />
              {picture && (
                <div className="mt-2">
                  <img
                    src={picture}
                    alt="미리보기"
                    className="rounded-circle"
                    style={{ width: 64, height: 64, objectFit: "cover" }}
                    onError={(e) => {
                      (e.target as HTMLImageElement).style.display = "none";
                    }}
                  />
                </div>
              )}
            </div>
            <div>
              <label className="form-label small mb-1">이름</label>
              <input
                type="text"
                className="form-control form-control-sm"
                placeholder="이름"
                value={name}
                onChange={(e) => setName(e.target.value)}
              />
            </div>
            <button type="submit" className="btn btn-primary btn-sm align-self-start" disabled={submitting}>
              {submitting ? "저장 중…" : "저장"}
            </button>
          </form>
        </div>
      </div>

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
