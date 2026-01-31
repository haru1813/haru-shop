"use client";

import { useEffect, useState, Suspense } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { getGoogleLoginUrl, getKakaoLoginUrl, getNaverLoginUrl } from "@/lib/api";
import { GoogleIcon, KakaoIcon, NaverIcon } from "@/components/LoginIcons";

function LoginContent() {
  const searchParams = useSearchParams();
  const [googleLoginUrl, setGoogleLoginUrl] = useState("");
  const [kakaoLoginUrl, setKakaoLoginUrl] = useState("");
  const [naverLoginUrl, setNaverLoginUrl] = useState("");

  useEffect(() => {
    const next = searchParams.get("next");
    if (next && next.startsWith("/")) {
      try {
        sessionStorage.setItem("harushop_login_next", next);
      } catch {
        // ignore
      }
    }
  }, [searchParams]);

  useEffect(() => {
    setGoogleLoginUrl(getGoogleLoginUrl());
    setKakaoLoginUrl(getKakaoLoginUrl());
    setNaverLoginUrl(getNaverLoginUrl());
  }, []);

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-logo">
          <span className="login-logo-text">Haru Shop</span>
        </div>
        <p className="login-subtitle">소셜 계정으로 간편 로그인</p>
        <div className="login-buttons">
          <a
            href={googleLoginUrl || "#"}
            className="btn btn-google"
            aria-label="Google 로그인"
          >
            <GoogleIcon />
            <span>Google 계정으로 로그인</span>
          </a>
          <a
            href={kakaoLoginUrl || "#"}
            className="btn btn-login btn-kakao"
            aria-label="카카오 로그인"
          >
            <KakaoIcon />
            <span>카카오 로그인</span>
          </a>
          <a
            href={naverLoginUrl || "#"}
            className="btn btn-login btn-naver"
            aria-label="네이버 로그인"
          >
            <NaverIcon />
            <span>네이버 로그인</span>
          </a>
        </div>
        <p className="login-footer">
          <Link href="/" className="login-footer-link">
            홈으로 돌아가기
          </Link>
        </p>
      </div>
    </div>
  );
}

export default function LoginPage() {
  return (
    <Suspense
      fallback={
        <div className="login-page">
          <div className="login-card">
            <p className="login-subtitle">로딩 중...</p>
          </div>
        </div>
      }
    >
      <LoginContent />
    </Suspense>
  );
}
