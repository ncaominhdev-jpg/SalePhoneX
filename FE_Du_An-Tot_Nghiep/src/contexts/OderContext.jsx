// src/contexts/oderContext.js
import React, {
  createContext,
  useContext,
  useMemo,
  useCallback,
  useEffect,
  useState,
} from "react";
import { toast } from "react-toastify";
import constants from "../constants/constants";
const OrderContext = createContext(null);

export function OrderProvider({ children }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [openDetail, setOpenDetail] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [detailError, setDetailError] = useState("");

  const token = useMemo(() => localStorage.getItem("access_token"), []);
  const headers = useMemo(
    () => ({
      "Content-Type": "application/json",
      ...(token && { Authorization: `Bearer ${token}` }),
    }),
    [token]
  );

  // Khóa scroll khi mở modal
  useEffect(() => {
    if (!openDetail) return;
    const sbw = window.innerWidth - document.documentElement.clientWidth;
    const prevOverflow = document.body.style.overflow;
    const prevPR = document.body.style.paddingRight;
    document.body.style.overflow = "hidden";
    if (sbw > 0) document.body.style.paddingRight = `${sbw}px`;
    return () => {
      document.body.style.overflow = prevOverflow;
      document.body.style.paddingRight = prevPR;
    };
  }, [openDetail]);

  const fetchOrders = useCallback(
    async (userId) => {
      try {
        setLoading(true);
        setError("");
        const res = await fetch(`${constants.BASE_URL}/orders`, { headers });
        const data = await res.json();
        if (!res.ok) throw new Error(data?.message || "Không thể tải đơn hàng");
        setOrders(userId ? data.filter((o) => o.user_id === userId) : data);
      } catch (e) {
        setError(e.message || "Lỗi không xác định");
      } finally {
        setLoading(false);
      }
    },
    [headers]
  );

  const fetchOrderDetail = useCallback(
    async (orderId) => {
      try {
        setLoadingDetail(true);
        setDetailError("");
        const res = await fetch(`${constants.BASE_URL}/orders/${orderId}`, {
          headers,
        });
        const data = await res.json();
        if (!res.ok)
          throw new Error(data?.message || "Không lấy được chi tiết đơn");
        setSelectedOrder(data);
        setOpenDetail(true);
      } catch (e) {
        setDetailError(e.message || "Lỗi không xác định");
      } finally {
        setLoadingDetail(false);
      }
    },
    [headers]
  );

  const closeDetail = useCallback(() => {
    setOpenDetail(false);
    setSelectedOrder(null);
    setDetailError("");
  }, []);

  // Gọi API huỷ + cập nhật state + toast kết quả (KHÔNG confirm ở đây)
  const cancelOrder = useCallback(
    async (orderId) => {
      const order = orders.find((o) => o.id === orderId) || selectedOrder;
      if (!order) {
        toast.error("❌ Không tìm thấy đơn hàng");
        return false;
      }
      if (order.status !== "pending") {
        toast.error("❌ Chỉ huỷ được đơn đang chờ xác nhận");
        return false;
      }

      try {
        const res = await fetch(`${constants.BASE_URL}/orders/${orderId}`, {
          method: "PUT",
          headers,
          body: JSON.stringify({ status: "cancelled" }),
        });

        let data = {};
        try {
          data = await res.json();
        } catch {}

        if (!res.ok) {
          toast.error(data?.message || "Huỷ đơn thất bại");
          return false;
        }

        // cập nhật state
        setOrders((prev) =>
          prev.map((o) =>
            o.id === orderId ? { ...o, status: "cancelled" } : o
          )
        );
        setSelectedOrder((prev) =>
          prev ? { ...prev, status: "cancelled" } : prev
        );

        toast.success(data?.message || "Huỷ đơn hàng thành công");
        return true;
      } catch (e) {
        toast.error(e.message || "Có lỗi khi huỷ đơn");
        return false;
      }
    },
    [headers, orders, selectedOrder]
  );

  // Hiển thị toast xác nhận, người dùng bấm "Có" mới gọi cancelOrder
  const requestCancel = useCallback(
    (orderId) => {
      toast(
        ({ closeToast }) => (
          <div className="space-y-2">
            <p className="font-medium">Bạn có chắc muốn huỷ đơn #{orderId}?</p>
            <div className="flex gap-2">
              <button
                onClick={async () => {
                  await cancelOrder(orderId);
                  closeToast();
                }}
                className="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600"
              >
               Huỷ đơn hàng
              </button>
              <button
                onClick={closeToast}
                className="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"
              >
                Để sau
              </button>
            </div>
          </div>
        ),
        {
          autoClose: false,
          closeOnClick: false,
          draggable: false,
          style: { background: "#fff" },
        }
      );
    },
    [cancelOrder]
  );

  const value = {
    // state
    orders,
    loading,
    error,
    openDetail,
    selectedOrder,
    loadingDetail,
    detailError,
    // actions
    fetchOrders,
    fetchOrderDetail,
    closeDetail,
    cancelOrder, // nếu bạn vẫn muốn gọi thẳng
    requestCancel, // 👉 dùng cái này để hiển thị confirm trước
    setOrders,
  };

  return (
    <OrderContext.Provider value={value}>{children}</OrderContext.Provider>
  );
}

export function useOrder() {
  const ctx = useContext(OrderContext);
  if (!ctx) throw new Error("useOrder must be used within OrderProvider");
  return ctx;
}
