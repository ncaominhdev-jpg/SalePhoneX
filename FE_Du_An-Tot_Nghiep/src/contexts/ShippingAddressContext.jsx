import React, { createContext, useContext, useState, useEffect } from "react";
import axios from "axios";
import constants from "../constants/constants";
import { toast } from "react-toastify";

const ShippingAddressContext = createContext();

export const ShippingAddressProvider = ({ children }) => {
    const [addresses, setAddresses] = useState([]);
    const [loading, setLoading] = useState(false);

    const getAuthConfig = () => {
        const rawToken = localStorage.getItem("access_token");
        if (!rawToken) {
            console.warn("⚠️ Không có token trong localStorage");
            return null;
        }
        const token = rawToken.startsWith("Bearer ")
            ? rawToken
            : `Bearer ${rawToken}`;

        return {
            headers: {
                Authorization: token,
                Accept: "application/json",
            },
        };
    };

    const fetchAddresses = async () => {
        const config = getAuthConfig();
        if (!config) return;

        try {
            setLoading(true);
            const res = await axios.get(
                `${constants.BASE_URL}/shipping-addresses`,
                config
            );
            setAddresses(res.data);
        } catch (err) {
            if (err.response?.status === 401) {
                console.error("❌ Token không hợp lệ hoặc đã hết hạn.");
                localStorage.removeItem("access_token");
                toast.warning("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
            } else {
                toast.error(
                    err.response?.data?.message ||
                    "Không thể tải địa chỉ. Vui lòng thử lại."
                );
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (typeof window !== "undefined") {
            const token = localStorage.getItem("access_token");
            if (token) {
                fetchAddresses();
            } else {
                console.warn("⚠️ Không có token trong localStorage khi mount");
            }
        }
    }, []);


    const addOrUpdateAddress = async (data, id = null) => {
        const config = getAuthConfig();
        if (!config) throw new Error("Chưa đăng nhập");

        const payload = {
            recipient_name: data.recipient_name,
            phone: data.phone,
            address: data.address,
            city: data.city,
            district: data.district,
            ward: data.ward,
            is_default: data.is_default === "true" || data.is_default === true,
        };

        try {
            if (id) {
                await axios.put(
                    `${constants.BASE_URL}/shipping-addresses/${id}`,
                    payload,
                    config
                );
            } else {
                await axios.post(
                    `${constants.BASE_URL}/shipping-addresses`,
                    payload,
                    config
                );
            }
            await fetchAddresses();
            toast.success("Địa chỉ đã được lưu thành công!");
        } catch (error) {
            if (error.response?.status === 401) {
                localStorage.removeItem("access_token");
                toast.warning("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
            } else {
                toast.error(
                    error.response?.data?.message ||
                    "Không thể lưu địa chỉ. Vui lòng thử lại."
                );
            }
            throw error;
        }
    };

    const deleteAddress = async (id) => {
        const config = getAuthConfig();
        if (!config) return;

        try {
            await axios.delete(
                `${constants.BASE_URL}/shipping-addresses/${id}`,
                config
            );
            fetchAddresses();
            toast.success("Địa chỉ đã được xoá thành công!");
        } catch (err) {
            if (err.response?.status === 401) {
                localStorage.removeItem("access_token");
                toast.warning("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
            } else {
                toast.error(
                    err.response?.data?.message ||
                    "Không thể xoá địa chỉ. Vui lòng thử lại."
                );
            }
        }
    };

    return (
        <ShippingAddressContext.Provider
            value={{
                addresses,
                loading,
                fetchAddresses,
                addOrUpdateAddress,
                deleteAddress,
            }}
        >
            {children}
        </ShippingAddressContext.Provider>
    );
};

export const useShippingAddress = () => useContext(ShippingAddressContext);
