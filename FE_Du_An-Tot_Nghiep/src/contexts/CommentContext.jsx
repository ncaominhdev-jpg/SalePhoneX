import React, { createContext, useContext, useState, useEffect } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import constants from "../constants/constants";

import { toast } from "react-toastify"; // nhớ import
const CommentContext = createContext();
export const CommentProvider = ({ children }) => {
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(false);

  const user = JSON.parse(localStorage.getItem("user")) || null;
  const token = localStorage.getItem("access_token");

  // CommentContext.jsx
  const fetchComments = async (productId) => {
    if (!productId) return;

    try {
      setLoading(true);
      const res = await axios.get(`${constants.BASE_URL}/comments`, {
        params: { product_id: productId },
      });
      setComments(res.data); // đã có replies trong từng comment
    } catch (err) {
      console.error("Lỗi khi lấy comments:", err.response?.data || err.message);
    } finally {
      setLoading(false);
    }
  };

  // Gửi bình luận
  const addComment = async (productId, content, parentId = null) => {
    if (!user) {
      Swal.fire("Thông báo", "Bạn cần đăng nhập để bình luận", "warning");
      return false;
    }
    if (!content.trim()) return false;

    try {
      setLoading(true);
      const res = await axios.post(
        `${constants.BASE_URL}/comments`,
        {
          product_id: productId,
          user_id: user.id,
          parent_id: parentId,
          content,
          status: 1, // tự động duyệt (hoặc để 0 nếu chờ duyệt)
        },
        {
          headers: { Authorization: `Bearer ${token}` },
        }
      );
      // Sau khi thêm mới, reload lại list
      await fetchComments(productId);

      toast.success("Đã gửi bình luận thành công!");
      return res.data.comment;
    } catch (err) {
      console.error("Lỗi khi thêm comment:", err.response?.data || err.message);
      toast.error(err.response?.data?.message || "Không gửi được bình luận");
      return false;
    } finally {
      setLoading(false);
    }
  };

  return (
    <CommentContext.Provider
      value={{ comments, loading, fetchComments, addComment }}
    >
      {children}
    </CommentContext.Provider>
  );
};

// Hook để dùng nhanh
export const useComments = () => useContext(CommentContext);
