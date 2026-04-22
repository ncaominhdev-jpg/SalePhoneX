import React, { useEffect, useState } from "react";
import {
  FaCheckCircle,
  FaTimesCircle,
  FaShoppingBag,
  FaHome,
  FaEnvelope,
} from "react-icons/fa";
import { useNavigate, useLocation } from "react-router-dom";
import axios from "axios";
import constants from "../../constants/constants";
import { useCart } from "../../contexts/CartContext";
import { toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

// ⬇️ import modal
import OrderDetail from "../OrderDetail/OrderDetail";

const ThankYouPage = () => {
  const navigate = useNavigate();
  const { fetchCart } = useCart();
  const { search } = useLocation();
  const params = new URLSearchParams(search);
  const status = params.get("status");
  const orderId = params.get("order_id");

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(false);
  const [invoiceSent, setInvoiceSent] = useState(false);
  const [loaded, setLoaded] = useState(false);

  // ⬇️ state mở modal
  const [showOrderModal, setShowOrderModal] = useState(false);

  // Khi load lại trang, check localStorage xem đã gửi invoice chưa
  useEffect(() => {
    if (orderId) {
      const sentFlag = localStorage.getItem(`invoice_sent_${orderId}`);
      if (sentFlag === "true") {
        setInvoiceSent(true);
      }
    }
  }, [orderId]);

  useEffect(() => {
    if (status === "success") {
      localStorage.removeItem("checkoutItems");
    }
  }, [status]);

  useEffect(() => {
    if (status === "success" && !loaded) {
      fetchCart();

      const token = localStorage.getItem("access_token");
      if (orderId && token) {
        axios
          .get(`${constants.BASE_URL}/orders/${orderId}`, {
            headers: { Authorization: `Bearer ${token}` },
          })
          .then((res) => setOrder(res.data))
          .catch((err) => console.error("Không lấy được đơn hàng:", err));
      }

      setLoaded(true);
    }
  }, [status, orderId, fetchCart, loaded]);

  // Gửi hóa đơn qua email
  const handleSendInvoice = async () => {
    if (invoiceSent) return;
    setLoading(true);
    try {
      const token = localStorage.getItem("access_token");
      const res = await axios.post(
        `${constants.BASE_URL}/orders/${orderId}/send-invoice`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      toast.success(res.data.message || "✅ Hóa đơn đã được gửi qua email!");
      setInvoiceSent(true);
      localStorage.setItem(`invoice_sent_${orderId}`, "true");
    } catch (err) {
      toast.error(err.response?.data?.error || "❌ Không thể gửi hóa đơn");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[70vh] flex flex-col items-center justify-center bg-gradient-to-b from-gray-50 to-white px-6 py-12">
      {status === "success" ? (
        <>
          <FaCheckCircle className="text-green-500 text-[100px] animate-bounce" />
          <h1 className="text-3xl sm:text-4xl font-bold text-gray-800 mt-6">
            Đặt hàng thành công!
          </h1>
          <p className="text-gray-600 mt-3 text-center max-w-md">
            Cảm ơn bạn đã tin tưởng và mua sắm tại cửa hàng của chúng tôi. Đơn
            hàng của bạn đang được xử lý và sẽ sớm được giao đến bạn.
          </p>

          {!order ? (
            // Hiệu ứng load khi chưa có order
            <div className="mt-6 bg-white border border-gray-200 rounded-xl shadow-md p-5 w-full max-w-md text-left animate-pulse">
              <div className="h-6 bg-gray-300 rounded w-1/2 mb-4"></div>
              <div className="h-4 bg-gray-200 rounded w-2/3 mb-2"></div>
              <div className="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
              <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div className="h-6 bg-gray-300 rounded w-1/4"></div>
            </div>
          ) : (
            <div className="mt-6 bg-white border border-gray-200 rounded-xl shadow-md p-5 w-full max-w-md text-left">
              <h2 className="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-700 mb-3">
                Thông tin đơn hàng #{order.id}
              </h2>
              <p className="text-sm md:text-base text-gray-600">
                <b>Người nhận:</b> {order.recipient_name}
              </p>
              <p className="text-sm md:text-base text-gray-600">
                <b>Điện thoại:</b> {order.phone}
              </p>
              <p className="text-sm md:text-base text-gray-600">
                <b>Địa chỉ:</b> {order.address}
              </p>
              <p className="text-sm md:text-base text-gray-600">
                <b>Tổng tiền:</b>{" "}
                <span className="text-red-600 font-bold">
                  {order.total_amount !== undefined &&
                  order.total_amount !== null
                    ? Math.round(order.total_amount).toLocaleString("vi-VN")
                    : 0}{" "}
                  đ
                </span>
              </p>
            </div>
          )}

          <div className="flex flex-col sm:flex-row gap-4 mt-8">
            {/* ⬇️ mở modal, KHÔNG điều hướng */}
            <button
              type="button"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setShowOrderModal(true);
              }}
              className="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg shadow-lg font-semibold transition"
              disabled={!orderId}
            >
              <FaShoppingBag /> Xem đơn hàng
            </button>

            <button
              onClick={() => navigate("/")}
              className="flex items-center justify-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg shadow font-semibold transition"
            >
              <FaHome /> Tiếp tục mua sắm
            </button>

            {/* ✅ Chỉ hiển thị nếu không phải COD */}
            {order?.payment_method !== "cod" && (
              <button
                onClick={handleSendInvoice}
                disabled={loading || invoiceSent}
                className={`flex items-center justify-center gap-2 px-6 py-3 rounded-lg shadow font-semibold transition 
                  ${
                    invoiceSent
                      ? "bg-gray-400 text-white cursor-not-allowed"
                      : "bg-red-500 hover:bg-red-600 text-white"
                  }`}
              >
                <FaEnvelope />{" "}
                {invoiceSent
                  ? "Đã gửi hóa đơn"
                  : loading
                  ? "Đang gửi..."
                  : "Gửi hóa đơn qua Email"}
              </button>
            )}
          </div>

          {/* ⬇️ Render modal chi tiết đơn hàng */}
          <OrderDetail
            open={showOrderModal}
            orderId={orderId}
            onClose={() => setShowOrderModal(false)}
          />
        </>
      ) : (
        <>
          <FaTimesCircle className="text-red-500 text-[100px] animate-pulse" />
          <h1 className="text-3xl sm:text-4xl font-bold text-gray-800 mt-6">
            Thanh toán thất bại!
          </h1>
          <p className="text-gray-600 mt-3 text-center max-w-md">
            Rất tiếc, giao dịch của bạn không thành công. Vui lòng thử lại hoặc
            chọn phương thức thanh toán khác.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 mt-8">
            <button
              onClick={() => navigate("/cart")}
              className="flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg shadow-lg font-semibold transition"
            >
              Thử lại
            </button>
            <button
              onClick={() => navigate("/")}
              className="flex items-center justify-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg shadow font-semibold transition"
            >
              <FaHome /> Về trang chủ
            </button>
          </div>
        </>
      )}
    </div>
  );
};

export default ThankYouPage;
