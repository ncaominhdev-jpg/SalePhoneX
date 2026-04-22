// src/contexts/VoucherContext.jsx
import React, { createContext, useContext, useState, useEffect, useCallback } from "react";
import axios from "axios";
import constants from "../constants/constants";

const VoucherContext = createContext();

export const VoucherProvider = ({ children }) => {
    const [vouchers, setVouchers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [voucherCode, setVoucherCode] = useState("");
    const [discount, setDiscount] = useState(0);
    const [finalTotal, setFinalTotal] = useState(0);

    const [user, setUser] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem("user"));
        } catch {
            return null;
        }
    });
    const [token, setToken] = useState(() => localStorage.getItem("access_token"));

    // ✅ Hàm fetch voucher (có log debug)
    const fetchVouchers = useCallback(async () => {
        setLoading(true);
        try {
            let userVouchers = [];

            if (user && token) {
                const userRes = await axios.get(
                    `${constants.BASE_URL}/user/${user.id}/vouchers`,
                    { headers: { Authorization: `Bearer ${token}` } }
                );
                userVouchers = userRes.data;
                console.log("🎯 User vouchers API trả về:", userVouchers);
            }

            const today = new Date();

            const combined = userVouchers.map((v) => {
                const expired = v.end_date && new Date(v.end_date) < today;
                const outOfQuota = v.usage_limit !== null && v.used >= v.usage_limit;

                return {
                    ...v,
                    is_used: v.is_used ?? false,
                    expired,
                    outOfQuota,
                    claimed: true,
                    disabled: expired || outOfQuota || (v.is_used ?? false),
                };
            });

            setVouchers(combined.filter((v) => !v.expired && !v.outOfQuota));
        } catch (err) {
            console.error("❌ Lỗi khi lấy voucher:", err);
            setVouchers([]);
        } finally {
            setLoading(false);
        }
    }, [user, token]);


    // ✅ Gọi lại API mỗi lần vào trang Voucher
    useEffect(() => {
        fetchVouchers();
    }, [fetchVouchers]);

    // Nghe sự kiện thay đổi storage (login/logout tab khác)
    useEffect(() => {
        function handleStorageChange() {
            setUser(JSON.parse(localStorage.getItem("user")));
            setToken(localStorage.getItem("access_token"));
            fetchVouchers(); // gọi lại khi user/token thay đổi
        }
        window.addEventListener("storage", handleStorageChange);
        return () => window.removeEventListener("storage", handleStorageChange);
    }, [fetchVouchers]);

    // ✅ Hàm áp dụng voucher
    const applyVoucher = async (code, subtotal) => {
        try {
            if (!code) {
                // nếu code rỗng -> bỏ áp dụng
                setVoucherCode("");
                setDiscount(0);
                setFinalTotal(subtotal);
                return;
            }

            const res = await axios.post(
                `${constants.BASE_URL}/vouchers/apply`,
                { code, total_amount: subtotal },
                { headers: { Authorization: `Bearer ${token}` } }
            );

            setVoucherCode(code);
            setDiscount(res.data.discount);
            setFinalTotal(res.data.final_total);

            setVouchers((prev) =>
                prev.map((v) =>
                    v.code === code ? { ...v, is_used: true, disabled: true } : v
                )
            );

            return res.data;
        } catch (err) {
            setVoucherCode("");
            setDiscount(0);
            setFinalTotal(0);
            throw err;
        }
    };

    return (
        <VoucherContext.Provider
            value={{
                vouchers,
                loading,
                voucherCode,
                discount,
                finalTotal,
                applyVoucher,
                refreshVouchers: fetchVouchers, // ✅ hàm gọi lại API
            }}
        >
            {children}
        </VoucherContext.Provider>
    );
};

export const useVouchers = () => useContext(VoucherContext);
