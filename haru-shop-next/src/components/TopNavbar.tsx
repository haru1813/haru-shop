"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const iconSize = 20;

const SearchIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={iconSize}
    height={iconSize}
    fill="currentColor"
    viewBox="0 0 16 16"
    aria-hidden
  >
    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
  </svg>
);

const CartIcon = () => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={iconSize}
    height={iconSize}
    fill="currentColor"
    viewBox="0 0 16 16"
    aria-hidden
  >
    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-7 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0zm7 0a2 2 0 1 1 4 0 2 2 0 0 1-4 0z" />
  </svg>
);

const navTabs = [
  { href: "/", label: "홈" },
  { href: "/category", label: "카테고리" },
  { href: "/wishlist", label: "찜" },
  { href: "/mypage", label: "마이페이지" },
] as const;

export default function TopNavbar() {
  const pathname = usePathname();

  const iconLinks = (
    <ul className="navbar-nav align-items-center gap-2 flex-row">
      <li className="nav-item">
        <Link
          href="/search"
          className="nav-link d-flex align-items-center justify-content-center p-2 text-dark"
          aria-label="검색"
        >
          <SearchIcon />
        </Link>
      </li>
      <li className="nav-item">
        <Link
          href="/cart"
          className="nav-link d-flex align-items-center justify-content-center p-2 text-dark"
          aria-label="장바구니"
        >
          <CartIcon />
        </Link>
      </li>
    </ul>
  );

  return (
    <nav className="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
      <div className="container-fluid d-flex align-items-center">
        <Link href="/" className="navbar-brand fw-semibold me-4">
          Haru Shop
        </Link>
        {/* PC: 홈·카테고리·찜·마이페이지 (가운데 영역) */}
        <div className="collapse navbar-collapse flex-grow-1" id="navbarNav">
          <ul className="navbar-nav me-auto d-none d-lg-flex">
            {navTabs.map(({ href, label }) => {
              const isActive =
                href === "/" ? pathname === "/" : pathname.startsWith(href);
              return (
                <li key={href} className="nav-item">
                  <Link
                    href={href}
                    className={`nav-link ${isActive ? "active fw-semibold" : ""}`}
                  >
                    {label}
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
        {/* 검색·장바구니 (오른쪽 정렬) */}
        <div className="d-flex flex-shrink-0">
          {iconLinks}
        </div>
      </div>
    </nav>
  );
}
