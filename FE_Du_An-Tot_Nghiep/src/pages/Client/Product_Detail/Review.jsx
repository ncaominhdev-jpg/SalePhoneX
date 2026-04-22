// src/components/Review.jsx
import React, { useEffect, useMemo, useState } from "react";
import { createPortal } from "react-dom";
import { toast } from "react-toastify";
import { FaStar } from "react-icons/fa";
import { useReviews } from "../../../contexts/ReviewContext";

const PAGE_SIZE = 3;

const StarRating = ({ value = 0, size = 16, className = "" }) => {
  const full = Math.floor(value);
  const fraction = value - full;
  return (
    <div className={`flex items-center ${className}`}>
      {[...Array(5)].map((_, i) => {
        const fill =
          i < full ? 100 : i === full ? Math.round(fraction * 100) : 0;
        return (
          <div
            key={i}
            className="relative"
            style={{ width: size, height: size }}
          >
            <FaStar className="absolute inset-0 text-gray-300" size={size} />
            <div
              className="overflow-hidden absolute inset-0"
              style={{ width: `${fill}%`, height: size }}
            >
              <FaStar className="text-yellow-400" size={size} />
            </div>
          </div>
        );
      })}
    </div>
  );
};

const fmtDate = (d) => {
  try {
    return new Date(d).toLocaleDateString();
  } catch {
    return "";
  }
};

const Review = ({ productId }) => {
  const {
    // actions
    fetchReviews,
    createReview,
    // selectors theo productId
    getReviews,
    getAverage,
    getCount,
    isLoading,
    getError,
  } = useReviews();

  // ---- state UI ----
  const [starFilter, setStarFilter] = useState(0);
  const [showAll, setShowAll] = useState(false);

  // modal/form
  const [showForm, setShowForm] = useState(false);
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);
  const [comment, setComment] = useState("");
  const [submitting, setSubmitting] = useState(false);

  // user (chỉ để check đăng nhập hiển thị thông báo)
  const authUser =
    JSON.parse(localStorage.getItem("user") || "null") ||
    JSON.parse(sessionStorage.getItem("user") || "null");

  // --- lấy dữ liệu theo productId ---
  const list = getReviews(productId);
  const average = getAverage(productId);
  const count = getCount(productId);
  const loading = isLoading(productId);
  const error = getError(productId);

  useEffect(() => {
    if (productId) fetchReviews(productId);
    setShowAll(false);
    setShowForm(false);
    setRating(0);
    setHover(0);
    setComment("");
  }, [productId, fetchReviews]);

  // Phân bố sao
  const distribution = useMemo(() => {
    const dist = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    (list || []).forEach((r) => {
      const k = Math.max(1, Math.min(5, Number(r.rating) || 0));
      dist[k] += 1;
    });
    return dist;
  }, [list]);

  // Lọc theo sao
  const filteredList = useMemo(() => {
    let tmp = list || [];
    if (starFilter > 0)
      tmp = tmp.filter((r) => Number(r.rating) === starFilter);
    return tmp;
  }, [list, starFilter]);

  // Áp dụng PAGE_SIZE
  const visibleReviews = useMemo(
    () => (showAll ? filteredList : filteredList.slice(0, PAGE_SIZE)),
    [filteredList, showAll]
  );

  // Tỷ lệ đề xuất
  const recommendPercent = useMemo(() => {
    if (!count) return 0;
    const good = (list || []).filter((r) => Number(r.rating) >= 4).length;
    return Math.round((good / count) * 100);
  }, [list, count]);

  // Gửi đánh giá — dùng Context
  const submitReview = async (e) => {
    e?.preventDefault?.();
    if (!authUser) {
      toast.info("Bạn cần đăng nhập để đánh giá.");
      return;
    }
    if (!rating) {
      toast.warn("Vui lòng chọn số sao.");
      return;
    }
    try {
      setSubmitting(true);
      await createReview(productId, { rating, comment });
      toast.success("Gửi đánh giá thành công!");

      setShowForm(false);
      setRating(0);
      setHover(0);
      setComment("");

      // Load lại từ server để đồng bộ dữ liệu (id, time...)
      await fetchReviews(productId);

      // đảm bảo ở đúng khu vực reviews
      if (window.location.hash !== "#reviews") {
        window.history.replaceState(
          null,
          "",
          `${window.location.pathname}#reviews`
        );
      }
      setTimeout(() => {
        document.getElementById("reviews")?.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }, 0);
    } catch (err) {
      const status = err?.status || err?.response?.status;
      const msg = err?.message || err?.response?.data?.error;
      if (status === 401)
        toast.error("Bạn chưa đăng nhập hoặc token không hợp lệ.");
      else if (status === 403)
        toast.error(
          "Chỉ được đánh giá khi đơn hàng chứa sản phẩm này đã giao."
        );
      else if (status === 409) toast.error("Bạn đã đánh giá sản phẩm này rồi.");
      else toast.error(msg || "Gửi đánh giá thất bại, vui lòng thử lại.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <section id="reviews" className="mt-8">
      <div className="rounded-2xl border border-gray-100 bg-white shadow-sm">
        {/* Header tổng quan */}
        <div className="p-5 md:p-6 border-b">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Trái: điểm TB + phân bố */}
            <div className="flex flex-col md:flex-row gap-5 items-start">
              <div className="flex items-center gap-4 p-4 rounded-2xl bg-red-50 border border-red-100 w-full md:w-auto">
                <div className="text-5xl font-bold text-red-600 leading-none">
                  {Number(average || 0).toFixed(1)}
                </div>
                <div className="flex flex-col">
                  <div className="text-gray-500 text-2xl font-semibold">/5</div>
                  <StarRating value={average || 0} size={18} className="mt-1" />
                  <div className="text-gray-600 text-sm mt-1">
                    {count} lượt đánh giá
                  </div>
                </div>
              </div>

              {/* Thanh phân bố sao */}
              <div className="flex-1 w-full">
                {[5, 4, 3, 2, 1].map((s) => {
                  const n = distribution[s] || 0;
                  const pct = count ? Math.round((n / count) * 100) : 0;
                  return (
                    <div key={s} className="flex items-center gap-3 mb-2">
                      <div className="w-6 text-sm font-medium">{s}</div>
                      <FaStar className="text-yellow-400" size={14} />
                      <div className="flex-1 h-2.5 bg-gray-200 rounded-full overflow-hidden">
                        <div
                          className="h-2.5 rounded-full"
                          style={{
                            width: `${pct}%`,
                            background:
                              "linear-gradient(90deg, #fca5a5 0%, #ef4444 100%)",
                            transition: "width .3s ease",
                          }}
                        />
                      </div>
                      <div className="w-24 text-right text-sm text-gray-600">
                        {n} đánh giá
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Phải: tỷ lệ đề xuất */}
            <div className="md:block">
              <div className="p-4 rounded-2xl border border-green-100 bg-green-50">
                <div className="text-sm text-green-700 font-medium">
                  Tỷ lệ đề xuất
                </div>
                <div className="mt-2 flex items-end gap-2">
                  <div className="text-3xl font-bold text-green-700">
                    {recommendPercent}%
                  </div>
                  <div className="text-sm text-green-700/80">
                    người dùng chấm ≥ 4★
                  </div>
                </div>
                <div className="mt-3 h-2.5 bg-white/70 rounded-full overflow-hidden">
                  <div
                    className="h-2.5 bg-green-500 rounded-full"
                    style={{
                      width: `${recommendPercent}%`,
                      transition: "width .3s",
                    }}
                  />
                </div>
              </div>
            </div>
          </div>

          {error && (
            <div className="mt-3 text-sm text-red-600 bg-red-50 border border-red-100 p-3 rounded-lg">
              {error}
            </div>
          )}
        </div>

        {/* Modal viết đánh giá (Portal) */}
        {showForm &&
          createPortal(
            <div
              className="fixed inset-0 z-[1100] bg-black/40 flex items-center justify-center p-4"
              onClick={() => !submitting && setShowForm(false)}
            >
              <div
                className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl"
                onClick={(e) => e.stopPropagation()}
              >
                <h3 className="text-lg font-semibold mb-1">
                  Đánh giá sản phẩm
                </h3>

                {!authUser ? (
                  <div className="text-sm text-gray-600">
                    Bạn cần{" "}
                    <a
                      href="/login"
                      className="text-red-600 font-medium underline"
                    >
                      đăng nhập
                    </a>{" "}
                    để viết đánh giá.
                  </div>
                ) : (
                  <form onSubmit={submitReview} className="space-y-3">
                    <div className="flex items-center gap-2">
                      {[1, 2, 3, 4, 5].map((s) => (
                        <button
                          key={s}
                          type="button"
                          onMouseEnter={() => setHover(s)}
                          onMouseLeave={() => setHover(0)}
                          onClick={() => setRating(s)}
                          className="p-0.5"
                        >
                          <FaStar
                            size={24}
                            className={
                              (hover || rating) >= s
                                ? "text-yellow-400"
                                : "text-gray-300"
                            }
                          />
                        </button>
                      ))}
                      <span className="text-sm text-gray-600">{rating}/5</span>
                    </div>

                    <textarea
                      rows={3}
                      value={comment}
                      onChange={(e) => setComment(e.target.value)}
                      className="w-full border rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                      placeholder="Chia sẻ cảm nhận của bạn..."
                    />

                    <div className="flex justify-end gap-2">
                      <button
                        type="button"
                        onClick={() => setShowForm(false)}
                        className="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50"
                        disabled={submitting}
                      >
                        Hủy
                      </button>
                      <button
                        type="submit"
                        disabled={submitting || !rating}
                        className="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 disabled:opacity-60"
                      >
                        {submitting ? "Đang gửi..." : "Gửi đánh giá"}
                      </button>
                    </div>
                  </form>
                )}
              </div>
            </div>,
            document.body
          )}

        {/* Filter pill */}
        {count > 0 && (
          <div className="px-5 py-3 border-b flex flex-wrap items-center gap-2 mb-4">
            <span className="text-sm text-gray-700 mr-2">
              Lọc đánh giá theo
            </span>
            <button
              onClick={() => setStarFilter(0)}
              className={`px-3 py-1.5 rounded-full border text-sm ${
                starFilter === 0
                  ? "bg-red-500 text-white border-red-500"
                  : "bg-white hover:bg-gray-50 border-gray-300"
              }`}
            >
              Tất cả ({count})
            </button>
            {[5, 4, 3, 2, 1].map((s) => (
              <button
                key={s}
                onClick={() => setStarFilter(s)}
                className={`px-3 py-1.5 rounded-full border text-sm flex items-center gap-1 ${
                  starFilter === s
                    ? "bg-red-500 text-white border-red-500"
                    : "bg-white hover:bg-gray-50 border-gray-300"
                }`}
              >
                <FaStar
                  className={
                    starFilter === s ? "text-white" : "text-yellow-400"
                  }
                  size={12}
                />
                {s} sao ({distribution[s] || 0})
              </button>
            ))}
          </div>
        )}

        {/* Danh sách review */}
        <div className="px-5 md:px-6 pb-6">
          {loading ? (
            <div className="text-sm text-gray-500 py-6">Đang tải đánh giá…</div>
          ) : visibleReviews.length > 0 ? (
            <>
              {visibleReviews.map((r) => (
                <article
                  key={r.id}
                  className="rounded-xl border border-gray-100 p-4 bg-white shadow-sm mb-3 hover:shadow-md transition"
                >
                  <header className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold shadow">
                      {r.user?.name?.charAt(0).toUpperCase() || "U"}
                    </div>
                    <div className="min-w-0 flex-1">
                      {/* Hàng trên: tên + ngày */}
                      <div className="flex items-center justify-between">
                        <div className="font-medium truncate">
                          {r.user?.name || "Người dùng"}
                        </div>
                        {r.created_at && (
                          <span className="text-xs text-gray-400 ml-3">
                            {fmtDate(r.created_at)}
                          </span>
                        )}
                      </div>

                      {/* Hàng dưới: sao + điểm */}
                      <div className="flex items-center gap-2 text-xs text-gray-500 mt-1">
                        <StarRating value={Number(r.rating) || 0} size={14} />
                        <span>{Number(r.rating) || 0}/5</span>
                      </div>
                    </div>
                  </header>

                  {r.title && (
                    <div className="mt-2 text-sm font-medium text-gray-800">
                      {r.title}
                    </div>
                  )}
                  {r.comment && (
                    <p className="mt-2 text-sm leading-relaxed text-gray-800">
                      {r.comment}
                    </p>
                  )}
                </article>
              ))}

              {/* Xem tất cả / Thu gọn */}
              {filteredList.length > PAGE_SIZE && (
                <div className="mt-4 flex justify-center">
                  {!showAll ? (
                    <button
                      onClick={() => setShowAll(true)}
                      className="px-6 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium shadow-sm transition"
                    >
                      Xem tất cả đánh giá →
                    </button>
                  ) : (
                    <button
                      onClick={() => setShowAll(false)}
                      className="px-6 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium shadow-sm transition"
                    >
                      Thu gọn
                    </button>
                  )}
                </div>
              )}
            </>
          ) : (
            <div className="py-6 text-sm text-gray-500">
              Không có đánh giá nào{" "}
              {starFilter > 0 ? `với ${starFilter} sao` : ""}.
            </div>
          )}
        </div>
      </div>
    </section>
  );
};

export default Review;
