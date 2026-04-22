import React, { useContext, useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'react-toastify';
import constants from '../../constants/constants';
import { UserContext } from '../../contexts/UserContext';

const UserInfoForm = () => {
    const [loading, setLoading] = useState(false);
    const { user, updateUser } = useContext(UserContext);

    const {
        register,
        handleSubmit,
        setValue,
        formState: { errors }
    } = useForm();

    useEffect(() => {
        if (user) {
            setValue("fullName", user.name || "");
            setValue("phone", user.phone || "");
            setValue("email", user.email || "");
        }
    }, [user, setValue]);

    const onSubmit = async (data) => {
        try {
            const token = localStorage.getItem("access_token");
            const userId = user.id;

            const response = await fetch(`${constants.BASE_URL}/users/${userId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json"
                },
                body: JSON.stringify({
                    name: data.fullName,
                    phone: data.phone
                }),
            });

            if (!response.ok) throw new Error("Cập nhật thất bại");

            const updatedUser = await response.json();
            updateUser(updatedUser);
            toast.success("Cập nhật thành công!");
        } catch (error) {
            toast.error("Có lỗi xảy ra khi cập nhật.");
            console.error(error);
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-2 sm:space-y-2">
            <h3 className="text-base sm:text-lg font-semibold text-gray-800">
                Thông tin cá nhân
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <input
                        type="text"
                        placeholder="Họ và tên"
                        {...register("fullName", { required: "Họ và tên không được để trống" })}
                        className={inputClass}
                    />
                    {errors.fullName && (
                        <p className="text-red-500 text-xs mt-1">{errors.fullName.message}</p>
                    )}
                </div>

                <div>
                    <input
                        type="text"
                        placeholder="Số điện thoại"
                        {...register("phone", {
                            required: "Số điện thoại không được để trống",
                            pattern: {
                                value: /^[0-9]{10,11}$/,
                                message: "Số điện thoại không hợp lệ",
                            },
                        })}
                        className={inputClass}
                    />
                    {errors.phone && (
                        <p className="text-red-500 text-xs mt-1">{errors.phone.message}</p>
                    )}
                </div>

                <div className="sm:col-span-2">
                    <input
                        type="email"
                        placeholder="Email"
                        {...register("email")}
                        readOnly
                        className={`${inputClass} bg-gray-100 cursor-not-allowed`}
                    />
                </div>
            </div>

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={loading}
                    className="bg-red-600 text-white px-5 py-2 rounded-md hover:bg-red-700 disabled:opacity-60 transition"
                >
                    {loading ? "Đang lưu..." : "Lưu thay đổi"}
                </button>
            </div>
        </form>
    );
}
const inputClass =
    "w-full border border-gray-300 rounded-md px-3 py-2 sm:px-4 sm:py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-red-500";

export default UserInfoForm;
