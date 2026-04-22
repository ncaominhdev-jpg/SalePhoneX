import React, { useState } from "react";
import image from "../../../assets/SmartPhone/iphone-16-pro-max.webp";
import axios from "axios";
import Swal from "sweetalert2";
import { FaCheck } from "react-icons/fa";
import constants from "../../../constants/constants";
import { useNavigate } from "react-router-dom";
import { useCart } from "../../../contexts/CartContext";
import VoucherModal from "../../../components/VoucherModal/VoucherModal";
import SummaryItem from "../../../components/SummaryItem/SummaryItem";

const OrderSummary = ({
  paymentMethod,
  checkoutItems,
  userInfo,
  voucherCode,
  vouchers = [],
  onApplyVoucher,
  onPaymentSuccess,
  discount = 0,
  finalTotal = 0,
}) => {
  const navigate = useNavigate();
  const { removeCartItem, fetchCart } = useCart();
  const user = JSON.parse(localStorage.getItem("user")) || {};
  const selectedCity = localStorage.getItem("selectedCity") || "";
  const shippingInfo = JSON.parse(localStorage.getItem("shippingInfo")) || {};
  const [isVoucherModalOpen, setIsVoucherModalOpen] = useState(false);

  const subtotal = checkoutItems.reduce((acc, p) => acc + p.price * p.quantity, 0);
  const payableTotal = finalTotal || subtotal - discount;


  // --- Helper: chuẩn hóa city ngắn gọn (để khớp pickBranch của BE) ---
  const normalizeCity = (city) =>
    (city || "").trim();

  const handleSubmitOrder = async () => {
    if (!paymentMethod) {
      Swal.fire("Thông báo", "Vui lòng chọn phương thức thanh toán", "warning");
      return;
    }

    const cartIds = checkoutItems.map((item) => item.id);

    const confirmResult = await Swal.fire({
      title: "Xác nhận đặt hàng",
      html: `
        <div class="text-left text-sm leading-relaxed">
          <p><b>Người nhận:</b> ${shippingInfo.name || user.name || "Chưa có"}</p>
          <p><b>Số điện thoại:</b> ${shippingInfo.phone || user.phone || "Chưa có"}</p>
          <p><b>Địa chỉ:</b> ${shippingInfo.method === "store"
          ? `Nhận hàng tại cửa hàng: ${shippingInfo.storeAddress || "Chưa có"}`
          : `${shippingInfo.street || ""}, ${shippingInfo.wardName || ""}, ${selectedCity || shippingInfo.provinceName || "Chưa có"
          }`
        }</p>
          <p><b>Phương thức thanh toán:</b> ${paymentMethod === "cod" ? "Thanh toán khi nhận hàng (COD)" : paymentMethod === "vnpay" ? "VNPay" : "MoMo"
        }</p>
          <p><b>Tổng tiền:</b>
            <span class="text-red-600 font-bold">
              ${payableTotal.toLocaleString()}đ
            </span>
          </p>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: "Xác nhận",
      cancelButtonText: "Huỷ",
      reverseButtons: true,
      customClass: {
        actions: "flex justify-center space-x-4",
        confirmButton:
          "bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg",
        cancelButton:
          "bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg",
      },
      buttonsStyling: false,
    });

    if (!confirmResult.isConfirmed) return;

    try {
      const user = JSON.parse(localStorage.getItem("user"));
      if (!user) {
        Swal.fire("Lỗi", "Bạn cần đăng nhập để thanh toán", "error").then(() => {
          navigate("/login");
        });
        return;
      }

      const token = localStorage.getItem("access_token");

      // Giới hạn MoMo 50 triệu
      if (paymentMethod === "momo" && payableTotal > 50_000_000) {
        Swal.fire({
          icon: "error",
          title: "Thông báo",
          html: `<p class="text-sm leading-relaxed text-gray-700">
             Đơn hàng MoMo không được vượt quá <b>50 triệu đồng</b>.
           </p>`,
          confirmButtonText: "Đã hiểu",
          buttonsStyling: false,
          customClass: {
            popup: "rounded-xl",
            title: "text-lg font-semibold",
            confirmButton:
              "bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg",
          },
          backdrop: "rgba(0,0,0,0.4)",
        });
        return;
      }

      // Xác định delivery_type (store|home) theo yêu cầu OrderController
      const deliveryType = shippingInfo.method === "store" ? "store" : "home";

      // Chỉ truyền branch_id khi nhận tại cửa hàng; giao tận nơi => để null để BE auto pick
      let branchId = null;
      if (deliveryType === "store") {
        branchId = Number(shippingInfo.storeId) || null;
      }

      // Chuẩn hóa địa chỉ & city
      const addressText =
        deliveryType === "store"
          ? `Nhận tại cửa hàng: ${shippingInfo.storeAddress || ""}`.trim()
          : `${shippingInfo.street || ""}, ${shippingInfo.wardName || ""}, ${selectedCity || shippingInfo.provinceName || ""
            }`.replace(/\s*,\s*,/g, ", ").trim();

      const shippingCity = normalizeCity(selectedCity || shippingInfo.provinceName || "");

      // Payload đúng với OrderController@store
      const payload = {
        user_id: user.id,
        branch_id: branchId, // null nếu home
        delivery_type: deliveryType, // 'store' | 'home'
        shipping_city: shippingCity || null, // để BE pick branch khi home
        total_amount: payableTotal,
        payment_method: paymentMethod,
        status: "pending",
        recipient_name: shippingInfo.name || user.name || "",
        phone: shippingInfo.phone || user.phone || "",
        address: addressText,
        note: shippingInfo.note || "",
        voucher_code: voucherCode || null,
        order_details: checkoutItems.map((item) => ({
          product_variant_id: item.variantId,
          quantity: item.quantity,
        })),
      };

      // Tạo đơn hàng
      const orderRes = await axios.post(`${constants.BASE_URL}/orders`, payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const order = orderRes.data;

      // Điều hướng thanh toán theo phương thức
      if (paymentMethod === "vnpay") {
        const res = await axios.post(
          `${constants.BASE_URL}/payments/vnpay/create`,
          {
            user_id: user.id,
            order_id: order.id,
            amount: payableTotal,
            voucher_code: voucherCode || null,
            language: "vn",
            cart_ids: cartIds, // để BE xoá giỏ sau khi thanh toán thành công
          },
          { headers: { Authorization: `Bearer ${token}` } }
        );
        window.location.href = res.data.payment_url;
      } else if (paymentMethod === "momo") {
        const res = await axios.post(
          `${constants.BASE_URL}/momo-payment`,
          {
            user_id: user.id,
            order_id: order.id,
            amount: payableTotal,
            voucher_code: voucherCode || null,
            orderInfo: "Thanh toán qua MoMo",
            cart_ids: cartIds,
          },
          { headers: { Authorization: `Bearer ${token}` } }
        );
        window.location.href = res.data.payUrl;
      } else if (paymentMethod === "cod") {
        // Tạo payment bản ghi COD (chưa thanh toán)
        await axios.post(
          `${constants.BASE_URL}/payments`,
          {
            order_id: order.id,
            user_id: user.id,
            method: "cod",
            amount: payableTotal,
            paid_at: null,
          },
          { headers: { Authorization: `Bearer ${token}` } }
        );

        Swal.fire({
          icon: "success",
          title: "Đặt hàng thành công!",
          showConfirmButton: false,
          timer: 2000,
        });

        if (onPaymentSuccess && voucherCode) {
          onPaymentSuccess(voucherCode);
        }

        // Xóa item giỏ hàng local và refetch
        for (const item of checkoutItems) {
          await removeCartItem(item.id);
        }
        await fetchCart();

        localStorage.removeItem("checkoutItems");
        localStorage.removeItem("shippingInfo");

        navigate(`/cam-on-quy-khach?status=success&order_id=${order.id}`);
      }
    } catch (err) {
      const status = err?.response?.status;
      const data = err?.response?.data;

      const baseSwal = (title, message) =>
        Swal.fire({
          icon: "warning",
          title,
          html: `<p class="text-sm leading-relaxed text-gray-700">${message}</p>`,
          confirmButtonText: "Đã hiểu",
          buttonsStyling: false,
          customClass: {
            popup: "rounded-xl",
            title: "text-lg font-semibold",
            confirmButton:
              "bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg",
          },
          backdrop: "rgba(0,0,0,0.4)",
        });

      // 409 / 422: lỗi nghiệp vụ / validate
      if (status === 409 || status === 422) {
        baseSwal(
          "Không thể tạo đơn",
          data?.message ||
          "Hiện chưa có chi nhánh nào đủ hàng hoặc dữ liệu chưa hợp lệ."
        );
        return;
      }

      // 500: lỗi hệ thống có message từ BE
      if (status === 500 && data?.error) {
        baseSwal("Không thể tạo đơn", data.error);
        return;
      }

      console.error("Lỗi khi tạo đơn hàng:", data || err);
      baseSwal("Lỗi", "Không thể đặt hàng. Vui lòng thử lại sau.");
    }

  };

  return (
    <div className="bg-white rounded-xl shadow-lg p-5 sm:p-6 h-fit lg:col-span-1 border border-neutral-200">
      {/* Tiêu đề */}
      <h1 className="text-lg sm:text-xl font-bold border-b pb-4 mb-4">Đơn hàng</h1>

      {/* Danh sách sản phẩm */}
      <div className="max-h-60 overflow-y-auto pr-2 space-y-4 custom-scrollbar">
        {checkoutItems.map((p) => (
          <SummaryItem key={p.id} item={p} />
        ))}
      </div>

      {/* Thông tin tổng kết */}
      <div className="border-t pt-4 mt-4 text-sm md:text-base text-gray-700 space-y-3">
        <div className="flex justify-between">
          <span>Tạm tính</span>
          <span>{subtotal.toLocaleString()}</span>
        </div>

        <div className="flex justify-between items-center">
          <span className="font-medium text-gray-800">
            Voucher
            {voucherCode && (
              <span className="ml-2 text-green-600 font-semibold">{voucherCode}</span>
            )}
          </span>

          {voucherCode ? (
            <button
              onClick={() => setIsVoucherModalOpen(true)}
              className="flex items-center gap-2 px-4 py-1.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition"
            >
              <FaCheck className="text-white" />
              Đã áp dụng
            </button>
          ) : (
            <button
              onClick={() => setIsVoucherModalOpen(true)}
              className="px-4 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
            >
              Nhập mã
            </button>
          )}
        </div>

        {discount > 0 && (
          <div className="flex justify-between text-green-600 font-semibold">
            <span>Giảm giá</span>
            <span>-{Number(discount).toLocaleString("vi-VN")}</span>
          </div>
        )}

        <div className="flex justify-between font-bold border-t pt-3 text-base sm:text-lg">
          <span>Tổng cộng</span>
          <span className="text-red-600">{payableTotal.toLocaleString()}</span>
        </div>
      </div>

      {/* Modal chọn voucher */}
      <VoucherModal
        isOpen={isVoucherModalOpen}
        onClose={() => setIsVoucherModalOpen(false)}
        vouchers={vouchers}
        voucherCode={voucherCode}
        onApplyVoucher={onApplyVoucher}
      />

      {/* Nút thanh toán */}
      <button
        onClick={handleSubmitOrder}
        disabled={!paymentMethod}
        className={`w-full mt-5 py-3 rounded-lg font-medium text-sm sm:text-base transition ${paymentMethod
          ? "bg-red-600 hover:bg-red-700 text-white cursor-pointer"
          : "bg-gray-300 text-gray-500 cursor-not-allowed"
          }`}
      >
        Tiến hành thanh toán
      </button>
    </div>
  );
};

export default OrderSummary;
