import React, { useContext } from 'react';
import {
    FaUser, FaTicketAlt, FaSignOutAlt, FaHistory, FaMapMarkedAlt, FaHeart
} from 'react-icons/fa';
import info from '../../assets/25c6b0b2-7750-4b33-a857-bd3baaed78a1.png';
import constants from '../../constants/constants';
import { useNavigate } from 'react-router-dom';
import { UserContext } from '../../contexts/UserContext';
import { toast } from 'react-toastify';
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const SidebarMenu = ({ tab, setTab }) => {
    const navigate = useNavigate();
    const { user, updateUser } = useContext(UserContext);

    const handleLogout = async () => {
        const result = await Swal.fire({
            title: "Bạn có chắc muốn đăng xuất?",
            showCancelButton: true,
            confirmButtonText: "Đăng xuất",
            cancelButtonText: "Hủy",
            reverseButtons: true,
            customClass: {
                popup: "w-[90%] sm:w-[400px] rounded-xl p-5",
                title: "text-base sm:text-lg font-semibold text-gray-800",
                htmlContainer: "text-sm sm:text-base text-gray-600",
                actions:
                    "flex flex-col sm:flex-row justify-center gap-3 mt-5 w-full",
                confirmButton:
                    "w-full sm:w-auto bg-red-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-red-700 transition",
                cancelButton:
                    "w-full sm:w-auto bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition",
            },
            buttonsStyling: false,
        });

        if (!result.isConfirmed) return;

        const token = localStorage.getItem("access_token") || sessionStorage.getItem("access_token");

        try {
            if (token) {
                await fetch(`${constants.BASE_URL}/logout`, {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${token}`,
                        Accept: "application/json",
                    },
                });
            }

            localStorage.removeItem("access_token");
            sessionStorage.removeItem("access_token");
            updateUser(null);

            Swal.fire({
                icon: "success",
                title: "Đã đăng xuất",
                text: "Bạn đã đăng xuất thành công!",
                confirmButtonColor: "#3085d6",
                timer: 2000,
                showConfirmButton: false,
            });

            navigate("/");
        } catch (error) {
            console.error("Lỗi khi gọi API đăng xuất:", error);
            Swal.fire({
                icon: "error",
                title: "Lỗi!",
                text: "Đăng xuất thất bại, vui lòng thử lại.",
                confirmButtonColor: "#d33",
            });
        }
    };

    const tabClass = (active) =>
        `flex items-center gap-2 px-3 py-2 rounded-md transition w-full
        ${active
            ? 'bg-red-100 text-red-600 font-semibold'
            : 'hover:bg-gray-100 text-gray-700'
        }`;

    return (
        <aside className="w-full lg:w-72 bg-white rounded-xl p-4 sm:p-5 shadow-[0_4px_12px_rgba(0,0,0,0.15)]">
            {/* Avatar + Info */}
            <div className="flex items-center gap-4 mb-4 sm:mb-6">
                <img
                    src={info}
                    alt="Avatar"
                    className="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover border border-gray-300"
                />
                <div className="flex-1">
                    <h3
                        className="font-semibold text-gray-800 text-sm sm:text-base max-w-[180px] truncate"
                        title={user?.name || "Người dùng"}
                    >
                        {(user?.name?.length > 12
                            ? user.name.substring(0, 12) + "..."
                            : user?.name) || "Người dùng"}
                    </h3>

                    <p className="text-xs text-gray-500">
                        {user?.phone || 'Chưa có số điện thoại'}
                    </p>
                </div>
            </div>

            {/* Menu */}
            <ul
                className="
                    grid grid-cols-2 lg:grid-cols-1
                    gap-2
                    text-xs sm:text-sm font-medium
                "
            >
                <li>
                    <button
                        onClick={() => setTab("info")}
                        className={`${tabClass(tab === "info")} flex items-center gap-2 justify-center lg:justify-start`}
                    >
                        <FaUser /> Thông tin
                    </button>
                </li>

                <li>
                    <button
                        onClick={() => setTab("history")}
                        className={`${tabClass(tab === "history")} flex items-center gap-2 justify-center lg:justify-start`}
                    >
                        <FaHistory /> Lịch sử đơn hàng
                    </button>
                </li>

                <li>
                    <button
                        onClick={() => setTab("address")}
                        className={`${tabClass(tab === "address")} flex items-center gap-2 justify-center lg:justify-start`}
                    >
                        <FaMapMarkedAlt /> Sổ địa chỉ
                    </button>
                </li>

                <li>
                    <button
                        onClick={() => setTab("lovely")}
                        className={`${tabClass(tab === "lovely")} flex items-center gap-2 justify-center lg:justify-start`}
                    >
                        <FaHeart /> Yêu thích
                    </button>
                </li>

                <li>
                    <button
                        onClick={() => setTab("voucher")}
                        className={`${tabClass(tab === "voucher")} flex items-center gap-2 justify-center lg:justify-start`}
                    >
                        <FaTicketAlt /> Voucher
                    </button>
                </li>

                <li className="col-span-2 lg:col-span-1">
                    <button
                        onClick={handleLogout}
                        className="flex items-center gap-2 px-3 py-2 rounded-md transition w-full text-red-600 hover:bg-red-50 font-medium justify-center lg:justify-start"
                    >
                        <FaSignOutAlt /> Đăng xuất
                    </button>
                </li>
            </ul>


        </aside>
    );
};

export default SidebarMenu;
