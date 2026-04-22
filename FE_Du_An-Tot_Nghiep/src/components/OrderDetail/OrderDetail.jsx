// src/components/modals/OrderDetail.jsx
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { createPortal } from "react-dom";
import axios from "axios";
import constants from "../../constants/constants";

const OrderDetail = ({ open, orderId, onClose }) => {
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const fetchOrder = useCallback(async () => {
    if (!open || !orderId) return;
    setLoading(true);
    setError("");
    try {
      const res = await axios.get(`${constants.BASE_URL}/orders/${orderId}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("access_token")}`,
        },
      });
      setOrder(res.data);
    } catch (e) {
      console.error(e);
      setError("Không thể tải đơn hàng");
    } finally {
      setLoading(false);
    }
  }, [open, orderId]);

  useEffect(() => {
    fetchOrder();
  }, [fetchOrder]);

  // ESC để đóng
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => e.key === "Escape" && onClose?.();
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, onClose]);

  // Khóa scroll body khi mở modal
  useEffect(() => {
    if (!open) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = prev;
    };
  }, [open]);

  // Helper: chuẩn hóa URL ảnh từ variant -> product
  const resolveImage = (item) => {
    const pv = item?.product_variant || {};
    const p = pv?.product || {};

    const candidates = [
      pv.img,
      pv.image,
      pv.thumbnail,
      pv?.images?.[0]?.url,
      pv?.medias?.[0]?.url,
      p.image,
      p.thumbnail,
      p?.images?.[0]?.url,
      p?.medias?.[0]?.url,
    ].filter(Boolean);

    const raw = candidates[0];
    if (!raw) return null;
    if (/^https?:\/\//i.test(raw)) return raw;
    return `${constants.BASE_DOMAIN}/storage/${String(raw).replace(
      /^storage\//,
      ""
    )}`;
  };

  const calculatedTotal = useMemo(() => {
    if (!order) return 0;
    if (order.total && order.total > 0) return order.total;
    return (
      order.order_items?.reduce(
        (sum, item) =>
          sum +
          (Number(item.price) ||
            Number(item.product_variant?.price) ||
            Number(item.product_variant?.product?.price) ||
            0) *
            (item.quantity || 1),
        0
      ) || 0
    );
  }, [order]);

  const statusColor =
    order?.status === "completed" || order?.status === "confirmed"
      ? "bg-green-100 text-green-700"
      : order?.status === "pending"
      ? "bg-yellow-100 text-yellow-700"
      : "bg-red-100 text-red-700";

  if (!open) return null;

  return createPortal(
    <div
      className="fixed inset-0 z-[1000] flex items-center justify-center"
      role="dialog"
      aria-modal="true"
      aria-labelledby="order-detail-title"
    >
      {/* backdrop */}
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      {/* panel */}
      <div
        className="relative z-[1001] w-full max-w-4xl mx-4 bg-white shadow-xl p-6 sm:p-8 rounded-2xl border border-gray-200"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between gap-4 mb-6 border-b pb-4">
          <h2
            id="order-detail-title"
            className="text-2xl font-bold text-gray-800"
          >
            Chi tiết đơn hàng{" "}
            <span className="text-red-600">{order ? `#${order.id}` : ""}</span>
          </h2>
          <button
            onClick={onClose}
            className="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium"
          >
            Đóng
          </button>
        </div>

        {loading ? (
          <p className="text-center py-8 text-gray-600 animate-pulse">
            Đang tải đơn hàng...
          </p>
        ) : error ? (
          <p className="text-center py-8 text-red-500 font-semibold">{error}</p>
        ) : !order ? (
          <p className="text-center py-8 text-gray-500">Không có dữ liệu.</p>
        ) : (
          <>
            {/* Thông tin đơn hàng */}
            <div className="grid md:grid-cols-2 gap-6 text-gray-700">
              <p>
                <span className="font-semibold">Khách hàng:</span>{" "}
                {order.user?.name || order.recipient_name}
              </p>
              <p>
                <span className="font-semibold">Tổng tiền:</span>{" "}
                <span className="text-red-600 font-bold">
                  {calculatedTotal.toLocaleString("vi-VN")} VND
                </span>
              </p>
              <p>
                <span className="font-semibold">Phương thức thanh toán:</span>{" "}
                {order.payment_method?.toUpperCase() || "—"}
              </p>
              <p>
                <span className="font-semibold">Trạng thái:</span>{" "}
                <span
                  className={`px-3 py-1 rounded-full text-sm font-medium ${statusColor}`}
                >
                  {order.status}
                </span>
              </p>
            </div>

            {/* Danh sách sản phẩm */}
            <h3 className="text-lg font-semibold text-gray-800 mt-10 mb-4">
              Danh sách sản phẩm
            </h3>
            <div className="overflow-x-auto border rounded-lg">
              <table className="min-w-full text-sm">
                <thead className="bg-gray-100">
                  <tr>
                    <th className="px-4 py-3 text-left font-semibold">Ảnh</th>
                    <th className="px-4 py-3 text-left font-semibold">
                      Sản phẩm
                    </th>
                    <th className="px-4 py-3 text-center font-semibold">
                      Số lượng
                    </th>
                    <th className="px-4 py-3 text-right font-semibold">Giá</th>
                  </tr>
                </thead>
                <tbody>
                  {order.order_items?.length ? (
                    order.order_items.map((item) => {
                      const price =
                        Number(item.price) ||
                        Number(item.product_variant?.price) ||
                        Number(item.product_variant?.product?.price) ||
                        0;

                      const imgUrl = resolveImage(item);
                      const name =
                        item.product_variant?.product?.name || "Không xác định";

                      return (
                        <tr key={item.id} className="border-t hover:bg-gray-50">
                          <td className="px-4 py-3">
                            {imgUrl ? (
                              <img
                                src={imgUrl}
                                alt={name}
                                className="w-12 h-12 rounded object-cover border"
                                loading="lazy"
                                width={48}
                                height={48}
                              />
                            ) : (
                              <div className="w-12 h-12 rounded bg-gray-200 flex items-center justify-center text-xs text-gray-500">
                                N/A
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-3 text-gray-800">{name}</td>
                          <td className="px-4 py-3 text-center">
                            {item.quantity}
                          </td>
                          <td className="px-4 py-3 text-right text-red-600 font-medium">
                            {price.toLocaleString("vi-VN")} VND
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td
                        colSpan={4}
                        className="px-4 py-6 text-center text-gray-500"
                      >
                        Không có sản phẩm trong đơn hàng
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </>
        )}
      </div>
    </div>,
    document.body
  );
};

export default OrderDetail;
