"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const tabs = [
  { href: "/", label: "홈", icon: "bi-house-door" },
  { href: "/cart", label: "장바구니", icon: "bi-cart3" },
  { href: "/orders", label: "주문목록", icon: "bi-receipt" },
  { href: "/wishlist", label: "찜", icon: "bi-heart" },
  { href: "/mypage", label: "마이페이지", icon: "bi-person" },
] as const;

export default function BottomTabBar() {
  const pathname = usePathname();

  return (
    <nav className="navbar navbar-expand fixed-bottom bg-white border-top bottom-tab-bar">
      <ul className="navbar-nav w-100 justify-content-around">
        {tabs.map(({ href, label, icon }) => {
          const isActive =
            href === "/" ? pathname === "/" : pathname.startsWith(href);
          return (
            <li key={href} className="nav-item">
              <Link
                href={href}
                className={`nav-link d-flex flex-column align-items-center py-2 ${
                  isActive ? "active text-primary fw-semibold" : "text-secondary"
                }`}
              >
                <i className={`bi ${icon} fs-4`} />
                <span className="small">{label}</span>
              </Link>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}
