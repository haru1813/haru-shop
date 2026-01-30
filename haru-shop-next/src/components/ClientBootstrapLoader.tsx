"use client";

import dynamic from "next/dynamic";

const BootstrapJs = dynamic(() => import("@/components/BootstrapJs"), {
  ssr: false,
});

export default function ClientBootstrapLoader() {
  return <BootstrapJs />;
}
