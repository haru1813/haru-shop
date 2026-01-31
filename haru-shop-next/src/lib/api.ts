/**
 * Spring Boot API 베이스 URL (호출 시점에 계산 → 브라우저에서는 접속한 호스트 기준)
 * - next.haru.company → spring.haru.company, localhost → localhost:502, 그 외(IP 등) → 현재 호스트:502
 */
export function getApiBaseUrl(): string {
  if (typeof window !== "undefined") {
    const host = window.location.hostname;
    if (host === "next.haru.company") return "https://spring.haru.company/api";
    if (host === "localhost") return "http://localhost:502/api";
    return process.env.NEXT_PUBLIC_API_URL || `http://${host}:502/api`;
  }
  return process.env.NEXT_PUBLIC_API_URL || "http://localhost:502/api";
}

/** 인증 필요 API용 헤더 (localStorage harushop_token) */
export function getAuthHeaders(): Record<string, string> {
  if (typeof window === "undefined") return {};
  const token = localStorage.getItem("harushop_token");
  if (!token) return {};
  return { Authorization: `Bearer ${token}` };
}

/** 리다이렉트 방식: Google 로그인 시작 URL (Spring이 Google로 302 리다이렉트) */
export function getGoogleLoginUrl(): string {
  return `${getApiBaseUrl()}/auth/google`;
}

/** 리다이렉트 방식: 카카오 로그인 시작 URL (Spring이 카카오로 302 리다이렉트) */
export function getKakaoLoginUrl(): string {
  return `${getApiBaseUrl()}/auth/kakao`;
}

/** 리다이렉트 방식: 네이버 로그인 시작 URL (Spring이 네이버로 302 리다이렉트) */
export function getNaverLoginUrl(): string {
  return `${getApiBaseUrl()}/auth/naver`;
}

export async function postGoogleLogin(idToken: string) {
  const res = await fetch(`${getApiBaseUrl()}/auth/google`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idToken }),
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || "로그인 실패");
  }
  return res.json() as Promise<{ token: string; user: { id: number; email: string; name: string; picture: string | null } }>;
}

// -----------------------------------------------------------------------------
// 카테고리
// -----------------------------------------------------------------------------
export interface CategoryDto {
  id: number;
  name: string;
  slug: string;
  icon: string | null;
  sortOrder: number;
  createdAt?: string;
  updatedAt?: string;
}

export async function getCategories(): Promise<CategoryDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/categories`);
  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || "카테고리 목록을 불러오지 못했습니다.");
  }
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function getCategoryBySlug(slug: string): Promise<CategoryDto | null> {
  const res = await fetch(`${getApiBaseUrl()}/categories/slug/${encodeURIComponent(slug)}`);
  if (res.status === 404) return null;
  if (!res.ok) throw new Error("카테고리를 불러오지 못했습니다.");
  return res.json();
}

// -----------------------------------------------------------------------------
// 상품
// -----------------------------------------------------------------------------
export interface ProductListItemDto {
  id: number;
  name: string;
  slug: string;
  price: number;
  imageUrl: string | null;
  categoryName: string | null;
}

export async function getProducts(params?: { categoryId?: number; search?: string; limit?: number; offset?: number }): Promise<ProductListItemDto[]> {
  const sp = new URLSearchParams();
  if (params?.categoryId != null) sp.set("categoryId", String(params.categoryId));
  if (params?.search) sp.set("search", params.search);
  if (params?.limit != null) sp.set("limit", String(params.limit));
  if (params?.offset != null) sp.set("offset", String(params.offset));
  const q = sp.toString();
  const res = await fetch(`${getApiBaseUrl()}/products${q ? "?" + q : ""}`);
  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || "상품 목록을 불러오지 못했습니다.");
  }
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export interface OptionGroupDto {
  id: number;
  name: string;
  optionType: "simple" | "combination" | "text";
  optionKey: string | null;
  required: boolean;
  sortOrder: number;
  items: OptionItemDto[];
}

export interface OptionItemDto {
  id: number;
  masterId: number;
  name: string;
  value: string | null;
  optionPrice: number;
  sortOrder: number;
}

export interface ProductSkuDto {
  id: number;
  productId: number;
  optionKey: string;
  optionPrice: number;
  stock: number;
  sellStatus: string;
}

export interface TextOptionSpecDto {
  masterId: number;
  label: string;
  placeholder: string | null;
  maxLength: number | null;
}

export interface ProductDetailDto {
  id: number;
  name: string;
  slug: string;
  price: number;
  description: string | null;
  imageUrl: string | null;
  stock: number;
  imageUrls: string[] | null;
  optionGroups: OptionGroupDto[] | null;
  skus: ProductSkuDto[] | null;
  textOptionSpecs: TextOptionSpecDto[] | null;
  detailDescription: string[] | null;
}

export async function getProduct(id: number): Promise<ProductDetailDto | null> {
  const res = await fetch(`${getApiBaseUrl()}/products/${id}`);
  if (res.status === 404) return null;
  if (!res.ok) {
    const text = await res.text();
    throw new Error(text || "상품을 불러오지 못했습니다.");
  }
  return res.json();
}

// -----------------------------------------------------------------------------
// 배너
// -----------------------------------------------------------------------------
export interface BannerDto {
  id: number;
  imageUrl: string;
  linkUrl: string | null;
  sortOrder: number;
}

export async function getBanners(): Promise<BannerDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/banners`);
  if (!res.ok) return [];
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

// -----------------------------------------------------------------------------
// 장바구니 (인증 필요)
// -----------------------------------------------------------------------------
export interface CartItemDto {
  id: number;
  productId: number;
  skuId: number | null;
  productName: string;
  price: number;
  imageUrl: string | null;
  quantity: number;
  optionText: string | null;
  selectedOptions: string | null;
}

export async function getCart(): Promise<CartItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/cart`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("장바구니를 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function addCart(body: { productId: number; skuId?: number | null; quantity?: number; optionText?: string | null; selectedOptions?: string | null }): Promise<{ id: number; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/cart`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "장바구니 담기 실패");
  }
  return res.json();
}

export async function updateCartQuantity(id: number, quantity: number): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/cart/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify({ quantity }),
  });
  if (!res.ok) throw new Error("수량 변경에 실패했습니다.");
}

export async function removeCart(id: number): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/cart/${id}`, { method: "DELETE", headers: getAuthHeaders() });
  if (!res.ok) throw new Error("삭제에 실패했습니다.");
}

// -----------------------------------------------------------------------------
// 찜 (인증 필요)
// -----------------------------------------------------------------------------
export interface WishlistItemDto {
  id: number;
  productId: number;
  productName: string;
  price: number;
  imageUrl: string | null;
}

export async function getWishlist(): Promise<WishlistItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/wishlist`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("찜 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function addWishlist(productId: number): Promise<{ id: number; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/wishlist`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify({ productId }),
  });
  if (res.status === 401 || res.status === 403) throw new Error("로그인이 필요합니다.");
  if (!res.ok) throw new Error("찜하기 실패");
  return res.json();
}

export async function removeWishlist(productId: number): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/wishlist/${productId}`, { method: "DELETE", headers: getAuthHeaders() });
  if (!res.ok && res.status !== 404) throw new Error("찜 해제 실패");
}

// -----------------------------------------------------------------------------
// 주문 (인증 필요)
// -----------------------------------------------------------------------------
export interface OrderItemDto {
  id: number;
  productId: number | null;
  productName: string;
  price: number;
  quantity: number;
  optionText: string | null;
  selectedOptions: string | null;
  imageUrl: string | null;
}

export interface OrderListItemDto {
  id: number;
  orderNumber: string;
  status: string;
  totalAmount: number;
  deliveryFee: number;
  receiverName: string | null;
  createdAt: string;
  items: OrderItemDto[];
}

export async function getOrders(): Promise<OrderListItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/orders`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("주문 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function createOrder(body: {
  items: Array<{ productId: number; skuId?: number | null; productName: string; price: number; quantity: number; optionText?: string | null; selectedOptions?: string | null }>;
  totalAmount: number;
  deliveryFee: number;
  receiverName: string;
  receiverPhone: string;
  receiverAddress: string;
}): Promise<{ id: number; orderNumber: string; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/orders`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "주문 실패");
  }
  return res.json();
}

// -----------------------------------------------------------------------------
// 마이페이지: 쿠폰
// -----------------------------------------------------------------------------
export interface UserCouponItemDto {
  id: number;
  couponId: number;
  code: string;
  name: string;
  discountType: string;
  discountValue: number;
  minOrderAmount: number;
  maxDiscountAmount: number | null;
  validFrom: string;
  validUntil: string;
  usedAt: string | null;
  createdAt: string;
}

export async function getMyCoupons(): Promise<UserCouponItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/coupons`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("쿠폰 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

// -----------------------------------------------------------------------------
// 마이페이지: 배송지
// -----------------------------------------------------------------------------
export interface UserAddressDto {
  id: number;
  label: string | null;
  recipientName: string;
  phone: string;
  postalCode: string | null;
  address: string;
  addressDetail: string | null;
  isDefault: boolean;
}

export async function getMyAddresses(): Promise<UserAddressDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/addresses`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("배송지 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function createAddress(body: {
  label?: string | null;
  recipientName: string;
  phone: string;
  postalCode?: string | null;
  address: string;
  addressDetail?: string | null;
  isDefault?: boolean;
}): Promise<{ id: number; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/addresses`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "배송지 등록 실패");
  }
  return res.json();
}

export async function updateAddress(
  id: number,
  body: {
    label?: string | null;
    recipientName?: string;
    phone?: string;
    postalCode?: string | null;
    address?: string;
    addressDetail?: string | null;
    isDefault?: boolean;
  }
): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/addresses/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "배송지 수정 실패");
  }
}

export async function deleteAddress(id: number): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/addresses/${id}`, {
    method: "DELETE",
    headers: getAuthHeaders(),
  });
  if (!res.ok) throw new Error("배송지 삭제 실패");
}

// -----------------------------------------------------------------------------
// 마이페이지: 리뷰
// -----------------------------------------------------------------------------
export interface ReviewListItemDto {
  id: number;
  productId: number;
  productName: string;
  rating: number;
  content: string | null;
  createdAt: string;
}

export async function getMyReviews(): Promise<ReviewListItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/reviews`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("리뷰 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function createReview(body: {
  productId: number;
  rating?: number;
  content?: string | null;
}): Promise<{ id: number; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/reviews`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "리뷰 등록 실패");
  }
  return res.json();
}

export async function deleteReview(id: number): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/reviews/${id}`, {
    method: "DELETE",
    headers: getAuthHeaders(),
  });
  if (!res.ok) throw new Error("리뷰 삭제 실패");
}

// -----------------------------------------------------------------------------
// 마이페이지: 1:1 문의
// -----------------------------------------------------------------------------
export interface InquiryListItemDto {
  id: number;
  productId: number;
  productName: string;
  title: string | null;
  content: string;
  answer: string | null;
  answeredAt: string | null;
  createdAt: string;
}

export async function getMyInquiries(): Promise<InquiryListItemDto[]> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/inquiries`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return [];
  if (!res.ok) throw new Error("문의 목록을 불러오지 못했습니다.");
  const data = await res.json();
  return Array.isArray(data) ? data : [];
}

export async function createInquiry(body: {
  productId: number;
  title?: string | null;
  content: string;
}): Promise<{ id: number; ok: boolean }> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/inquiries`, {
    method: "POST",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error((data as { error?: string }).error || "문의 등록 실패");
  }
  return res.json();
}

// -----------------------------------------------------------------------------
// 마이페이지: 설정(프로필)
// -----------------------------------------------------------------------------
export interface MyProfileDto {
  id: number;
  email: string;
  name: string;
  picture: string | null;
}

export async function getMyProfile(): Promise<MyProfileDto | null> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/profile`, {
    headers: getAuthHeaders(),
    cache: "no-store",
  });
  if (res.status === 401 || res.status === 403) return null;
  if (res.status === 404) return null;
  if (!res.ok) throw new Error("프로필을 불러오지 못했습니다.");
  return res.json();
}

export async function updateMyProfile(body: {
  name?: string;
  picture?: string;
}): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/mypage/profile`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json", ...getAuthHeaders() },
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error("프로필 수정 실패");
}
