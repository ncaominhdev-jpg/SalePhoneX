import React, { useEffect, useState } from "react";
import { FaTicketAlt } from "react-icons/fa";
import { useVouchers } from "../../contexts/VoucherContext";
import ClaimVoucherModal from "../Voucher/ClaimVoucherModal";

const ITEMS_PER_PAGE = 6;

const VoucherPage = () => {
    const { vouchers, loading, voucherCode, refreshVouchers } = useVouchers();
    const [currentPage, setCurrentPage] = useState(1);
    const [isClaimModalOpen, setIsClaimModalOpen] = useState(false);

    useEffect(() => {
        refreshVouchers();
    }, [refreshVouchers]);

    const totalPages = Math.ceil((vouchers?.length || 0) / ITEMS_PER_PAGE);
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const currentItems = vouchers?.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    function renderVoucher(v) {
        const isApplied = voucherCode === v.code;
        const disabled = v.is_used || v.expired || v.outOfQuota;

        return (
            <div
                key={v.id}
                className={
                    "relative p-4 border rounded-xl bg-white shadow-sm transition flex flex-col justify-between " +
                    (isApplied
                        ? "border-green-500 bg-green-50"
                        : disabled
                            ? "border-gray-300 bg-gray-100 opacity-70"
                            : "border-gray-200 hover:border-red-400")
                }
            >
                <div>
                    <p className="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <FaTicketAlt className="text-red-500" /> {v.code}
                    </p>
                    <p className="text-xs text-gray-600 mt-1">
                        {v.type === "percent"
                            ? "Giảm " + v.value + "%"
                            : "Giảm " + Number(v.value).toLocaleString("vi-VN") + " đ"}
                    </p>
                    <p className="text-xs text-gray-400 mt-1">
                        HSD: {new Date(v.end_date).toLocaleDateString("vi-VN")}
                    </p>
                </div>
                {v.is_used === 1 && (
                    <div className="absolute inset-0 flex items-center justify-center bg-white/70 rounded-xl">
                        <span className="text-red-600 font-bold text-lg sm:text-xl rotate-[-15deg] border-2 border-red-600 px-4 py-1 rounded-lg">
                            ĐÃ SỬ DỤNG
                        </span>
                    </div>
                )}
            </div>
        );
    }

    function renderPaginationButton(i) {
        return (
            <button
                key={i}
                onClick={() => setCurrentPage(i + 1)}
                className={
                    "px-3 py-1 border rounded text-sm font-medium transition " +
                    (currentPage === i + 1
                        ? "bg-red-500 text-white border-red-500"
                        : "hover:bg-gray-100")
                }
            >
                {i + 1}
            </button>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between border-b pb-3">
                <h3 className="flex items-center gap-2 text-lg sm:text-xl font-semibold text-gray-800">
                    <FaTicketAlt className="text-red-500 text-xl sm:text-2xl" />
                    Voucher của bạn
                    <span className="ml-2 text-sm text-gray-500">
                        ({vouchers?.length || 0})
                    </span>
                </h3>
                <button
                    onClick={() => setIsClaimModalOpen(true)}
                    className="px-4 py-2 text-sm font-medium rounded-lg bg-red-500 text-white hover:bg-red-600 transition"
                >
                    Nhận voucher
                </button>
            </div>

            <ClaimVoucherModal
                isOpen={isClaimModalOpen}
                onClose={() => {
                    setIsClaimModalOpen(false);
                    refreshVouchers();
                }}
            />

            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    {Array.from({ length: ITEMS_PER_PAGE }).map((_, i) => (
                        <div
                            key={i}
                            className="p-4 border rounded-xl bg-white shadow-sm animate-pulse flex flex-col justify-between"
                        >
                            <div className="space-y-2">
                                <div className="h-4 bg-gray-300 rounded w-1/2"></div>
                                <div className="h-3 bg-gray-200 rounded w-1/3"></div>
                                <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                            </div>
                            <div className="h-6 bg-gray-200 rounded w-1/4 mt-4"></div>
                        </div>
                    ))}
                </div>
            ) : !vouchers || vouchers.length === 0 ? (
                <div className="text-center py-16">
                    <FaTicketAlt className="text-5xl text-gray-400 mx-auto mb-4" />
                    <h2 className="text-lg font-semibold text-gray-700">
                        Bạn chưa có voucher nào khả dụng
                    </h2>
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        {currentItems.map(renderVoucher)}
                    </div>

                    {totalPages > 1 && (
                        <div className="flex justify-center gap-2 mt-4">
                            <button
                                onClick={() => setCurrentPage(Math.max(currentPage - 1, 1))}
                                disabled={currentPage === 1}
                                className="px-3 py-1 border rounded text-sm font-medium disabled:opacity-50 hover:bg-gray-100"
                            >
                                ←
                            </button>
                            {Array.from({ length: totalPages }, (_, i) =>
                                renderPaginationButton(i)
                            )}
                            <button
                                onClick={() =>
                                    setCurrentPage(Math.min(currentPage + 1, totalPages))
                                }
                                disabled={currentPage === totalPages}
                                className="px-3 py-1 border rounded text-sm font-medium disabled:opacity-50 hover:bg-gray-100"
                            >
                                →
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default VoucherPage;
