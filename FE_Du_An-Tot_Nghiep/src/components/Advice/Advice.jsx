import React, { useState } from "react";
import { toast } from "react-toastify";
import axios from "axios";
import constants from "../../constants/constants";
import { FaRegClock } from "react-icons/fa";
import { IoMdClose } from "react-icons/io";
import logo from '../../assets/logo2.png';

const Advice = ({ onClose, item }) => {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    const [form, setForm] = useState({
        company_name: "",
        customer_name: "",
        phone: "",
        email: "",
        product_variant_id: item?.variantId || null,
        quantity: item?.quantity || 1,
        note: "",
        receive_promotions: true,
    });

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === "checkbox" ? checked : value });
        setErrors({ ...errors, [name]: "" }); // clear lỗi khi nhập lại
    };

    // Hàm validate client-side
    const validateForm = () => {
        const newErrors = {};

        if (!form.company_name.trim()) {
            newErrors.company_name = "Tên công ty không được bỏ trống.";
        }
        if (!form.customer_name.trim()) {
            newErrors.customer_name = "Tên quý khách không được bỏ trống.";
        }
        if (!form.phone.trim()) {
            newErrors.phone = "Số điện thoại không được bỏ trống.";
        } else if (!/^(0[0-9]{9,10})$/.test(form.phone)) {
            newErrors.phone = "Số điện thoại không hợp lệ (VD: 0912345678).";
        }
        if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            newErrors.email = "Địa chỉ email không hợp lệ.";
        }
        if (!form.quantity || form.quantity < 1) {
            newErrors.quantity = "Số lượng phải lớn hơn 0.";
        }

        return newErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }

        setLoading(true);
        try {
            const token = localStorage.getItem("access_token");
            const payload = {
                ...form,
                user_id: JSON.parse(localStorage.getItem("user"))?.id || null,
            };

            await axios.post(`${constants.BASE_URL}/consult-requests`, payload, {
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
            });

            toast.success("Gửi thông tin tư vấn thành công!");
            onClose();
        } catch (error) {
            toast.error("Có lỗi khi gửi thông tin. Vui lòng thử lại.");
            console.error("Lỗi gửi yêu cầu tư vấn:", error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-fadeIn">
                {/* Header */}
                <div className="bg-gradient-to-r from-red-600 to-red-500 text-white px-8 grid grid-cols-3 items-center relative">
                    {/* Logo bên trái */}
                    <div className="flex items-center">
                        <img
                            src={logo}
                            alt="SalephoneX Logo"
                            className="w-20 h-20 object-contain"
                        />
                    </div>

                    {/* Tiêu đề căn giữa */}
                    <h2 className="text-lg font-bold text-center">
                        Đăng ký tư vấn
                    </h2>

                    {/* Nút đóng bên phải */}
                    <div className="flex justify-end">
                        <button
                            onClick={onClose}
                            disabled={loading}
                            className="text-2xl p-1 rounded-full hover:bg-red-700 transition"
                        >
                            <IoMdClose />
                        </button>
                    </div>
                </div>

                {/* Content */}
                <form onSubmit={handleSubmit} className="p-6 space-y-5 text-sm">
                    <p className="text-gray-700 text-center leading-relaxed">
                        Quý khách đang quan tâm đến đơn hàng số lượng lớn.
                        Vui lòng để lại thông tin bên dưới, đội ngũ <span className="font-semibold">SalephoneX </span>
                        sẽ liên hệ và gửi báo giá ưu đãi nhất trong thời gian sớm nhất.
                    </p>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Thông tin công ty</label>
                            <input
                                name="company_name"
                                value={form.company_name}
                                onChange={handleChange}
                                placeholder="Nhập tên công ty"
                                className={`${inputClass} ${errors.company_name ? "border-red-500" : ""}`}
                            />
                            {errors.company_name && (
                                <p className="text-red-600 text-xs mt-1">{errors.company_name}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Họ và tên</label>
                            <input
                                name="customer_name"
                                value={form.customer_name}
                                onChange={handleChange}
                                placeholder="Nhập họ và tên"
                                className={`${inputClass} ${errors.customer_name ? "border-red-500" : ""}`}
                            />
                            {errors.customer_name && (
                                <p className="text-red-600 text-xs mt-1">{errors.customer_name}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Số điện thoại</label>
                            <input
                                name="phone"
                                value={form.phone}
                                onChange={handleChange}
                                placeholder="Số điện thoại"
                                className={`${inputClass} ${errors.phone ? "border-red-500" : ""}`}
                            />
                            {errors.phone && (
                                <p className="text-red-600 text-xs mt-1">{errors.phone}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                name="email"
                                type="text"
                                value={form.email}
                                onChange={handleChange}
                                placeholder="Địa chỉ email"
                                className={`${inputClass} ${errors.email ? "border-red-500" : ""}`}
                            />
                            {errors.email && (
                                <p className="text-red-600 text-xs mt-1">{errors.email}</p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Sản phẩm bạn quan tâm</label>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-1">
                            <input
                                value={`${item?.productName} - ${item?.name}`}
                                readOnly
                                className="bg-gray-50 cursor-not-allowed text-gray-700 border rounded-lg px-3 py-2 text-sm"
                            />
                            <div>
                                <input
                                    name="quantity"
                                    type="number"
                                    min={1}
                                    value={form.quantity}
                                    onChange={handleChange}
                                    className={`${inputClass} ${errors.quantity ? "border-red-500" : ""}`}
                                />
                                {errors.quantity && (
                                    <p className="text-red-600 text-xs mt-1">{errors.quantity}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Ghi chú</label>
                        <textarea
                            name="note"
                            rows="3"
                            placeholder="Nhập ghi chú..."
                            value={form.note}
                            onChange={handleChange}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-red-500 focus:border-red-500"
                        />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="receive_promotions"
                            checked={form.receive_promotions}
                            onChange={handleChange}
                            className="w-4 h-4 text-red-600 border-gray-300 rounded"
                        />
                        Nhận thông tin khuyến mãi
                    </label>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full bg-gradient-to-r from-red-600 to-red-500 text-white font-semibold py-3 rounded-lg shadow-md hover:from-red-700 hover:to-red-600 transition"
                    >
                        {loading ? "Đang gửi..." : "Đăng ký nhận tư vấn giá tốt nhất"}
                    </button>
                </form>
            </div>
        </div>
    );
};

const inputClass =
    "w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-red-500";

export default Advice;
