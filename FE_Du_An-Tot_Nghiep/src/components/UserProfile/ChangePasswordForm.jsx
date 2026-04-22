import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import constants from '../../constants/constants';
import { Link } from 'react-router-dom';
import { toast } from 'react-toastify';

const ChangePasswordForm = () => {
    const { register, handleSubmit, watch, reset, formState: { errors } } = useForm();
    const [loading, setLoading] = useState(false);

    const onSubmit = async (data) => {
        setLoading(true);

        const toastId = toast.loading("Đang đổi mật khẩu...");

        try {
            const access_token = localStorage.getItem('access_token');
            const res = await fetch(`${constants.BASE_URL}/password/change`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${access_token}`
                },
                body: JSON.stringify({
                    current_password: data.currentPassword,
                    new_password: data.newPassword,
                    new_password_confirmation: data.confirmPassword
                })
            });

            const result = await res.json();

            if (!res.ok) {
                toast.update(toastId, {
                    render: result.message || "Đổi mật khẩu thất bại.",
                    type: "error",
                    isLoading: false,
                    autoClose: 4000,
                    theme: "colored"
                });
            } else {
                toast.update(toastId, {
                    render: result.message || "Đổi mật khẩu thành công!",
                    type: "success",
                    isLoading: false,
                    autoClose: 3000,
                    theme: "colored"
                });
                reset();
            }
        } catch (err) {
            toast.update(toastId, {
                render: "Không thể kết nối đến máy chủ.",
                type: "error",
                isLoading: false,
                autoClose: 4000,
                theme: "colored"
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-2 sm:space-y-2">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800">
                Đổi mật khẩu
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <input
                        type="password"
                        placeholder="Mật khẩu hiện tại"
                        {...register("currentPassword", {
                            required: "Vui lòng nhập mật khẩu hiện tại",
                        })}
                        className={inputClass}
                    />
                    {errors.currentPassword && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.currentPassword.message}
                        </p>
                    )}
                </div>

                <div>
                    <input
                        type="password"
                        placeholder="Mật khẩu mới"
                        {...register("newPassword", {
                            required: "Vui lòng nhập mật khẩu mới",
                            minLength: {
                                value: 6,
                                message: "Mật khẩu tối thiểu 6 ký tự",
                            },
                        })}
                        className={inputClass}
                    />
                    {errors.newPassword && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.newPassword.message}
                        </p>
                    )}
                </div>

                <div className="sm:col-span-2">
                    <input
                        type="password"
                        placeholder="Xác nhận mật khẩu mới"
                        {...register("confirmPassword", {
                            required: "Vui lòng xác nhận mật khẩu",
                            validate: (value) =>
                                value === watch("newPassword") || "Mật khẩu không khớp",
                        })}
                        className={inputClass}
                    />
                    {errors.confirmPassword && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.confirmPassword.message}
                        </p>
                    )}
                </div>
            </div>

            <div className="flex justify-between items-center mt-1">
                <p className="text-xs text-gray-500">
                    Quên mật khẩu?{" "}
                    <Link to="/forgot-password" className="text-blue-600 hover:underline">
                        Lấy lại mật khẩu tại đây
                    </Link>
                </p>
                <button
                    type="submit"
                    disabled={loading}
                    className="bg-red-600 text-white px-5 py-2 rounded-md hover:bg-red-700 disabled:opacity-60 transition"
                >
                    {loading ? "Đang xử lý..." : "Đổi mật khẩu"}
                </button>
            </div>
        </form>
    );
}
const inputClass =
    "w-full border border-gray-300 rounded-md px-3 py-2 sm:px-4 sm:py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-red-500";

export default ChangePasswordForm;
