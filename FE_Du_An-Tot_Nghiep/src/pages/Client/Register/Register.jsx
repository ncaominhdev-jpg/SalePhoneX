import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import logo from "../../../assets/logo2.png";
import constants from "../../../constants/constants";
import { toast } from "react-toastify";
import axios from "axios";

const Register = () => {
    const navigate = useNavigate();
    const [form, setForm] = useState({
        name: "",
        email: "",
        phone: "",
        password: "",
        confirmPassword: "",
    });

    const [errors, setErrors] = useState({});

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm((prev) => ({ ...prev, [name]: value }));

        if (errors[name]) {
            setErrors((prev) => {
                const updated = { ...prev };
                delete updated[name];
                return updated;
            });
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        const newErrors = {};
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneRegex = /^[0-9]{9,}$/;

        if (!form.name.trim()) newErrors.name = "Vui lòng nhập họ tên.";
        if (!form.email.trim()) {
            newErrors.email = "Vui lòng nhập email.";
        } else if (!emailRegex.test(form.email)) {
            newErrors.email = "Email không đúng định dạng.";
        }
        if (!form.phone.trim()) {
            newErrors.phone = "Vui lòng nhập số điện thoại.";
        } else if (!phoneRegex.test(form.phone)) {
            newErrors.phone = "Số điện thoại không hợp lệ.";
        }
        if (!form.password.trim()) {
            newErrors.password = "Vui lòng nhập mật khẩu.";
        } else if (form.password.length < 6) {
            newErrors.password = "Mật khẩu phải ít nhất 6 ký tự.";
        }
        if (!form.confirmPassword.trim()) {
            newErrors.confirmPassword = "Vui lòng xác nhận mật khẩu.";
        } else if (form.password !== form.confirmPassword) {
            newErrors.confirmPassword = "Mật khẩu không khớp.";
        }

        setErrors(newErrors);

        if (Object.keys(newErrors).length === 0) {
            const payload = {
                name: form.name,
                email: form.email,
                phone: form.phone,
                password: form.password,
                password_confirmation: form.confirmPassword,
            };

            try {
                const res = await axios.post(`${constants.BASE_URL}/register`, payload, {
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                });

                if (res.data?.access_token) {
                    toast.success("Đăng ký thành công! Vui lòng đăng nhập.");
                    setTimeout(() => navigate("/login"), 2000);
                } else {
                    toast.error("Đăng ký thất bại. Vui lòng thử lại.");
                }
            } catch (error) {
                if (error.response?.status === 422 && error.response.data.errors) {
                    const backendErrors = {};
                    Object.keys(error.response.data.errors).forEach((key) => {
                        const msg = error.response.data.errors[key][0];
                        if (msg === "The email has already been taken.") {
                            backendErrors.email = "Email này đã được sử dụng.";
                        } else if (msg.includes("password")) {
                            backendErrors.password = "Mật khẩu không hợp lệ.";
                        } else {
                            backendErrors[key] = msg;
                        }
                    });
                    setErrors(backendErrors);
                    toast.error("Vui lòng kiểm tra lại thông tin.");
                } else {
                    console.error("Lỗi API:", error);
                    toast.error("Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.");
                }
            }
        } else {
            toast.error("Vui lòng kiểm tra lại các trường nhập.");
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-2 sm:px-4">
            <div className="w-full max-w-sm sm:max-w-md bg-white rounded-xl shadow-md p-4 sm:p-8">
                <Link to="/">
                    <div className="flex justify-center mb-4 sm:mb-6">
                        <div className="bg-red-600 px-2 py-1 rounded-md">
                            <img src={logo} alt="Logo" className="h-12 sm:h-16" />
                        </div>
                    </div>
                </Link>

                <h2 className="text-lg sm:text-2xl font-bold text-center text-gray-800 mb-4 sm:mb-6">
                    Đăng ký tài khoản
                </h2>

                <form onSubmit={handleSubmit} className="space-y-3 sm:space-y-4">
                    {[
                        { label: "Họ và tên", name: "name", type: "text", placeholder: "Nhập họ tên" },
                        { label: "Email", name: "email", type: "text", placeholder: "Nhập email" },
                        { label: "Số điện thoại", name: "phone", type: "text", placeholder: "Nhập số điện thoại" },
                        { label: "Mật khẩu", name: "password", type: "password", placeholder: "Nhập mật khẩu" },
                        { label: "Xác nhận mật khẩu", name: "confirmPassword", type: "password", placeholder: "Nhập lại mật khẩu" },
                    ].map(({ label, name, type, placeholder }) => (
                        <div key={name}>
                            <label className="block text-sm font-medium text-gray-700">{label}</label>
                            <input
                                type={type}
                                name={name}
                                value={form[name]}
                                onChange={handleChange}
                                placeholder={placeholder}
                                className={`mt-1 w-full px-3 py-2 border rounded-md text-sm ${errors[name] ? "border-red-500" : ""
                                    }`}
                            />
                            {errors[name] && <p className="text-xs text-red-500 mt-1">{errors[name]}</p>}
                        </div>
                    ))}

                    <button
                        type="submit"
                        className="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-md text-sm"
                    >
                        Đăng ký
                    </button>
                </form>

                <p className="mt-4 text-center text-xs sm:text-sm">
                    Bạn đã có tài khoản?{" "}
                    <Link to="/login" className="text-red-600 font-medium hover:underline">
                        Đăng nhập
                    </Link>
                </p>
            </div>
        </div>
    );
};

export default Register;
