import TopNavbar from "./TopNavbar";
import BottomTabBar from "./BottomTabBar";

interface AppLayoutProps {
  children: React.ReactNode;
}

/**
 * 상단 네비게이션 + 하단 탭바를 포함한 공통 레이아웃.
 * 모든 라우팅 페이지에서 동일하게 사용됩니다.
 */
export default function AppLayout({ children }: AppLayoutProps) {
  return (
    <div className="app-layout">
      <TopNavbar />
      <main className="app-main">
        {children}
      </main>
      <BottomTabBar />
    </div>
  );
}
