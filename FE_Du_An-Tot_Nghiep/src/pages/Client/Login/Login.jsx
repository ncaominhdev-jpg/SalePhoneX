import React, { useState, useContext, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import logo from "../../../assets/logo2.png";
import constants from "../../../constants/constants";
import { UserContext } from "../../../contexts/UserContext";
import { toast } from "react-toastify";

const Login = () => {
    const navigate = useNavigate();
    const { updateUser } = useContext(UserContext);
    const [form, setForm] = useState({ email: "", password: "" });
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState("");

    // Nếu đã đăng nhập thì tự động redirect
    useEffect(() => {
        const savedUser = JSON.parse(localStorage.getItem("user"));
        if (savedUser) {
            if (savedUser.role === "admin") {
                window.location.href = `${constants.BASE_DOMAIN}/admin/dashboard`;
            } else if (savedUser.role === "user") {
                navigate("/");
            }
        }
    }, [navigate]);

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
        setErrors({ ...errors, [e.target.name]: "" });
        setGeneralError("");
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        let newErrors = {};
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!form.email.trim()) {
            newErrors.email = "Vui lòng nhập email";
        } else if (!emailRegex.test(form.email)) {
            newErrors.email = "Email không hợp lệ";
        }

        if (!form.password.trim()) {
            newErrors.password = "Vui lòng nhập mật khẩu";
        }

        setErrors(newErrors);
        if (Object.keys(newErrors).length > 0) return;

        try {
            const response = await fetch(`${constants.BASE_URL}/login`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(form),
            });

            const data = await response.json();

            if (response.ok && data.access_token) {
                // Nếu user
                localStorage.setItem("access_token", data.access_token);
                localStorage.setItem("user", JSON.stringify(data.user));
                updateUser(data.user);

                toast.success("Đăng nhập thành công!", {
                    position: "top-right",
                    autoClose: 2500,
                    theme: "colored",
                });
                navigate("/");
            } else if (response.status === 401) {
                setGeneralError(data?.message || "Email hoặc mật khẩu không đúng.");
            } else if (response.status === 403) {
                setGeneralError(data?.message || "Tài khoản của bạn đã bị vô hiệu hóa.");
            } else if (response.status === 422 && data.errors) {
                const backendErrors = {};
                Object.keys(data.errors).forEach((key) => {
                    backendErrors[key] = data.errors[key][0];
                });
                setErrors(backendErrors);
            } else {
                setGeneralError("Đã xảy ra lỗi. Vui lòng thử lại sau.");
            }
        } catch (error) {
            console.error("Lỗi gọi API:", error);
            setGeneralError("Không thể kết nối đến máy chủ. Vui lòng kiểm tra mạng.");
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
                    Đăng nhập tài khoản
                </h2>

                {generalError && (
                    <p className="text-sm text-center text-red-600 mb-2">{generalError}</p>
                )}

                <form className="space-y-3 sm:space-y-4" onSubmit={handleSubmit}>
                    <div>
                        <label className="block text-xs sm:text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value={form.email}
                            onChange={handleChange}
                            placeholder="Nhập email"
                            className={`mt-1 w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-1 ${errors.email
                                ? "border-red-500 ring-red-500"
                                : "focus:ring-red-500"
                                }`}
                        />
                        {errors.email && (
                            <p className="text-xs text-red-500 mt-1">{errors.email}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs sm:text-sm font-medium text-gray-700">
                            Mật khẩu
                        </label>
                        <input
                            type="password"
                            name="password"
                            value={form.password}
                            onChange={handleChange}
                            placeholder="Nhập mật khẩu"
                            className={`mt-1 w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-1 ${errors.password
                                ? "border-red-500 ring-red-500"
                                : "focus:ring-red-500"
                                }`}
                        />
                        {errors.password && (
                            <p className="text-xs text-red-500 mt-1">{errors.password}</p>
                        )}
                    </div>

                    <button
                        type="submit"
                        className="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-md text-sm"
                    >
                        Đăng nhập
                    </button>

                    <div className="text-right text-xs sm:text-sm">
                        <Link to="/forgot-password" className="text-blue-600 hover:underline">
                            Quên mật khẩu?
                        </Link>
                    </div>
                </form>

                <p className="mt-4 sm:mt-6 text-center text-xs sm:text-sm">
                    Bạn chưa có tài khoản?{" "}
                    <Link to="/register" className="text-red-600 font-medium hover:underline">
                        Đăng ký ngay
                    </Link>
                </p>
            </div>
        </div>
    );
};

export default Login;
