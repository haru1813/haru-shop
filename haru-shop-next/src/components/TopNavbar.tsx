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
        {/* 검색 (오른쪽 정렬) */}
        <div className="d-flex flex-shrink-0">
          {iconLinks}
        </div>
      </div>
    </nav>
  );
}
