import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import AppLayout from "@/components/AppLayout";
import ClientBootstrapLoader from "@/components/ClientBootstrapLoader";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Haru Shop",
  description: "Haru Shop - 하루샵",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ko">
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased`}
      >
        <AppLayout>{children}</AppLayout>
        <ClientBootstrapLoader />
      </body>
    </html>
  );
}
