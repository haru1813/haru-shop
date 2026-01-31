"use client";

import { Toaster } from "react-hot-toast";

export default function ToastProvider() {
  return (
    <Toaster
      position="top-center"
      toastOptions={{
        duration: 2500,
        style: {
          borderRadius: "12px",
          padding: "12px 16px",
        },
      }}
    />
  );
}
