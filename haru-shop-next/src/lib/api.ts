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
