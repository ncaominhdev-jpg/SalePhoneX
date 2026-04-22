// hooks/useWishlist.js
import { useState, useEffect, useMemo } from "react";
import axios from "axios";
import constants from "../constants/constants";
import { toast } from "react-toastify";

const getJson = (k) => {
  try {
    const v = localStorage.getItem(k) ?? sessionStorage.getItem(k);
    return v ? JSON.parse(v) : null;
  } catch {
    return null;
  }
};

const norm = (r) => String(r || "").trim().toLowerCase();
const BLOCKED_ROLES = new Set(["admin", "manager", "staff"]);

export const useWishlist = () => {
  const [wishlist, setWishlist] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const token =
    localStorage.getItem("access_token") || sessionStorage.getItem("access_token");
  const user = getJson("user");

  const isLoggedIn = !!token && !!user?.id;

  // Hỗ trợ cả user.role (string) và user.roles (array)
  const userRoles = Array.isArray(user?.roles) ? user.roles.map(norm) : [norm(user?.role)];
  const isBlocked = userRoles.some((r) => BLOCKED_ROLES.has(r));

  // Lấy wishlist từ API (role bị chặn hoặc chưa login -> không fetch)
  const fetchWishlist = async () => {
    if (!isLoggedIn || isBlocked) {
      setLoading(false);
      setWishlist([]);
      return;
    }
    setLoading(true);
    try {
      const res = await axios.get(`${constants.BASE_URL}/wishlists`, {
        params: { user_id: user.id },
        headers: { Authorization: `Bearer ${token}` },
      });
      setWishlist(res.data || []);
      setError(null);
    } catch (err) {
      console.error("Lỗi lấy wishlist:", err);
      setError("Không thể tải danh sách yêu thích");
      toast.error("Không thể tải danh sách yêu thích");
    } finally {
      setLoading(false);
    }
  };

  // Thêm vào wishlist (blocked -> toast & chặn)
  const addToWishlist = async (productId) => {
    if (!isLoggedIn) {
      toast.error("Bạn cần đăng nhập để thêm vào yêu thích");
      return;
    }
    if (isBlocked) {
      toast.error("Tài khoản nội bộ không thể thêm vào danh sách yêu thích");
      return;
    }

    try {
      const res = await axios.post(
        `${constants.BASE_URL}/wishlists`,
        { user_id: user.id, product_id: productId },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      setWishlist((prev) => {
        const item = res?.data?.data;
        if (!item) return prev;
        const exists = prev.some((w) => String(w.product_id) === String(productId));
        return exists ? prev : [...prev, item];
      });
      toast.success("Đã thêm vào danh sách yêu thích");
    } catch (err) {
      console.error("Lỗi thêm wishlist:", err);
      toast.error("Không thể thêm vào danh sách yêu thích");
    }
  };

  // Xoá khỏi wishlist (blocked -> toast & chặn)
  const removeFromWishlist = async (productId) => {
    if (!isLoggedIn) {
      toast.error("Bạn cần đăng nhập để xoá khỏi yêu thích");
      return;
    }
    if (isBlocked) {
      toast.error("Tài khoản nội bộ không thể xoá khỏi danh sách yêu thích");
      return;
    }

    try {
      await axios.delete(`${constants.BASE_URL}/wishlists/${productId}`, {
        params: { user_id: user.id },
        headers: { Authorization: `Bearer ${token}` },
      });
      setWishlist((prev) => prev.filter((w) => String(w.product_id) !== String(productId)));
      toast.info("Đã xoá khỏi danh sách yêu thích");
    } catch (err) {
      console.error("Lỗi xóa wishlist:", err);
      toast.error("Không thể xoá sản phẩm khỏi yêu thích");
    }
  };

  useEffect(() => {
    fetchWishlist();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // flag để UI có thể disable icon tim
  const blockedByRole = useMemo(() => isBlocked, [isBlocked]);

  return {
    wishlist,
    loading,
    error,
    blockedByRole,
    fetchWishlist,
    addToWishlist,
    removeFromWishlist,
  };
};
