// src/components/Voucher/VoucherModal.jsx
import React from "react";
import { FaTimes, FaTag } from "react-icons/fa";
import { useVouchers } from "../../contexts/VoucherContext";

const VoucherModal = ({ isOpen, onClose, voucherCode, onApplyVoucher }) => {
    const { vouchers, loading, refreshVouchers } = useVouchers();

    if (!isOpen) return null;

    const filteredVouchers = vouchers.filter(
        (v) => !v.expired && !v.outOfQuota && !v.is_used
    );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 px-4">
            <div className="bg-white rounded-lg w-full max-w-md sm:max-w-lg p-4 sm:p-6 shadow-xl relative mt-20 max-h-[80vh] overflow-y-auto">

                {/* Nút đóng */}
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 text-gray-500 hover:text-red-500 text-2xl"
                >
                    <FaTimes />
                </button>

                {/* Tiêu đề */}
                <h2 className="text-base sm:text-lg font-semibold mb-4 flex items-center gap-2 text-gray-800">
                    <FaTag className="text-red-600" /> Chọn mã giảm giá
                </h2>


                {/* Nội dung */}
                {loading ? (
                    <p className="text-center text-sm text-gray-500 py-6">Đang tải voucher...</p>
                ) : filteredVouchers.length === 0 ? (
                    <p className="text-center text-sm text-gray-500 py-6">Bạn chưa có voucher khả dụng</p>
                ) : (
                    <div className="space-y-3">
                        {filteredVouchers.map((v) => {
                            const isApplied = voucherCode === v.code;
                            return (
                                <div
                                    key={v.id}
                                    className={`p-3 sm:p-4 border rounded-md flex justify-between items-center text-sm sm:text-base transition ${isApplied
                                        ? "bg-green-100 border-green-400 text-green-700 font-medium hover:bg-green-200"
                                        : "border-gray-200 hover:bg-gray-50"
                                        }`}
                                >
                                    <div>
                                        <p className="font-semibold">{v.code}</p>
                                        <p className="text-xs text-gray-500">
                                            {v.type === "percent"
                                                ? `Giảm ${Number(v.value)}%`
                                                : `Giảm ${Number(v.value).toLocaleString()}`}
                                        </p>
                                        <p className="text-xs text-gray-400">
                                            HSD: {new Date(v.end_date).toLocaleDateString("vi-VN")}
                                        </p>
                                    </div>
                                    <button
                                        onClick={async () => {
                                            if (isApplied) {
                                                await onApplyVoucher("");
                                            } else {
                                                await onApplyVoucher(v.code);
                                            }
                                            refreshVouchers();
                                            onClose();
                                        }}
                                        className={`px-4 py-2 rounded-md text-sm font-medium ${isApplied
                                            ? "bg-gray-400 text-white cursor-not-allowed"
                                            : "bg-red-500 text-white hover:bg-red-600"
                                            }`}
                                    >
                                        {isApplied ? "Bỏ áp dụng" : "Áp dụng"}
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
};

export default VoucherModal;
