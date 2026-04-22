import React from "react";
import { Link } from "react-router-dom";

const Forbidden = () => {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50">
            <h1 className="text-6xl font-bold text-red-600">403</h1>
            <h2 className="text-2xl font-semibold text-gray-800 mt-4">
                Không có quyền truy cập
            </h2>
            <p className="text-gray-600 mt-2 text-center max-w-md">
                Xin lỗi, bạn không có quyền truy cập vào trang này.
                Vui lòng quay lại trang chủ hoặc đăng nhập bằng tài khoản khác.
            </p>

            <div className="flex gap-4 mt-6">
                <Link
                    to="/"
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                >
                    Về Trang Chủ
                </Link>
                <Link
                    to="/login"
                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                >
                    Đăng nhập lại
                </Link>
            </div>
        </div>
    );
};

export default Forbidden;
