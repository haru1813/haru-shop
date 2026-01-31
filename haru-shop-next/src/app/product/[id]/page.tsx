"use client";

import { useState, useMemo, useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { Heart } from "lucide-react";
import toast from "react-hot-toast";
import { getProduct, getProducts, addCart, addWishlist, removeWishlist, getWishlist, type ProductDetailDto, type ProductListItemDto } from "@/lib/api";

// --- 옵션 유형 (요구사항: 단독형 / 조합형 / 직접입력형)
export type OptionType = "simple" | "combination" | "text";

/** 옵션 그룹 (Option Master): 사이즈, 색상, 증정품, 각인 문구 등 */
export type OptionMaster = {
  id: string;
  name: string;
  type: OptionType;
  required?: boolean;
  sortOrder: number;
  optionKey?: string;
};

/** 옵션 항목 (Option Item): 빨강, S, M, 키링 등 - 단독형·조합형용 */
export type OptionItem = {
  id: string;
  masterId: string;
  name: string;
  value?: string;
  optionPrice?: number;
  sortOrder: number;
};

/** 조합형 SKU: 조합 결과별 추가금·재고·판매상태 */
export type ProductSku = {
  id: string;
  productId: number;
  optionKey: string;
  optionPrice: number;
  stock: number;
  sellStatus: "on_sale" | "out_of_stock" | "hidden";
};

/** 직접입력형 스펙: 라벨, placeholder, 최대 길이 */
export type TextOptionSpec = {
  masterId: string;
  label: string;
  placeholder: string;
  maxLength?: number;
};

/** API ProductDetailDto → ProductRow 변환 */
function mapApiToProductRow(d: ProductDetailDto): ProductRow {
  const priceNum = Number(d.price);
  const optionMasters: OptionMaster[] = (d.optionGroups ?? []).map((g) => ({
    id: String(g.id),
    name: g.name,
    type: g.optionType as OptionType,
    required: g.required,
    sortOrder: g.sortOrder ?? 0,
    optionKey: g.optionKey ?? undefined,
  }));
  const optionItems: OptionItem[] = (d.optionGroups ?? []).flatMap((g) =>
    (g.items ?? []).map((it) => ({
      id: String(it.id),
      masterId: String(g.id),
      name: it.name,
      value: it.value ?? undefined,
      optionPrice: Number(it.optionPrice ?? 0),
      sortOrder: it.sortOrder ?? 0,
    }))
  );
  const skus: ProductSku[] = (d.skus ?? []).map((s) => ({
    id: String(s.id),
    productId: s.productId,
    optionKey: s.optionKey,
    optionPrice: Number(s.optionPrice ?? 0),
    stock: s.stock ?? 0,
    sellStatus: (s.sellStatus as "on_sale" | "out_of_stock" | "hidden") ?? "on_sale",
  }));
  const textOptionSpecs: TextOptionSpec[] = (d.textOptionSpecs ?? []).map((t) => ({
    masterId: String(t.masterId),
    label: t.label,
    placeholder: t.placeholder ?? "",
    maxLength: t.maxLength ?? undefined,
  }));
  const imageUrls = (d.imageUrls && d.imageUrls.length > 0) ? d.imageUrls : (d.imageUrl ? [d.imageUrl] : []);
  return {
    id: d.id,
    name: d.name,
    price: priceNum.toLocaleString(),
    priceNum,
    description: d.description ?? "",
    detailDescription: d.detailDescription ?? [],
    optionMasters: optionMasters.length ? optionMasters : undefined,
    optionItems: optionItems.length ? optionItems : undefined,
    skus: skus.length ? skus : undefined,
    textOptionSpecs: textOptionSpecs.length ? textOptionSpecs : undefined,
    stock: d.stock,
    imageUrls,
  };
}

type ProductRow = {
  id: number;
  name: string;
  price: string;
  priceNum: number;
  description: string;
  detailDescription: string[];
  /** 단독형·조합형·직접입력형 옵션 (없으면 옵션 없음) */
  optionMasters?: OptionMaster[];
  optionItems?: OptionItem[];
  skus?: ProductSku[];
  textOptionSpecs?: TextOptionSpec[];
  /** 옵션 없을 때 통합 재고 (단독형·옵션없음 상품) */
  stock?: number;
  /** 상품 이미지 URL 목록 (캐러셀용) */
  imageUrls?: string[];
};

const IMG_SIZE = 600;

type TabId = "info" | "size" | "review" | "recommend" | "inquiry";

const TABS: { id: TabId; label: string }[] = [
  { id: "info", label: "상품 정보" },
  { id: "size", label: "사이즈" },
  { id: "review", label: "리뷰" },
  { id: "recommend", label: "추천" },
  { id: "inquiry", label: "문의" },
];

// ----- 옵션 선택 영역 (단독형 / 조합형 / 직접입력형)
function buildOptionKey(
  product: ProductRow,
  selectedCombination: Record<string, string>
): string | null {
  const masters = (product.optionMasters ?? []).filter((m) => m.type === "combination").sort((a, b) => a.sortOrder - b.sortOrder);
  const items = product.optionItems ?? [];
  const parts: string[] = [];
  for (const m of masters) {
    const itemId = selectedCombination[m.id];
    if (!itemId) return null;
    const item = items.find((i) => i.id === itemId);
    const key = m.optionKey ?? m.id;
    const value = item?.value ?? item?.name ?? "";
    parts.push(`${key}:${value}`);
  }
  return parts.length === masters.length ? parts.join(",") : null;
}

/** 조합형에서 해당 옵션 항목이 포함된 재고 있는 SKU가 하나라도 있는지 */
function hasStockForOptionItem(
  product: ProductRow,
  masterId: string,
  itemId: string
): boolean {
  const skus = product.skus ?? [];
  const items = product.optionItems ?? [];
  const master = (product.optionMasters ?? []).find((m) => m.id === masterId);
  const item = items.find((i) => i.id === itemId);
  if (!master || !item) return false;
  const keyPart = `${master.optionKey ?? masterId}:${item.value ?? item.name}`;
  return skus.some((s) => {
    if (s.stock <= 0) return false;
    const parts = s.optionKey.split(",");
    return parts.some((p) => p.trim() === keyPart);
  });
}

function ProductOptionSection({
  product,
  selectedOptionIds,
  textValues,
  currentSku,
  onSelectOption,
  onTextChange,
}: {
  product: ProductRow;
  selectedOptionIds: Record<string, string>;
  textValues: Record<string, string>;
  currentSku: ProductSku | null;
  onSelectOption: (masterId: string, itemId: string) => void;
  onTextChange: (masterId: string, value: string) => void;
}) {
  const masters = (product.optionMasters ?? []).sort((a, b) => a.sortOrder - b.sortOrder);
  const items = product.optionItems ?? [];
  const textSpecs = product.textOptionSpecs ?? [];

  if (masters.length === 0 && textSpecs.length === 0) return null;

  return (
    <div className="product-option-section card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
      <div className="card-body p-4">
        <h3 className="h6 fw-semibold mb-3">옵션 선택</h3>

        {/* 조합형: 그룹별 버튼 (사이즈, 색상 등) */}
        {masters.filter((m) => m.type === "combination").map((m) => (
          <div key={m.id} className="mb-4">
            <div className="d-flex align-items-center gap-2 mb-2">
              <span className="small fw-medium text-secondary">{m.name}</span>
              {m.required && <span className="badge bg-danger bg-opacity-10 text-danger small">필수</span>}
            </div>
            <div className="d-flex flex-wrap gap-2">
              {items
                .filter((i) => i.masterId === m.id)
                .sort((a, b) => a.sortOrder - b.sortOrder)
                .map((opt) => {
                  const selected = selectedOptionIds[m.id] === opt.id;
                  const outOfStock = !hasStockForOptionItem(product, m.id, opt.id);
                  return (
                    <button
                      key={opt.id}
                      type="button"
                      className={`btn btn-sm rounded-pill border ${selected ? "btn-primary border-primary" : "btn-outline-secondary"} ${outOfStock && !selected ? "product-option-out-of-stock" : ""}`}
                      onClick={() => onSelectOption(m.id, opt.id)}
                      disabled={outOfStock && !selected}
                      title={outOfStock && !selected ? "품절" : undefined}
                    >
                      {opt.name}
                      {opt.optionPrice != null && opt.optionPrice > 0 && (
                        <span className="ms-1 opacity-75">+{opt.optionPrice.toLocaleString()}원</span>
                      )}
                      {outOfStock && <span className="ms-1 small text-danger">(품절)</span>}
                    </button>
                  );
                })}
            </div>
          </div>
        ))}

        {/* 조합 선택 결과: 재고·추가금 표시 */}
        {currentSku != null && (
          <div className="alert alert-light border small mb-3 mb-md-4">
            <div className="d-flex justify-content-between align-items-center">
              <span className="text-secondary">선택 옵션 재고</span>
              <span className={currentSku.stock === 0 ? "text-danger fw-semibold" : "text-dark"}>
                {currentSku.stock === 0 ? "품절" : `${currentSku.stock}개`}
              </span>
            </div>
            {currentSku.optionPrice > 0 && (
              <div className="d-flex justify-content-between align-items-center mt-1">
                <span className="text-secondary">옵션 추가금</span>
                <span>+{currentSku.optionPrice.toLocaleString()}원</span>
              </div>
            )}
          </div>
        )}

        {/* 단독형: 라디오/버튼 (증정품 등) */}
        {masters.filter((m) => m.type === "simple").map((m) => (
          <div key={m.id} className="mb-4">
            <div className="d-flex align-items-center gap-2 mb-2">
              <span className="small fw-medium text-secondary">{m.name}</span>
              {m.required && <span className="badge bg-danger bg-opacity-10 text-danger small">필수</span>}
            </div>
            <div className="d-flex flex-wrap gap-2">
              {items
                .filter((i) => i.masterId === m.id)
                .sort((a, b) => a.sortOrder - b.sortOrder)
                .map((opt) => {
                  const selected = selectedOptionIds[m.id] === opt.id;
                  return (
                    <button
                      key={opt.id}
                      type="button"
                      className={`btn btn-sm rounded-pill border ${selected ? "btn-primary border-primary" : "btn-outline-secondary"}`}
                      onClick={() => onSelectOption(m.id, opt.id)}
                    >
                      {opt.name}
                    </button>
                  );
                })}
            </div>
          </div>
        ))}

        {/* 직접입력형: 텍스트 필드 (각인 문구 등) */}
        {textSpecs.map((spec) => (
          <div key={spec.masterId} className="mb-4">
            <label className="small fw-medium text-secondary d-block mb-2">{spec.label}</label>
            <input
              type="text"
              className="form-control form-control-sm rounded-pill"
              placeholder={spec.placeholder}
              maxLength={spec.maxLength}
              value={textValues[spec.masterId] ?? ""}
              onChange={(e) => onTextChange(spec.masterId, e.target.value)}
            />
            {spec.maxLength != null && (
              <div className="small text-secondary mt-1 text-end">
                {(textValues[spec.masterId] ?? "").length}/{spec.maxLength}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

const MOCK_REVIEWS = [
  { id: 1, author: "구매자***", rating: 5, date: "2025-01-25", content: "착용감 좋고 디자인도 예뻐요. 다음에 또 구매할게요!" },
  { id: 2, author: "haru***", rating: 4, date: "2025-01-20", content: "가격 대비 만족스럽습니다. 배송도 빨라요." },
  { id: 3, author: "shop***", rating: 5, date: "2025-01-15", content: "사이즈 딱 맞아요. 추천합니다." },
];

function ProductDetailTabs({ product, recommended }: { product: ProductRow; recommended: ProductListItemDto[] }) {
  const [activeTab, setActiveTab] = useState<TabId>("info");

  return (
    <div className="product-detail-tabs card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
      <div className="product-detail-tab-buttons d-flex border-bottom overflow-auto">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            type="button"
            className={`product-detail-tab-btn flex-grow-1 flex-shrink-0 py-3 px-2 border-0 bg-transparent small fw-medium ${
              activeTab === tab.id ? "active text-primary" : "text-secondary"
            }`}
            onClick={() => setActiveTab(tab.id)}
          >
            {tab.label}
          </button>
        ))}
      </div>
      <div className="card-body p-4">
        {activeTab === "info" && (
          <div className="product-tab-info">
            {/* 상품 정보 탭: 이미지 → 설명 → 이미지 → 설명 순서 */}
            {[
              { imageId: 1, description: product.description },
              { imageId: 2, description: product.detailDescription[0] ?? "" },
              { imageId: 3, description: product.detailDescription[1] ?? "" },
              { imageId: 4, description: product.detailDescription.slice(2).join("\n") },
            ].map((block, idx) => (
              <div key={idx} className="product-tab-info-block mb-4">
                <div className="rounded-2 overflow-hidden bg-secondary bg-opacity-10 mb-3" style={{ aspectRatio: "1", maxWidth: 400 }}>
                  <img
                    src={`https://picsum.photos/seed/pd-${product.id}-${block.imageId}/400/400`}
                    alt={`${product.name} ${block.imageId}`}
                    className="w-100 h-100"
                    style={{ objectFit: "cover" }}
                  />
                </div>
                <p className="text-secondary small mb-0" style={{ lineHeight: 1.7 }}>
                  {block.description}
                </p>
              </div>
            ))}
          </div>
        )}
        {activeTab === "size" && (
          <div className="product-tab-size">
            <p className="small text-secondary mb-3">단위: cm (허리/엉덩이/밑단/총장 등 제품별 상이)</p>
            <div className="table-responsive">
              <table className="table table-bordered small mb-0">
                <thead>
                  <tr className="table-light">
                    <th>사이즈</th>
                    <th>S</th>
                    <th>M</th>
                    <th>L</th>
                    <th>XL</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>어깨</td>
                    <td>44</td>
                    <td>46</td>
                    <td>48</td>
                    <td>50</td>
                  </tr>
                  <tr>
                    <td>가슴</td>
                    <td>44</td>
                    <td>47</td>
                    <td>50</td>
                    <td>53</td>
                  </tr>
                  <tr>
                    <td>총장</td>
                    <td>64</td>
                    <td>66</td>
                    <td>68</td>
                    <td>70</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p className="small text-secondary mt-3 mb-0">※ 실측 기준이며 1~2cm 오차 있을 수 있습니다.</p>
          </div>
        )}
        {activeTab === "review" && (
          <div className="product-tab-review">
            <div className="d-flex align-items-center justify-content-between mb-3">
              <span className="small text-secondary">총 {MOCK_REVIEWS.length}개 리뷰</span>
              <button type="button" className="btn btn-outline-primary btn-sm rounded-pill">
                리뷰 작성
              </button>
            </div>
            <ul className="list-unstyled mb-0">
              {MOCK_REVIEWS.map((r) => (
                <li key={r.id} className="border-bottom pb-3 mb-3 product-tab-review-item">
                  <div className="d-flex align-items-center gap-2 mb-1">
                    <span className="small fw-medium">{r.author}</span>
                    <span className="small text-warning">{"★".repeat(r.rating)}{"☆".repeat(5 - r.rating)}</span>
                    <span className="small text-secondary">{r.date}</span>
                  </div>
                  <p className="small mb-0 text-secondary" style={{ lineHeight: 1.6 }}>
                    {r.content}
                  </p>
                </li>
              ))}
            </ul>
          </div>
        )}
        {activeTab === "recommend" && (
          <div className="product-tab-recommend">
            <p className="small text-secondary mb-3">함께 보면 좋은 상품이에요.</p>
            <div className="row g-2 g-md-3">
              {recommended.map((p) => (
                <div key={p.id} className="col-6">
                  <Link href={`/product/${p.id}`} className="text-decoration-none text-dark d-block">
                    <div className="d-flex gap-2 align-items-center">
                      <div
                        className="rounded overflow-hidden bg-secondary bg-opacity-10 flex-shrink-0"
                        style={{ width: 64, height: 64 }}
                      >
                        <img
                          src={p.imageUrl || `https://picsum.photos/seed/rec-${p.id}/120/120`}
                          alt=""
                          className="w-100 h-100"
                          style={{ objectFit: "cover" }}
                        />
                      </div>
                      <div className="min-w-0 flex-grow-1">
                        <p className="small mb-0 text-truncate fw-normal">{p.name}</p>
                        <p className="small mb-0 text-primary fw-semibold">{Number(p.price).toLocaleString()}원</p>
                      </div>
                    </div>
                  </Link>
                </div>
              ))}
            </div>
          </div>
        )}
        {activeTab === "inquiry" && (
          <div className="product-tab-inquiry">
            <p className="small text-secondary mb-3">상품에 대한 문의를 남겨 주세요. 영업일 기준 1~2일 내 답변 드립니다.</p>
            <div className="mb-3">
              <button type="button" className="btn btn-primary btn-sm rounded-pill">
                문의하기
              </button>
            </div>
            <div className="small text-secondary">
              <p className="mb-2">등록된 문의가 없습니다.</p>
              <p className="mb-0">첫 문의를 남겨 보세요.</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

/** 단독형 선택값 → API용 selectedOptions 문자열 (JSON 객체) */
function buildSelectedOptionsJson(product: ProductRow, selectedOptionIds: Record<string, string>): string | null {
  const simpleMasters = (product.optionMasters ?? []).filter((m) => m.type === "simple");
  if (simpleMasters.length === 0) return null;
  const items = product.optionItems ?? [];
  const obj: Record<string, string> = {};
  for (const m of simpleMasters) {
    const itemId = selectedOptionIds[m.id];
    if (!itemId) continue;
    const item = items.find((i) => i.id === itemId);
    if (item) obj[m.name] = item.name;
  }
  return Object.keys(obj).length > 0 ? JSON.stringify(obj) : null;
}

export default function ProductDetailPage() {
  const params = useParams();
  const router = useRouter();
  const id = Number(params.id);
  const [product, setProduct] = useState<ProductRow | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [recommended, setRecommended] = useState<ProductListItemDto[]>([]);
  const [quantity, setQuantity] = useState(1);
  const [wished, setWished] = useState(false);
  const [selectedOptionIds, setSelectedOptionIds] = useState<Record<string, string>>({});
  const [textValues, setTextValues] = useState<Record<string, string>>({});

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError(null);
    getProduct(id)
      .then((d) => {
        if (mounted && d) setProduct(mapApiToProductRow(d));
      })
      .catch((e) => {
        if (mounted) setError(e instanceof Error ? e.message : "상품을 불러오지 못했습니다.");
      })
      .finally(() => {
        if (mounted) setLoading(false);
      });
    return () => { mounted = false; };
  }, [id]);

  useEffect(() => {
    if (!product) return;
    getProducts({ limit: 5, offset: 0 })
      .then((list) => setRecommended(list.filter((p) => p.id !== product.id).slice(0, 4)));
  }, [product?.id]);

  useEffect(() => {
    if (!id || product == null) return;
    getWishlist()
      .then((list) => setWished(list.some((p) => p.productId === id)))
      .catch(() => setWished(false));
  }, [id, product == null]);

  const optionKey = product ? buildOptionKey(product, selectedOptionIds) : null;
  const currentSku = useMemo(() => {
    if (!product?.skus || !optionKey) return null;
    return product.skus.find((s) => s.optionKey === optionKey) ?? null;
  }, [product, optionKey]);

  const optionPrice = currentSku?.optionPrice ?? 0;
  const availableStock =
    product?.skus != null && product.skus.length > 0
      ? (currentSku?.stock ?? 0)
      : (product?.stock ?? 99);

  const combinationMasters = (product?.optionMasters ?? []).filter((m) => m.type === "combination");
  const allCombinationSelected =
    combinationMasters.length === 0 ||
    combinationMasters.every((m) => selectedOptionIds[m.id]);
  const canAddToCart =
    allCombinationSelected && (product?.skus?.length ? (currentSku != null && availableStock > 0) : true);
  const unitPrice = (product?.priceNum ?? 0) + optionPrice;
  const subtotal = unitPrice * quantity;
  const deliveryFee = subtotal >= 50000 ? 0 : 3000;
  const total = subtotal + deliveryFee;

  useEffect(() => {
    if (product) setQuantity((q) => Math.min(q, availableStock));
  }, [availableStock, product]);

  if (loading) {
    return (
      <div className="container-fluid px-3 py-5 d-flex align-items-center justify-content-center">
        <div className="spinner-border text-primary" role="status"><span className="visually-hidden">로딩 중</span></div>
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="container-fluid px-3 py-5 text-center">
        <p className="text-secondary mb-3">{error ?? "상품을 찾을 수 없습니다."}</p>
        <Link href="/" className="btn btn-primary rounded-pill px-4">홈으로</Link>
      </div>
    );
  }

  function handleSelectOption(masterId: string, itemId: string) {
    setSelectedOptionIds((prev) => ({ ...prev, [masterId]: itemId }));
  }
  function handleTextChange(masterId: string, value: string) {
    setTextValues((prev) => ({ ...prev, [masterId]: value }));
  }

  function buildCartBody() {
    if (!product) return null;
    const skuId = currentSku ? Number(currentSku.id) : undefined;
    const optionText = (product.textOptionSpecs ?? []).length > 0
      ? (product.textOptionSpecs ?? []).map((s) => textValues[s.masterId] ?? "").filter(Boolean).join(" / ") || undefined
      : undefined;
    const selectedOptions = buildSelectedOptionsJson(product, selectedOptionIds) ?? undefined;
    return {
      productId: product.id,
      skuId: skuId ?? null,
      quantity,
      optionText: optionText ?? null,
      selectedOptions: selectedOptions ?? null,
    };
  }

  async function handleAddToCart(redirectTo: "cart" | "checkout" = "cart") {
    const body = buildCartBody();
    if (!body) return;
    if (!product) return;
    try {
      const result = await addCart(body);
      if (redirectTo === "checkout") {
        router.push(`/checkout?cartItemId=${result.id}`);
      } else {
        router.push("/cart");
      }
    } catch (e) {
      alert(e instanceof Error ? e.message : "장바구니 담기에 실패했습니다.");
    }
  }

  async function handleWishToggle() {
    if (!product) return;
    try {
      if (wished) {
        await removeWishlist(product.id);
        setWished(false);
        toast.success("찜 해제했어요");
      } else {
        await addWishlist(product.id);
        setWished(true);
        toast.success("찜 목록에 담았어요");
      }
    } catch (e) {
      const msg = e instanceof Error ? e.message : "찜하기에 실패했습니다.";
      toast.error(msg);
      if (msg === "로그인이 필요합니다." && typeof window !== "undefined") {
        const go = window.confirm("로그인 페이지로 이동할까요?");
        if (go) router.push(`/login?next=${encodeURIComponent(`/product/${product.id}`)}`);
      }
    }
  }

  const imageUrls = product.imageUrls && product.imageUrls.length > 0
    ? product.imageUrls
    : [`https://picsum.photos/seed/p${product.id}/${IMG_SIZE}/${IMG_SIZE}`];
  const carouselId = `productCarousel-${product.id}`;

  return (
    <div className="container-fluid px-3 py-3 pb-5 product-detail-page">
      {/* 상품 이미지 캐러셀 (1개, 여러 장 슬라이드) */}
      <div className="product-detail-carousel-wrap rounded-3 overflow-hidden shadow-sm mb-4">
        <div
          id={carouselId}
          className="carousel slide product-detail-carousel"
          data-bs-ride="carousel"
        >
          <div className="carousel-inner">
            {imageUrls.map((url, i) => (
              <div
                key={i}
                className={`carousel-item ${i === 0 ? "active" : ""}`}
              >
                <div className="product-detail-carousel-img-wrap">
                  <img
                    src={url}
                    alt={`${product.name} ${i + 1}`}
                    className="d-block w-100 h-100"
                    style={{ objectFit: "cover" }}
                  />
                </div>
              </div>
            ))}
          </div>
          {imageUrls.length > 1 && (
            <>
              <button
                className="carousel-control-prev product-detail-carousel-btn"
                type="button"
                data-bs-target={`#${carouselId}`}
                data-bs-slide="prev"
                aria-label="이전"
              >
                <span className="carousel-control-prev-icon" aria-hidden="true" />
              </button>
              <button
                className="carousel-control-next product-detail-carousel-btn"
                type="button"
                data-bs-target={`#${carouselId}`}
                data-bs-slide="next"
                aria-label="다음"
              >
                <span className="carousel-control-next-icon" aria-hidden="true" />
              </button>
              <div className="carousel-indicators product-detail-carousel-indicators">
                {imageUrls.map((_, i) => (
                  <button
                    key={i}
                    type="button"
                    data-bs-target={`#${carouselId}`}
                    data-bs-slide-to={i}
                    className={i === 0 ? "active" : ""}
                    aria-label={`이미지 ${i + 1}`}
                  />
                ))}
              </div>
            </>
          )}
        </div>
      </div>

      {/* 상품 정보 (이름·가격·설명) */}
      <div className="product-detail-info card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div className="card-body p-4">
          <h1 className="h4 fw-semibold mb-2">{product.name}</h1>
          <p className="product-detail-price fs-4 fw-bold text-primary mb-3">
            {product.price}원
            {optionPrice > 0 && (
              <span className="fs-6 fw-normal text-secondary ms-2">+ 옵션 추가금 {optionPrice.toLocaleString()}원</span>
            )}
          </p>
          <p className="text-secondary small mb-0" style={{ lineHeight: 1.6 }}>
            {product.description}
          </p>
        </div>
      </div>

      {/* 옵션 선택 (단독형 / 조합형 / 직접입력형) */}
      <ProductOptionSection
        product={product}
        selectedOptionIds={selectedOptionIds}
        textValues={textValues}
        currentSku={currentSku}
        onSelectOption={handleSelectOption}
        onTextChange={handleTextChange}
      />

      {/* 수량 · 찜 · 장바구니 · 구매하기 */}
      <div className="product-detail-actions card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div className="card-body p-4">
          {!allCombinationSelected && combinationMasters.length > 0 && (
            <p className="small text-warning mb-3">옵션을 모두 선택해 주세요. (색상, 사이즈)</p>
          )}
          {allCombinationSelected && availableStock === 0 && product.skus && product.skus.length > 0 && (
            <p className="small text-danger mb-3">선택한 옵션은 현재 품절입니다.</p>
          )}
          <div className="d-flex align-items-center justify-content-between mb-3">
            <span className="small text-secondary">수량</span>
            <div className="d-flex align-items-center gap-2">
              <button
                type="button"
                className="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                style={{ width: 36, height: 36 }}
                onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                aria-label="수량 감소"
              >
                −
              </button>
              <span className="px-3 fw-semibold" style={{ minWidth: 32, textAlign: "center" }}>
                {quantity}
              </span>
              <button
                type="button"
                className="btn btn-outline-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
                style={{ width: 36, height: 36 }}
                onClick={() => setQuantity((q) => Math.min(availableStock, q + 1))}
                aria-label="수량 증가"
                disabled={quantity >= availableStock}
              >
                +
              </button>
            </div>
          </div>
          {availableStock < 99 && (
            <div className="d-flex justify-content-between align-items-center mb-2 small text-secondary">
              <span>재고</span>
              <span>{availableStock}개</span>
            </div>
          )}
          <div className="d-flex justify-content-between align-items-center mb-3 small">
            <span className="text-secondary">총 상품금액</span>
            <span className="fw-semibold">{subtotal.toLocaleString()}원</span>
          </div>
          {deliveryFee > 0 && (
            <div className="d-flex justify-content-between align-items-center mb-2 small text-secondary">
              <span>배송비</span>
              <span>{deliveryFee.toLocaleString()}원</span>
            </div>
          )}
          {deliveryFee === 0 && subtotal > 0 && (
            <div className="small text-success mb-2">무료배송 적용</div>
          )}
          <hr className="my-3" />
          <div className="d-flex justify-content-between align-items-center mb-4">
            <span className="fw-semibold">총 결제금액</span>
            <span className="fs-5 fw-bold text-primary">{total.toLocaleString()}원</span>
          </div>
          <div className="d-flex flex-column gap-2">
            <div className="d-flex gap-2">
              <button
                type="button"
                className="btn btn-outline-danger rounded-pill flex-grow-1 py-2 d-flex align-items-center justify-content-center gap-2"
                onClick={handleWishToggle}
                aria-label={wished ? "찜 해제" : "찜하기"}
              >
                <Heart
                  size={20}
                  strokeWidth={2}
                  className={wished ? "text-danger" : ""}
                  fill={wished ? "currentColor" : "none"}
                />
                <span>{wished ? "찜 해제" : "찜하기"}</span>
              </button>
              <button
                type="button"
                className="btn btn-outline-primary rounded-pill flex-grow-1 py-2 fw-semibold"
                onClick={() => handleAddToCart()}
                disabled={!canAddToCart}
              >
                장바구니 담기
              </button>
            </div>
            <button
              type="button"
              className="btn btn-primary rounded-pill w-100 py-3 fw-semibold"
              onClick={() => canAddToCart && handleAddToCart("checkout")}
              disabled={!canAddToCart}
            >
              구매하기
            </button>
          </div>
        </div>
      </div>

      {/* 배송 안내 */}
      <div className="product-detail-delivery card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div className="card-body py-3 px-4">
          <ul className="list-unstyled small mb-0 text-secondary">
            <li className="d-flex align-items-center gap-2 mb-2">
              <span className="text-dark fw-medium">배송비</span>
              <span>5만원 이상 구매 시 무료배송 (미만 3,000원)</span>
            </li>
            <li className="d-flex align-items-center gap-2 mb-2">
              <span className="text-dark fw-medium">배송</span>
              <span>평균 2~3일 소요</span>
            </li>
          </ul>
        </div>
      </div>

      {/* 탭: 상품 정보 | 사이즈 | 리뷰 | 추천 | 문의 */}
      <ProductDetailTabs product={product} recommended={recommended} />

      <div style={{ height: 24 }} aria-hidden />
    </div>
  );
}
