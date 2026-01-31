"use client";

import { useSearchParams } from "next/navigation";
import { useEffect, useState, Suspense } from "react";
import Link from "next/link";

function CallbackContent() {
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<"loading" | "ok" | "error">("loading");
  const [message, setMessage] = useState("");

  useEffect(() => {
    const error = searchParams.get("error");
    const token = searchParams.get("token");
    const userStr = searchParams.get("user");

    if (error) {
      setStatus("error");
      setMessage(error === "no_code" ? "로그인 정보를 받지 못했습니다." : "로그인에 실패했습니다.");
      return;
    }

    if (token && userStr) {
      try {
        const user = JSON.parse(decodeURIComponent(userStr));
        if (typeof window !== "undefined") {
          localStorage.setItem("harushop_token", token);
          localStorage.setItem("harushop_user", JSON.stringify(user));
        }
        setStatus("ok");
        let nextUrl = "/";
        try {
          const saved = typeof window !== "undefined" ? sessionStorage.getItem("harushop_login_next") : null;
          if (saved && saved.startsWith("/")) nextUrl = saved;
          else nextUrl = searchParams.get("next") || "/";
          if (typeof window !== "undefined") sessionStorage.removeItem("harushop_login_next");
        } catch {
          nextUrl = searchParams.get("next") || "/";
        }
        window.location.href = nextUrl.startsWith("/") ? nextUrl : "/";
        return;
      } catch {
        setStatus("error");
        setMessage("로그인 정보 처리에 실패했습니다.");
        return;
      }
    }

    setStatus("error");
    setMessage("로그인 정보가 없습니다.");
  }, [searchParams]);

  if (status === "loading") {
    return (
      <div className="login-page">
        <div className="login-card">
          <p className="login-subtitle">로그인 처리 중...</p>
        </div>
      </div>
    );
  }

  if (status === "error") {
    return (
      <div className="login-page">
        <div className="login-card">
          <p className="login-subtitle text-danger">{message}</p>
          <Link href="/login" className="btn btn-outline-primary mt-3">
            로그인 다시 시도
          </Link>
          <Link href="/" className="btn btn-link mt-2 d-block">
            홈으로
          </Link>
        </div>
      </div>
    );
  }

  return null;
}

export default function LoginCallbackPage() {
  return (
    <Suspense
      fallback={
        <div className="login-page">
          <div className="login-card">
            <p className="login-subtitle">로그인 처리 중...</p>
          </div>
        </div>
      }
    >
      <CallbackContent />
    </Suspense>
  );
}
