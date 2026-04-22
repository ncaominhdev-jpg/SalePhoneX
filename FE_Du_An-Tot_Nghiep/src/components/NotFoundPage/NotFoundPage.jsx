import React from "react";
import { FaHome, FaArrowLeft } from "react-icons/fa";
import { useNavigate } from "react-router-dom";

const NotFoundPage = () => {
    const navigate = useNavigate();

    return (
        <div className="relative min-h-screen overflow-hidden bg-gradient-to-b from-rose-50 via-white to-rose-100 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
            {/* Decorative blobs */}
            <div className="pointer-events-none absolute -top-24 -left-24 h-[420px] w-[420px] rounded-full bg-rose-300/30 blur-3xl dark:bg-rose-500/20" />
            <div className="pointer-events-none absolute -bottom-24 -right-24 h-[420px] w-[420px] rounded-full bg-pink-200/40 blur-3xl dark:bg-fuchsia-500/10" />

            {/* Content */}
            <div className="relative z-10 mx-auto flex min-h-screen max-w-5xl flex-col items-center justify-center px-6 py-12">
                {/* Card */}
                <div className="w-full rounded-3xl border border-white/60 bg-white/70 p-8 shadow-[0_20px_80px_rgba(244,63,94,0.12)] backdrop-blur-md dark:border-white/10 dark:bg-white/5">
                    {/* Badge */}
                    <div className="mx-auto mb-4 w-fit rounded-full border border-rose-200/60 bg-rose-50/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-rose-600 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        Lỗi 404
                    </div>

                    {/* 404 big number */}
                    <h1 className="text-center text-[72px] leading-none font-black tracking-tighter sm:text-[96px] md:text-[120px]">
                        <span className="bg-gradient-to-r from-rose-500 via-fuchsia-500 to-amber-500 bg-clip-text text-transparent drop-shadow-sm">
                            404
                        </span>
                    </h1>

                    {/* Title */}
                    <h2 className="mt-3 text-center text-2xl font-bold text-slate-800 dark:text-slate-100 sm:text-3xl">
                        Oops! Trang bạn tìm không tồn tại
                    </h2>

                    {/* Description */}
                    <p className="mx-auto mt-3 max-w-xl text-center text-slate-600 dark:text-slate-300">
                        Có thể liên kết đã bị hỏng hoặc trang đã được di chuyển. Hãy quay về
                        trang chủ để tiếp tục mua sắm hoặc trở lại trang trước.
                    </p>

                    {/* Actions */}
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <button
                            onClick={() => navigate(-1)}
                            className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow md:text-base dark:border-white/10 dark:bg-white/10 dark:text-slate-200"
                            aria-label="Quay lại trang trước"
                        >
                            <FaArrowLeft />
                            Quay lại
                        </button>

                        <button
                            onClick={() => navigate("/")}
                            className="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 px-6 py-3 text-sm font-bold text-white shadow-[0_12px_30px_rgba(244,63,94,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_rgba(244,63,94,0.45)] md:text-base"
                            aria-label="Về trang chủ"
                        >
                            <FaHome />
                            Về trang chủ
                        </button>
                    </div>

                    {/* Helpful links */}
                    <div className="mt-6 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <span className="rounded-full bg-white/70 px-3 py-1 dark:bg-white/10">
                            <span className="font-semibold">Gợi ý:</span> Kiểm tra lại URL
                        </span>
                        <span className="rounded-full bg-white/70 px-3 py-1 dark:bg-white/10">
                            <span className="font-semibold">Mẹo:</span> Tìm sản phẩm từ thanh tìm kiếm
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default NotFoundPage;
