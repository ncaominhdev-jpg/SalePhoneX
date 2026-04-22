import React, { useMemo, useState, useRef } from "react";
import { Link } from "react-router-dom";
import { toSlug } from "../../../../utils/slug";
import { useCategories } from "../../../../hooks/useCategories";
import { useFlashDeals } from "../../../../contexts/FlashDealsContext";
import { Swiper, SwiperSlide } from "swiper/react";
import { Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import { FaChevronLeft, FaChevronRight } from "react-icons/fa";

/* ===== Helpers ===== */
const VND = (n) => Number(n || 0).toLocaleString("vi-VN");

function useCountdown(targetMs) {
  const [left, setLeft] = React.useState(() =>
    Math.max(0, typeof targetMs === "number" ? targetMs - Date.now() : 0)
  );
  React.useEffect(() => {
    if (typeof targetMs !== "number") return;
    const id = setInterval(() => setLeft(Math.max(0, targetMs - Date.now())), 1000);
    return () => clearInterval(id);
  }, [targetMs]);

  const s = Math.floor(left / 1000);
  const hh = String(Math.floor(s / 3600)).padStart(2, "0");
  const mm = String(Math.floor((s % 3600) / 60)).padStart(2, "0");
  const ss = String(s % 60).padStart(2, "0");
  return { left, hh, mm, ss };
}

/* ===== Build URL SEO ===== */
function useBuildProductHref() {
  const { data: categoriesData } = useCategories();
  const categories = categoriesData?.categories || [];
  const categoryMap = categoriesData?.categoryMap || {};

  return (item) => {
    const catFromId =
      categories.find((c) => Number(c.id) === Number(item?.category_id)) ||
      Object.values(categoryMap).find((c) => Number(c.id) === Number(item?.category_id)) ||
      null;

    const categorySlug =
      (item?.category_slug && toSlug(item.category_slug)) ||
      (item?.category_name ? toSlug(item.category_name) : null) ||
      (catFromId ? toSlug(catFromId.name) : null) ||
      "san-pham";

    const productSlug =
      (item?.product_slug && toSlug(item.product_slug)) ||
      (item?.name ? toSlug(item.name) : "");

    return categorySlug && productSlug ? `/${categorySlug}/${productSlug}` : null;
  };
}

export default function FlashDealLaptops() {
  const { sessions, loading, error } = useFlashDeals();
  const [activeIdx, setActiveIdx] = useState(0);
  const buildHref = useBuildProductHref();
  const swiperRef = useRef(null);

  const current = useMemo(() => sessions?.[activeIdx] || null, [sessions, activeIdx]);

  const now = Date.now();
  const startMs = typeof current?.start_at_ms === "number" ? current.start_at_ms : null;
  const endMs = typeof current?.end_at_ms === "number" ? current.end_at_ms : null;
  const targetMs = startMs && now < startMs ? startMs : endMs ?? null;
  const labelCountdown = startMs && now < startMs ? "Bắt đầu sau" : "Kết thúc sau";
  const { hh, mm, ss } = useCountdown(targetMs);

  if (loading) {
    return (
      <section className="mx-auto max-w-7xl px-3 py-6">
        <div className="h-10 w-40 bg-gray-200 rounded animate-pulse mb-4" />
        <div className="flex gap-3 overflow-x-auto">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="w-[220px] h-[300px] bg-gray-100 rounded-lg animate-pulse" />
          ))}
        </div>
      </section>
    );
  }

  if (error || !current) return null;

  return (
    <section className="mx-auto max-w-7xl px-3 sm:px-4 md:px-6 py-6">
      <div className="relative rounded-[28px] bg-gradient-to-b from-[#fef2f2] via-[#ffe3e3] to-[#ffd6d6] p-4 sm:p-5 border border-white shadow-[0_20px_60px_rgba(255,0,0,.12)]">
        {/* Tiêu đề */}
        <div className="relative mx-auto mb-4 h-12 sm:h-14 max-w-[820px] rounded-[22px] bg-gradient-to-r from-[#ff3b3b] to-[#ff6a6a] text-white grid place-items-center shadow">
          <h2 className="px-4 text-sm sm:text-base md:text-lg font-extrabold tracking-wide">
            SĂN DEAL GIÁ SỐC - CHẬM LÀ TIẾC
          </h2>
        </div>

        {/* Header Tabs + Countdown */}
        <div className="mb-4 flex items-center justify-between gap-3">
          <div className="no-scrollbar flex gap-2 overflow-x-auto pb-1">
            {sessions.map((s, idx) => {
              const active = activeIdx === idx;
              return (
                <button
                  key={idx}
                  onClick={() => setActiveIdx(idx)}
                  className={`rounded-full border-2 px-3 sm:px-4 py-1.5 text-xs sm:text-sm transition-all ${active
                    ? "border-white text-white bg-gradient-to-r from-[#ff3b3b] to-[#ff6a6a] shadow"
                    : "border-white/70 bg-white/50 text-[#b32121] hover:bg-white"
                    }`}
                >
                  {s.label || `Phiên ${idx + 1}`}
                </button>
              );
            })}
          </div>

          {typeof targetMs === "number" && (
            <div className="flex items-center gap-2">
              <span className="hidden sm:inline text-xs font-semibold text-[#8a1c1c]">
                {labelCountdown.toUpperCase()}:
              </span>
              <div className="flex items-center gap-1">
                {[hh, mm, ss].map((t, i) => (
                  <span
                    key={i}
                    className="inline-flex h-8 w-10 items-center justify-center rounded-md bg-white text-[#b32121] text-sm font-extrabold shadow"
                  >
                    {t}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Carousel sản phẩm - Swiper + nút điều hướng Tailwind */}
        <div className="relative">
          {/* Nút Prev */}
          <button
            onClick={() => swiperRef.current?.slidePrev()}
            aria-label="Prev"
            className="absolute left-1 top-1/2 -translate-y-1/2 z-20 grid place-items-center w-9 h-9 rounded-full bg-white/90 text-[#d43030] shadow hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/70"
          >
            <FaChevronLeft />
          </button>

          <Swiper
            modules={[Autoplay]}
            onSwiper={(swiper) => (swiperRef.current = swiper)}
            autoplay={{ delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true }}
            loop={true}
            spaceBetween={12}
            slidesPerView={2}
            breakpoints={{
              640: { slidesPerView: 3 },
              1024: { slidesPerView: 4 },
              1280: { slidesPerView: 5 },
            }}
            className="px-10"
          >
            {current?.items?.map((it) => {
              const href = buildHref(it);
              const soldOut = Number(it.sold) >= Number(it.quota || 0);
              const discount =
                it.price_list > 0
                  ? Math.max(0, Math.round(((it.price_list - it.price_sale) / it.price_list) * 100))
                  : 0;

              const CardWrapper = href ? Link : "div";
              const cardProps = href
                ? {
                  to: href,
                  state: { productId: it.id },
                  className:
                    "group relative block h-full rounded-2xl border border-white/80 bg-white p-3 shadow hover:shadow-lg transition",
                }
                : {
                  className:
                    "relative h-full rounded-2xl border border-white/80 bg-white p-3 shadow opacity-95",
                  "aria-disabled": true,
                  title: "Thiếu slug để điều hướng",
                };

              return (
                <SwiperSlide key={it.id}>
                  <CardWrapper {...cardProps}>
                    {discount > 0 && (
                      <span className="absolute left-3 top-3 z-10 rounded-md bg-rose-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                        -{discount}%
                      </span>
                    )}

                    <div className="relative aspect-square overflow-hidden rounded-xl bg-gray-50">
                      <img
                        src={it.image}
                        alt={it.name}
                        loading="lazy"
                        onError={(e) => (e.currentTarget.src = "/images/placeholder.webp")}
                        className="absolute inset-0 h-full w-full object-contain"
                      />
                      {soldOut && (
                        <div className="pointer-events-none absolute inset-0 grid place-items-center bg-white/70 backdrop-blur-[1px]">
                          <span className="rounded-full bg-gray-900/90 px-3 py-1 text-xs font-semibold text-white">
                            Hết hàng
                          </span>
                        </div>
                      )}
                    </div>

                    <h3 className="mt-2 line-clamp-2 min-h-[48px] text-base font-semibold text-gray-800">
                      {it.name}
                    </h3>

                    <div className="mt-1">
                      <div className="text-[18px] font-extrabold text-[#d43030]">
                        {VND(it.price_sale)}đ
                      </div>
                      <div className="text-xs text-gray-400 line-through">
                        {VND(it.price_list)}đ
                      </div>
                    </div>
                  </CardWrapper>
                </SwiperSlide>
              );
            })}
          </Swiper>

          {/* Nút Next */}
          <button
            onClick={() => swiperRef.current?.slideNext()}
            aria-label="Next"
            className="absolute right-1 top-1/2 -translate-y-1/2 z-20 grid place-items-center w-9 h-9 rounded-full bg-white/90 text-[#d43030] shadow hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/70"
          >
            <FaChevronRight />
          </button>
        </div>

        {/* Note cuối */}
        <div className="mt-4 grid place-items-center">
          <div className="rounded-full border border-white bg-white/70 px-4 py-2 text-center text-[12px] text-[#b32121] shadow-sm">
            Số lượng có hạn – Hết là thôi!
          </div>
        </div>
      </div>
    </section>
  );
}
