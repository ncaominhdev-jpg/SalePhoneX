import React, { useState } from "react";
import { Link } from "react-router-dom";
import logo from "../../../assets/logo2.png";
import constants from "../../../constants/constants";
import { toast } from "react-toastify";

const ForgotPassword = () => {
    const [email, setEmail] = useState("");
    const [error, setError] = useState("");
    const [submitted, setSubmitted] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.trim()) {
            setError("Vui lòng nhập email");
            toast.error("❌ Vui lòng nhập email hợp lệ!");
            return;
        } else if (!emailRegex.test(email)) {
            setError("Email không hợp lệ");
            toast.error("❌ Email không đúng định dạng!");
            return;
        }

        setError("");
        setLoading(true);

        try {
            const res = await fetch(`${constants.BASE_URL}/password/forgot`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ email })
            });

            const data = await res.json();

            if (!res.ok) {
                setError(data.message || "Đã xảy ra lỗi.");
                toast.error(`❌ ${data.message || "Gửi liên kết thất bại"}`);
                return;
            }

            if (data.success) {
                setSubmitted(true);
                toast.success("✅ Link reset mật khẩu đã được gửi đến email của bạn!");
            } else {
                setError(data.message || "Có lỗi xảy ra");
                toast.error(`❌ ${data.message || "Gửi liên kết thất bại"}`);
            }
        } catch (err) {
            setError("Không thể kết nối tới máy chủ.");
            toast.error("❌ Không thể kết nối tới máy chủ.");
        } finally {
            setLoading(false);
        }
    };

    const handleResend = () => {
        setSubmitted(false);
        setEmail("");
        setError("");
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
                    Quên mật khẩu
                </h2>

                {submitted ? (
                    <div className="text-center space-y-4">
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-green-100 rounded-full">
                                <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 className="text-lg font-medium text-green-800 mb-2">Email đã được gửi!</h3>
                            <p className="text-sm text-green-700 mb-4">
                                Chúng tôi đã gửi link reset mật khẩu đến email: <strong>{email}</strong>
                            </p>
                            <p className="text-xs text-green-600">
                                Vui lòng kiểm tra email và click vào link để reset mật khẩu. 
                                Link sẽ hết hạn sau 60 phút.
                            </p>
                        </div>
                        
                        <div className="text-sm text-gray-600">
                            Không nhận được email? 
                            <button 
                                onClick={handleResend}
                                className="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-md hover:shadow-lg"
                            >
                                Gửi lại
                            </button>
                        </div>
                    </div>
                ) : (
                    <form className="space-y-4" onSubmit={handleSubmit}>
                        <div className="text-sm text-gray-600 text-center mb-4">
                            Nhập email của bạn để nhận link reset mật khẩu
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                type="email"
                                value={email}
                                onChange={(e) => {
                                    setEmail(e.target.value);
                                    setError("");
                                }}
                                placeholder="Nhập email của bạn"
                                className={`mt-1 w-full px-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 transition-colors ${
                                    error 
                                        ? "border-red-500 ring-2 ring-red-200" 
                                        : "border-gray-300 focus:ring-red-500 focus:border-red-500"
                                }`}
                                disabled={loading}
                            />
                            {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={loading || !email.trim()}
                            className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-semibold py-3 px-4 rounded-lg text-sm transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl flex items-center justify-center"
                        >
                            {loading ? (
                                <>
                                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Đang gửi...
                                </>
                            ) : (
                                "Gửi link reset mật khẩu"
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

export default ForgotPassword;