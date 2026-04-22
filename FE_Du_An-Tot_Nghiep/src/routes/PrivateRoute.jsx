// routes/PrivateRoute.jsx
import React from "react";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";

/* ==== Helpers ==== */
const getStorageJson = (k) => {
    try {
        return JSON.parse(localStorage.getItem(k) || sessionStorage.getItem(k) || "null");
    } catch {
        return null;
    }
};
const readToken = () =>
    localStorage.getItem("access_token") || sessionStorage.getItem("access_token");

const norm = (r) => String(r || "").trim().toLowerCase();
const rolesOf = (user) =>
    Array.isArray(user?.roles) ? user.roles.map(norm) : [norm(user?.role)];
const hasAnyRole = (user, roles = []) => {
    const need = new Set((Array.isArray(roles) ? roles : [roles]).map(norm));
    return rolesOf(user).some((r) => need.has(r));
};

/* ==== Các role nội bộ bị chặn trên giao diện client ==== */
const INTERNAL_ROLES = ["admin", "manager", "staff"];

export default function PrivateRoute({ children, allowedRoles, publicRoute = false }) {
    const token = readToken();
    const user = getStorageJson("user");

    // --- Public route ---
    if (publicRoute) {
        // Chưa đăng nhập -> cho vào bình thường
        if (!token) return children;

        // Đã đăng nhập nhưng là role nội bộ -> chỉ toast, không render
        if (user && hasAnyRole(user, INTERNAL_ROLES)) {
            toast.error("Tài khoản nội bộ không thể truy cập chức năng trên giao diện khách hàng");
            return null; // không chuyển trang
        }

        // User thường đã login -> cho vào
        return children;
    }

    // --- Protected route (cần đăng nhập) ---
    if (!token || !user) return <Navigate to="/login" replace />;

    // Nếu route yêu cầu role cụ thể
    if (allowedRoles && !hasAnyRole(user, allowedRoles)) {
        toast.error("Bạn không có quyền truy cập vào chức năng này");
        return null; // không chuyển trang
    }

    return children;
}
