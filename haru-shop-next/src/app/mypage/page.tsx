"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { getCart, getWishlist } from "@/lib/api";

type UserInfo = {
  id: number;
  email: string;
  name?: string;
  picture?: string | null;
};

const SHORTCUTS = [
  { href: "/orders", label: "주문내역", icon: "bi-receipt", countKey: "orders" as const },
  { href: "/wishlist", label: "찜", icon: "bi-heart", countKey: "wishlist" as const },
  { href: "/cart", label: "장바구니", icon: "bi-cart3", countKey: "cart" as const },
  { href: "/mypage/coupons", label: "쿠폰", icon: "bi-ticket-perforated", countKey: null },
] as const;

const MENU_ITEMS = [
  { href: "/orders", label: "주문/배송 조회", icon: "bi-box-seam" },
  { href: "/mypage/addresses", label: "배송지 관리", icon: "bi-geo-alt" },
  { href: "/mypage/reviews", label: "리뷰 관리", icon: "bi-chat-square-text" },
  { href: "/mypage/inquiry", label: "1:1 문의", icon: "bi-question-circle" },
  { href: "/mypage/settings", label: "설정", icon: "bi-gear" },
] as const;

export default function MypagePage() {
  const router = useRouter();
  const [user, setUser] = useState<UserInfo | null>(null);
  const [cartCount, setCartCount] = useState<number>(0);
  const [wishlistCount, setWishlistCount] = useState<number>(0);

  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      const raw = localStorage.getItem("harushop_user");
      if (raw) {
        const u = JSON.parse(raw) as UserInfo;
        setUser(u);
      } else {
        setUser(null);
      }
    } catch {
      setUser(null);
    }
  }, []);

  useEffect(() => {
    if (!user) return;
    getCart()
      .then((list) => setCartCount(list.length))
      .catch(() => setCartCount(0));
    getWishlist()
      .then((list) => setWishlistCount(list.length))
      .catch(() => setWishlistCount(0));
  }, [user]);

  const handleLogout = () => {
    localStorage.removeItem("harushop_token");
    localStorage.removeItem("harushop_user");
    setUser(null);
    setCartCount(0);
    setWishlistCount(0);
    router.push("/");
  };

  const getCount = (key: typeof SHORTCUTS[number]["countKey"]) => {
    if (key === "cart") return cartCount;
    if (key === "wishlist") return wishlistCount;
    return null;
  };

  return (
    <div className="container-fluid px-3 py-3 pb-4">
      {/* 프로필 카드 */}
      <div className="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div className="card-body py-4">
          <div className="d-flex align-items-center gap-3">
            <div
              className="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden"
              style={{ width: 56, height: 56 }}
              aria-hidden
            >
              {user?.picture ? (
                <img
                  src={user.picture}
                  alt=""
                  className="w-100 h-100"
                  style={{ objectFit: "cover" }}
                />
              ) : (
                <i className="bi bi-person fs-2 text-secondary" />
              )}
            </div>
            <div className="flex-grow-1 min-w-0">
              <h2 className="h5 mb-1 fw-semibold text-truncate">
                {user ? user.name || user.email : "로그인해 주세요"}
              </h2>
              <p className="text-secondary small mb-0 text-truncate">
                {user
                  ? user.email
                  : "로그인하면 주문·찜·쿠폰을 확인할 수 있어요"}
              </p>
            </div>
            {user ? (
              <button
                type="button"
                className="btn btn-outline-secondary btn-sm rounded-pill flex-shrink-0"
                onClick={handleLogout}
              >
                로그아웃
              </button>
            ) : (
              <Link
                href="/login"
                className="btn btn-outline-primary btn-sm rounded-pill flex-shrink-0"
              >
                로그인
              </Link>
            )}
          </div>
        </div>
      </div>

      {/* 바로가기 (주문/찜/장바구니/쿠폰) */}
      <div className="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div className="card-body py-3 px-0">
          <ul className="list-unstyled d-flex justify-content-around mb-0">
            {SHORTCUTS.map(({ href, label, icon, countKey }) => {
              const count = countKey != null ? getCount(countKey) : null;
              return (
                <li key={label} className="text-center">
                  <Link
                    href={href}
                    className="d-flex flex-column align-items-center text-decoration-none text-dark"
                  >
                    <span className="position-relative">
                      <i className={`bi ${icon} fs-3`} />
                      {count != null && count > 0 && (
                        <span className="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger small">
                          {count > 99 ? "99+" : count}
                        </span>
                      )}
                    </span>
                    <span className="small mt-1">{label}</span>
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
      </div>

      {/* 메뉴 리스트 */}
      <div className="card border-0 shadow-sm rounded-3 overflow-hidden">
        <ul className="list-group list-group-flush">
          {MENU_ITEMS.map(({ href, label, icon }, index) => (
            <li
              key={label}
              className={`list-group-item border-0 px-3 py-2 ${
                index < MENU_ITEMS.length - 1 ? "border-bottom" : ""
              }`}
            >
              <Link
                href={href}
                className="d-flex align-items-center justify-content-between text-decoration-none text-dark py-1"
              >
                <span className="d-flex align-items-center gap-2">
                  <i className={`bi ${icon} text-secondary`} style={{ width: 20 }} />
                  <span className="small">{label}</span>
                </span>
                <i className="bi bi-chevron-right text-secondary small" />
              </Link>
            </li>
          ))}
        </ul>
        <div className="card-body border-top py-2">
          {user ? (
            <button
              type="button"
              className="d-flex align-items-center justify-content-center gap-2 text-secondary text-decoration-none small py-2 border-0 bg-transparent w-100"
              onClick={handleLogout}
            >
              <i className="bi bi-box-arrow-right" />
              <span>로그아웃</span>
            </button>
          ) : (
            <Link
              href="/login"
              className="d-flex align-items-center justify-content-center gap-2 text-secondary text-decoration-none small py-2"
            >
              <i className="bi bi-box-arrow-in-right" />
              <span>로그인</span>
            </Link>
          )}
        </div>
      </div>

      <div style={{ height: 8 }} aria-hidden />
    </div>
  );
}
