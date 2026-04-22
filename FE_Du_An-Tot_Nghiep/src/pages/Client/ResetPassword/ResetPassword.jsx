import React, { useState, useEffect } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import logo from "../../../assets/logo2.png";
import constants from "../../../constants/constants";
import { toast } from "react-toastify";

const ResetPassword = () => {
    const navigate = useNavigate();
    const location = useLocation();

    const [form, setForm] = useState({ password: "", confirmPassword: "" });
    const [errors, setErrors] = useState({});
    const [submitted, setSubmitted] = useState(false);
    const [email, setEmail] = useState("");
    const [token, setToken] = useState("");
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    useEffect(() => {
        const params = new URLSearchParams(location.search);
        const emailParam = params.get("email") || "";
        const tokenParam = params.get("token") || "";
        
        setEmail(emailParam);
        setToken(tokenParam);

        // Validate URL parameters
        if (!emailParam || !tokenParam) {
            toast.error("❌ Link reset không hợp lệ!");
            setTimeout(() => navigate("/forgot-password"), 2000);
        }
    }, [location.search, navigate]);

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
        // Clear error when user starts typing
        if (errors[e.target.name]) {
            setErrors({ ...errors, [e.target.name]: "" });
        }
    };

    const validateForm = () => {
        let newErrors = {};

        if (!form.password.trim()) {
            newErrors.password = "Vui lòng nhập mật khẩu mới";
        } else if (form.password.length < 8) {
            newErrors.password = "Mật khẩu phải có ít nhất 8 ký tự";
        }

        if (!form.confirmPassword.trim()) {
            newErrors.confirmPassword = "Vui lòng xác nhận lại mật khẩu";
        } else if (form.password !== form.confirmPassword) {
            newErrors.confirmPassword = "Mật khẩu không khớp";
        }

        if (!email || !token) {
            newErrors.token = "Thiếu thông tin email hoặc token.";
        }

        return newErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const newErrors = validateForm();
        setErrors(newErrors);
        
        if (Object.keys(newErrors).length > 0) {
            toast.error("❌ Vui lòng kiểm tra lại thông tin nhập!");
            return;
        }

        setLoading(true);

        try {
            const res = await fetch(`${constants.BASE_URL}/password/reset`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    email,
                    token,
                    password: form.password,
                    password_confirmation: form.confirmPassword
                })
            });

            const result = await res.json();

            if (!res.ok) {
                if (result.errors) {
                    // Handle validation errors from Laravel
                    setErrors(result.errors);
                    const firstError = Object.values(result.errors)[0][0];
                    toast.error(`❌ ${firstError}`);
                } else {
                    setErrors({ general: result.message || "Đã xảy ra lỗi." });
                    toast.error(`❌ ${result.message || "Đặt lại mật khẩu thất bại"}`);
                }
                return;
            }

            if (result.success) {
                setSubmitted(true);
                toast.success("✅ Mật khẩu đã được đặt lại thành công!");
                setTimeout(() => navigate("/login"), 3000);
            } else {
                setErrors({ general: result.message || "Có lỗi xảy ra" });
                toast.error(`❌ ${result.message || "Đặt lại mật khẩu thất bại"}`);
            }
        } catch (err) {
            setErrors({ general: "Lỗi kết nối server." });
            toast.error("❌ Không thể kết nối đến máy chủ!");
        } finally {
            setLoading(false);
        }
    };

    const togglePasswordVisibility = (field) => {
        if (field === 'password') {
            setShowPassword(!showPassword);
        } else {
            setShowConfirmPassword(!showConfirmPassword);
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
                    Đặt lại mật khẩu
                </h2>

                {submitted ? (
                    <div className="text-center space-y-4">
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-green-100 rounded-full">
                                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 className="text-lg font-medium text-green-800 mb-2">Thành công!</h3>
                            <p className="text-sm text-green-700 mb-4">
                                Mật khẩu của bạn đã được cập nhật thành công.
                            </p>
                            <p className="text-xs text-green-600">
                                Bạn sẽ được chuyển đến trang đăng nhập sau 3 giây...
                            </p>
                        </div>
                        
                        <Link 
                            to="/login" 
                            className="inline-flex items-center px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        >
                            Đăng nhập ngay
                        </Link>
                    </div>
                ) : (
                    <form className="space-y-3 sm:space-y-4" onSubmit={handleSubmit}>
                        {errors.general && (
                            <div className="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p className="text-sm text-red-700 text-center">{errors.general}</p>
                            </div>
                        )}

                        <div className="text-sm text-gray-600 text-center mb-4">
                            Nhập mật khẩu mới cho tài khoản: <strong>{email}</strong>
                        </div>

                        <div>
                            <label className="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                Mật khẩu mới
                            </label>
                            <div className="relative">
                                <input
                                    type={showPassword ? "text" : "password"}
                                    name="password"
                                    value={form.password}
                                    onChange={handleChange}
                                    placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)"
                                    className={`mt-1 w-full px-3 py-2 sm:px-4 sm:py-2 pr-10 border rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 transition-colors ${
                                        errors.password 
                                            ? "border-red-500 ring-2 ring-red-200" 
                                            : "border-gray-300 focus:ring-red-500 focus:border-red-500"
                                    }`}
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    onClick={() => togglePasswordVisibility('password')}
                                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg className="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        {showPassword ? (
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                        ) : (
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        )}
                                    </svg>
                                </button>
                            </div>
                            {errors.password && <p className="text-xs text-red-500 mt-1">{errors.password}</p>}
                        </div>

                        <div>
                            <label className="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                Xác nhận mật khẩu
                            </label>
                            <div className="relative">
                                <input
                                    type={showConfirmPassword ? "text" : "password"}
                                    name="confirmPassword"
                                    value={form.confirmPassword}
                                    onChange={handleChange}
                                    placeholder="Nhập lại mật khẩu mới"
                                    className={`mt-1 w-full px-3 py-2 sm:px-4 sm:py-2 pr-10 border rounded-md text-xs sm:text-sm focus:outline-none focus:ring-2 transition-colors ${
                                        errors.confirmPassword 
                                            ? "border-red-500 ring-2 ring-red-200" 
                                            : "border-gray-300 focus:ring-red-500 focus:border-red-500"
                                    }`}
                                    disabled={loading}
                                />
                                <button
                                    type="button"
                                    onClick={() => togglePasswordVisibility('confirm')}
                                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg className="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        {showConfirmPassword ? (
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                        ) : (
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        )}
                                    </svg>
                                </button>
                            </div>
                            {errors.confirmPassword && (
                                <p className="text-xs text-red-500 mt-1">{errors.confirmPassword}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={loading || !form.password || !form.confirmPassword}
                            className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold py-3 px-4 rounded-lg text-sm transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl flex items-center justify-center"
                        >
                            {loading ? (
                                <>
                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Đang xử lý...
                                </>
                            ) : (
                                "Cập nhật mật khẩu"
                            )}
                        </button>
                    </form>
                )}

                <p className="mt-6 text-center text-sm text-gray-600">
                    Quay lại{" "}
                    <Link to="/login" className="text-red-600 font-medium hover:underline">
                        Đăng nhập
                    </Link>
                </p>
            </div>
        </div>
    );
};

export default ResetPassword;