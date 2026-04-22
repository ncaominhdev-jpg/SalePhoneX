// src/contexts/ReviewContext.jsx
import React, { createContext, useContext, useState, useCallback } from "react";
import axios from "axios";
import constants from "../constants/constants";

const ReviewContext = createContext(null);

// Lấy token (ưu tiên remember_token trong user, fallback access_token)
function getAuthToken() {
  try {
    const u1 = JSON.parse(localStorage.getItem("user") || "null");
    if (u1?.remember_token) return u1.remember_token;
    const u2 = JSON.parse(sessionStorage.getItem("user") || "null");
    if (u2?.remember_token) return u2.remember_token;
    return (
      localStorage.getItem("access_token") ||
      sessionStorage.getItem("access_token") ||
      ""
    );
  } catch {
    return "";
  }
}

// Tính trung bình từ list rating
function averageFrom(list) {
  if (!Array.isArray(list) || list.length === 0) return 0;
  const sum = list.reduce((s, r) => s + Number(r?.rating || 0), 0);
  return Math.round((sum / list.length) * 10) / 10;
}

export const ReviewProvider = ({ children }) => {
  // Lưu theo productId
  const [reviewsByProduct, setReviewsByProduct] = useState({}); // { [id]: Review[] }
  const [avgByProduct, setAvgByProduct] = useState({}); // { [id]: number }
  const [countByProduct, setCountByProduct] = useState({}); // { [id]: number }
  const [loadingByProduct, setLoadingByProduct] = useState({}); // { [id]: boolean }
  const [errorByProduct, setErrorByProduct] = useState({}); // { [id]: string|null }

  // ---- Selectors (an toàn) ----
  const getReviews = useCallback(
    (productId) => reviewsByProduct[String(productId)] || [],
    [reviewsByProduct]
  );

  const getAverage = useCallback(
    (productId) => {
      const key = String(productId);
      const v = avgByProduct[key];
      if (Number.isFinite(v)) return v;
      return averageFrom(reviewsByProduct[key]);
    },
    [avgByProduct, reviewsByProduct]
  );

  const getCount = useCallback(
    (productId) => {
      const key = String(productId);
      const v = countByProduct[key];
      if (Number.isFinite(v)) return v;
      const list = reviewsByProduct[key];
      return Array.isArray(list) ? list.length : 0;
    },
    [countByProduct, reviewsByProduct]
  );

  const isLoading = useCallback(
    (productId) => !!loadingByProduct[String(productId)],
    [loadingByProduct]
  );

  const getError = useCallback(
    (productId) => errorByProduct[String(productId)] || null,
    [errorByProduct]
  );

  // ---- Actions ----
  const fetchReviews = useCallback(async (productId) => {
    if (!productId) return;
    const key = String(productId);

    // set loading true cho productId
    setLoadingByProduct((m) => ({ ...m, [key]: true }));
    setErrorByProduct((m) => ({ ...m, [key]: null }));

    try {
      const res = await axios.get(
        `${constants.BASE_URL}/products/${productId}/reviews`
      );

      const list = Array.isArray(res?.data?.reviews) ? res.data.reviews : [];
      const avg = Number(res?.data?.average ?? 0);

      setReviewsByProduct((m) => ({ ...m, [key]: list }));
      setAvgByProduct((m) => ({
        ...m,
        [key]: Number.isFinite(avg)
          ? Math.round(avg * 10) / 10
          : averageFrom(list),
      }));
      setCountByProduct((m) => ({ ...m, [key]: list.length }));
    } catch (err) {
      const msg =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        "Không thể tải đánh giá";
      setErrorByProduct((m) => ({ ...m, [key]: msg }));
      // khi lỗi, vẫn reset dữ liệu của sản phẩm này
      setReviewsByProduct((m) => ({ ...m, [key]: [] }));
      setAvgByProduct((m) => ({ ...m, [key]: 0 }));
      setCountByProduct((m) => ({ ...m, [key]: 0 }));
    } finally {
      setLoadingByProduct((m) => ({ ...m, [key]: false }));
    }
  }, []);

  // Tạo review + cập nhật ngay product tương ứng
  const createReview = useCallback(
    async (productId, { rating, comment, order_id, variant_id }) => {
      const token = getAuthToken();
      if (!token) {
        const e = new Error("Unauthorized");
        e.status = 401;
        throw e;
      }

      const key = String(productId);

      try {
        const payload = {
          rating: Number(rating),
          comment,
          // order_id có thể undefined; chỉ gửi nếu có số hợp lệ
          ...(Number.isFinite(Number(order_id))
            ? { order_id: Number(order_id) }
            : {}),
          ...(Number.isFinite(Number(variant_id))
            ? { product_variant_id: Number(variant_id) }
            : {}),
        };

        const res = await axios.post(
          `${constants.BASE_URL}/products/${productId}/reviews`,
          payload,
          { headers: { Authorization: `Bearer ${token}` } }
        );

        const created =
          res?.data && typeof res.data === "object"
            ? res.data
            : {
                id: Math.random().toString(36).slice(2),
                user: {
                  name:
                    JSON.parse(localStorage.getItem("user") || "null")?.name ||
                    JSON.parse(sessionStorage.getItem("user") || "null")
                      ?.name ||
                    "Bạn",
                },
                product_id: Number(productId),
                order_id: Number.isFinite(Number(order_id))
                  ? Number(order_id)
                  : undefined,
                rating: Number(rating),
                comment: comment || "",
                created_at: new Date().toISOString(),
                product_variant_id: Number.isFinite(Number(variant_id))
                  ? Number(variant_id)
                  : undefined,
              };

        // cập nhật local state cho đúng product
        setReviewsByProduct((m) => {
          const prev = m[key] || [];
          const next = [created, ...prev];
          return { ...m, [key]: next };
        });
        setCountByProduct((m) => {
          const nextCount = (m[key] ?? 0) + 1;
          return { ...m, [key]: nextCount };
        });
        setAvgByProduct((m) => {
          const list = reviewsByProduct[key] || [];
          const nextAvg = averageFrom([created, ...list]);
          return { ...m, [key]: nextAvg };
        });

        return created;
      } catch (err) {
        if (process.env.NODE_ENV === "development") {
          console.error("Create review failed:", err?.response?.data || err);
        }
        const e = new Error(
          err?.response?.data?.error ||
            err?.response?.data?.message ||
            "Gửi đánh giá thất bại"
        );
        e.status = err?.response?.status;
        throw e;
      }
    },
    [reviewsByProduct]
  );

  return (
    <ReviewContext.Provider
      value={{
        // dữ liệu thô (nếu cần)
        reviewsByProduct,
        avgByProduct,
        countByProduct,
        loadingByProduct,
        errorByProduct,

        // selectors
        getReviews,
        getAverage,
        getCount,
        isLoading,
        getError,

        // actions
        fetchReviews,
        createReview,
      }}
    >
      {children}
    </ReviewContext.Provider>
  );
};

export const useReviews = () => useContext(ReviewContext);
