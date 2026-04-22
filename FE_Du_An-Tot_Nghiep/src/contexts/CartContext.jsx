// contexts/CartContext.jsx
import { createContext, useContext, useState, useEffect } from "react";
import constants from "../constants/constants";
import axios from "axios";
import { toast } from "react-toastify";

const CartContext = createContext();

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchCart = async () => {
    const userId = JSON.parse(localStorage.getItem("user"))?.id;
    if (!userId) return;

    setLoading(true);
    try {
      const res = await fetch(`${constants.BASE_URL}/carts/user/${userId}`);
      const data = await res.json();
      setCartItems(data || []);
    } catch (err) {
      console.error("Lỗi khi fetch giỏ hàng:", err);
    } finally {
      setLoading(false);
    }
  };

  const updateCartItem = async (id, newQuantity) => {
    try {
      const token = localStorage.getItem("access_token");
      const res = await axios.put(
        `${constants.BASE_URL}/carts/${id}`,
        { quantity: newQuantity },
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return res.data;
    } catch (err) {
      toast.error(err.response?.data?.message || "Lỗi khi cập nhật giỏ hàng");
      throw err;
    }
  };


  const addCartItem = async (productVariantId, quantity) => {
    try {
      console.log(
        "Thêm biến thể vào giỏ hàng, productVariantId:",
        productVariantId
      );
      console.log("Giỏ hàng hiện tại:", cartItems);

      const existingItem = cartItems.find(
        (item) => item.product_variant_id === productVariantId
      );
      console.log("Sản phẩm đã tồn tại trong giỏ:", existingItem);

      if (existingItem) {
        // Nếu đã có → update số lượng
        const newQuantity = existingItem.quantity + quantity;
        await updateCartItem(existingItem.id, newQuantity);
      } else {
        const userId = JSON.parse(localStorage.getItem("user"))?.id;
        if (!userId) {
          console.error("Người dùng chưa đăng nhập");
          return;
        }
        const res = await axios.post(`${constants.BASE_URL}/carts`, {
          user_id: userId,
          product_variant_id: productVariantId,
          quantity,
        });
        setCartItems((prev) => [...prev, res.data]);
      }
    } catch (error) {
      console.error(
        "Lỗi thêm sản phẩm vào giỏ hàng:",
        error.response?.data || error.message
      );
    }
  };

  const removeCartItem = async (id) => {
    try {
      await axios.delete(`${constants.BASE_URL}/carts/${id}`);
      setCartItems((prev) => prev.filter((item) => item.id !== id));
    } catch (error) {
      console.error("Lỗi xoá sản phẩm:", error.response?.data || error.message);
    }
  };

  useEffect(() => {
    fetchCart();
  }, []);

  return (
    <CartContext.Provider
      value={{
        cartItems,
        setCartItems,
        fetchCart,
        updateCartItem,
        addCartItem,
        removeCartItem,
        loading,
      }}
    >
      {children}
    </CartContext.Provider>
  );
};

export const useCart = () => useContext(CartContext);
