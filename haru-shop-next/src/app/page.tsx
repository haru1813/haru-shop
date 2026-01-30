"use client";

import { useState, useEffect } from "react";
import {
  Shirt,
  LayoutGrid,
  CircleDot,
  Diamond,
  Gem,
  ShoppingBag,
  Footprints,
  Heart,
  type LucideIcon,
} from "lucide-react";

/* picsum.photos: https://picsum.photos/width/height 또는 /id/width/height */
const CAROUSEL_IMAGES = [
  { src: "https://picsum.photos/id/1/1200/400", alt: "배너 1" },
  { src: "https://picsum.photos/id/10/1200/400", alt: "배너 2" },
  { src: "https://picsum.photos/id/100/1200/400", alt: "배너 3" },
];

const CATEGORY_ICON_SIZE = 28;

const CATEGORIES: { Icon: LucideIcon; label: string }[] = [
  { Icon: Shirt, label: "상의" },
  { Icon: LayoutGrid, label: "하의" },
  { Icon: CircleDot, label: "원피스" },
  { Icon: Diamond, label: "스커트" },
  { Icon: Gem, label: "주얼리" },
  { Icon: ShoppingBag, label: "가방" },
  { Icon: Footprints, label: "신발" },
];

const PRODUCTS = [
  { id: 1, name: "심플 코튼 티셔츠", price: "29,000" },
  { id: 2, name: "슬림 와이드 팬츠", price: "49,000" },
  { id: 3, name: "플라워 원피스", price: "59,000" },
  { id: 4, name: "플리츠 스커트", price: "39,000" },
  { id: 5, name: "실버 펜던트", price: "19,000" },
  { id: 6, name: "레더 크로스백", price: "89,000" },
  { id: 7, name: "캔버스 스니커즈", price: "69,000" },
  { id: 8, name: "오버핏 후드집업", price: "55,000" },
];

const PRODUCT_IMG_SIZE = 300;

export default function Home() {
  const [imageSeed, setImageSeed] = useState(() => Date.now());

  useEffect(() => {
    const interval = setInterval(() => {
      setImageSeed(Date.now());
    }, 5000);
    return () => clearInterval(interval);
  }, []);
  return (
    <div className="container-fluid px-3 py-3">
      <div
        id="homeCarousel"
        className="carousel slide mb-3 rounded-3 overflow-hidden"
        data-bs-ride="carousel"
      >
        <div className="carousel-inner">
          {CAROUSEL_IMAGES.map((img, i) => (
            <div
              key={i}
              className={`carousel-item ${i === 0 ? "active" : ""}`}
            >
              <img
                src={img.src}
                className="d-block w-100 carousel-img"
                alt={img.alt}
              />
            </div>
          ))}
        </div>
        <button
          className="carousel-control-prev carousel-btn"
          type="button"
          data-bs-target="#homeCarousel"
          data-bs-slide="prev"
          aria-label="이전"
        >
          <span className="carousel-control-prev-icon" aria-hidden="true" />
        </button>
        <button
          className="carousel-control-next carousel-btn"
          type="button"
          data-bs-target="#homeCarousel"
          data-bs-slide="next"
          aria-label="다음"
        >
          <span className="carousel-control-next-icon" aria-hidden="true" />
        </button>
      </div>
      <div className="d-flex flex-wrap justify-content-center gap-3 mb-3 py-3 border-bottom">
        {CATEGORIES.map((cat, i) => (
          <div
            key={i}
            className="category-item text-center text-secondary"
            style={{ minWidth: "4rem", cursor: "pointer" }}
          >
            <cat.Icon
              size={CATEGORY_ICON_SIZE}
              className="d-block mx-auto"
              strokeWidth={1.5}
            />
            <span className="small mt-1 d-block">{cat.label}</span>
          </div>
        ))}
      </div>
      <div className="row g-3 mt-2">
        {PRODUCTS.map((product) => (
          <div
            key={product.id}
            className="col-6 col-md-4 col-lg-3"
          >
            <div className="card border-0 h-100">
              <div
                className="position-relative overflow-hidden rounded-top"
                style={{ aspectRatio: "1" }}
              >
                <img
                  src={`https://picsum.photos/seed/${imageSeed}-${product.id}/${PRODUCT_IMG_SIZE}/${PRODUCT_IMG_SIZE}`}
                  className="w-100 h-100"
                  alt={product.name}
                  style={{ objectFit: "cover" }}
                />
                <button
                  type="button"
                  className="product-wish-btn position-absolute bottom-0 end-0 m-2 p-1 rounded-circle bg-white border-0 shadow-sm d-flex align-items-center justify-content-center"
                  aria-label="찜하기"
                >
                  <Heart size={20} strokeWidth={2} className="text-danger" />
                </button>
              </div>
              <div className="card-body p-2">
                <div className="card-title small mb-1 text-truncate" title={product.name}>
                  {product.name}
                </div>
                <div className="small fw-semibold text-dark">
                  {product.price}원
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
