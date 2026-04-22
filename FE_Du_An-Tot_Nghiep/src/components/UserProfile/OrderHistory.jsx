// src/pages/OrderHistory/OrderHistory.jsx
import React, { useEffect, useMemo, useState } from "react";
import logo from "../../assets/1ce3169b-4ad7-4038-94d9-00ce4bd19d86.png";
import { createPortal } from "react-dom";
import { useOrder } from "../../contexts/oderContext";
import { useNavigate } from "react-router-dom";
import { FaStar } from "react-icons/fa";
import axios from "axios";
import constants from "../../constants/constants";
import Swal from "sweetalert2";

function getAuthToken() {
  try {
    const u1 = JSON.parse(localStorage.getItem("user") || "null");
    if (u1?.remember_token) return u1.remember_token;
    const u2 = JSON.parse(sessionStorage.getItem("user") || "null");
    if (u2?.remember_token) return u2.remember_token;
    return (
      localStorage.getItem("access_token") ||
      sessionStorage.getItem("access_token") ||
      ""
    );
  } catch {
    return "";
  }
}

const OrderHistory = () => {
  const user = useMemo(
    () =>
      JSON.parse(
        localStorage.getItem("user") || sessionStorage.getItem("user")
      ),
    []
  );

  const {
    orders,
    loading,
    openDetail,
    selectedOrder,
    loadingDetail,
    detailError,
    fetchOrders,
    fetchOrderDetail,
    closeDetail,
    requestCancel,
  } = useOrder();

  const [statusFilter, setStatusFilter] = useState("all");
  const [currentPage, setCurrentPage] = useState(1);
  const perPage = 2;

  const statusMap = {
    all: "Tất cả",
    pending: "Chờ xác nhận",
    confirmed: "Đã xác nhận",
    shipped: "Đang vận chuyển",
    delivered: "Đã giao hàng",
    cancelled: "Đã huỷ",
  };

  const statusColors = {
    all: "border-b-2 border-gray-500 text-gray-600",
    pending: "border-b-2 border-yellow-500 text-yellow-600",
    confirmed: "border-b-2 border-blue-500 text-blue-600",
    shipped: "border-b-2 border-purple-500 text-purple-600",
    delivered: "border-b-2 border-green-500 text-green-600",
    cancelled: "border-b-2 border-red-500 text-red-600",
  };

  const statusOptions = Object.keys(statusMap);

  useEffect(() => {
    if (user?.id) fetchOrders(user.id);
  }, [user, fetchOrders]);

  // Lọc & phân trang
  const filtered =
    statusFilter === "all"
      ? orders
      : orders.filter((o) => o.status === statusFilter);
  const totalPages = Math.ceil(filtered.length / perPage);
  const paginate = (items) =>
    items.slice((currentPage - 1) * perPage, currentPage * perPage);

  const navigate = useNavigate();

  // Modal đánh giá 1 sản phẩm
  const [reviewModal, setReviewModal] = useState({
    open: false,
    productId: null,
    productName: "",
    categorySlug: null,
    orderId: null,
  });

  const closeReview = () =>
    setReviewModal({
      open: false,
      productId: null,
      productName: "",
      categorySlug: null,
      orderId: null,
    });

  // Lấy chi tiết đơn (khi chưa có trong context) — DÙNG HEADER
  const fetchOrderDetailById = async (orderId) => {
    try {
      const token = getAuthToken();
      const res = await axios.get(`${constants.BASE_URL}/orders/${orderId}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      return res.data;
    } catch (e) {
      console.error(
        "Fetch order detail failed:",
        e?.response?.data || e.message
      );
      return null;
    }
  };

  const onRateFromOrderCard = async (order) => {
    if ((order.status || "").trim().toLowerCase() !== "delivered") return;

    let detail = null;
    const existingItems =
      selectedOrder?.id === order.id
        ? selectedOrder.orderDetails ||
          selectedOrder.order_details ||
          selectedOrder.orderItems ||
          selectedOrder.order_items ||
          []
        : [];

    if (existingItems.length > 0) {
      detail = selectedOrder;
    } else {
      detail = await fetchOrderDetailById(order.id);
    }
    if (!detail) return alert("Không lấy được thông tin sản phẩm trong đơn.");

    const items =
      detail.orderDetails ||
      detail.order_details ||
      detail.orderItems ||
      detail.order_items ||
      [];

    const first = items[0];
    if (!first) return alert("Đơn hàng không có sản phẩm để đánh giá.");

    const variant = first.productVariant || first.product_variant;
    const product = variant?.product;
    const productId = variant?.product_id || product?.id;
    const productName = product?.name || first?.product_name || "Sản phẩm";
    const categorySlug =
      product?.category?.slug || product?.category_slug || null;

    if (!productId) return alert("Không tìm thấy mã sản phẩm để đánh giá.");

    setReviewModal({
      open: true,
      productId,
      productName,
      categorySlug,
      orderId: order.id,
    });
  };

  // ✅ MỚI: mở modal đánh giá cho 1 biến thể cụ thể trong chi tiết đơn
  const openReviewForItem = (it, orderId) => {
    const variant = it.productVariant || it.product_variant;
    const product = variant?.product;
    const productId = variant?.product_id || product?.id;
    const productName = product?.name || it.product_name || "Sản phẩm";
    const variantId = variant?.id; // 👈 lấy id biến thể
    if (!productId) {
      Swal.fire({
        icon: "error",
        title: "Lỗi",
        text: "Không tìm thấy mã sản phẩm để đánh giá.",
      });
      return;
    }
    setReviewModal({
      open: true,
      productId,
      productName,
      categorySlug: product?.category?.slug || product?.category_slug || null,
      orderId,
      variantId,
    });
  };

  return (
    <div className="space-y-4 sm:space-y-6">
      {/* Tabs trạng thái */}
      <div className="border-b border-gray-200 text-xs sm:text-sm font-semibold flex gap-4 sm:gap-6 overflow-x-auto">
        {statusOptions.map((status) => (
          <button
            key={status}
            onClick={() => {
              setStatusFilter(status);
              setCurrentPage(1);
            }}
            className={`pb-3 -mb-px whitespace-nowrap transition-colors duration-200 ${
              statusFilter === status
                ? statusColors[status]
                : "text-gray-500 hover:text-red-500"
            }`}
            aria-current={statusFilter === status ? "page" : undefined}
          >
            {statusMap[status]}
          </button>
        ))}
      </div>

      {/* Danh sách */}
      <div className="flex flex-col justify-between min-h-[340px]">
        <div className="space-y-3 sm:space-y-4 flex-1">
          {loading ? (
            Array.from({ length: perPage }).map((_, i) => (
              <div
                key={i}
                className="border border-gray-200 bg-white p-4 rounded-xl flex justify-between animate-pulse shadow-sm"
              >
                <div className="flex-1 space-y-2">
                  <div className="h-4 bg-gray-200 rounded w-1/3"></div>
                  <div className="h-3 bg-gray-100 rounded w-1/4"></div>
                </div>
                <div className="text-right space-y-2">
                  <div className="h-4 bg-gray-200 rounded w-20 ml-auto"></div>
                  <div className="h-3 bg-gray-100 rounded w-16 ml-auto"></div>
                </div>
              </div>
            ))
          ) : filtered.length === 0 ? (
            <div className="text-center py-12">
              <img
                src={logo}
                alt="empty-order"
                className="w-24 h-24 mx-auto mb-3"
              />
              <p className="text-gray-500 text-sm">Bạn chưa có đơn hàng nào</p>
            </div>
          ) : (
            paginate(filtered).map((order) => (
              <div
                key={order.id}
                className="border border-gray-200 bg-white rounded-lg p-3 sm:p-4 shadow-sm hover:shadow-md transition text-sm"
              >
                <div className="flex items-start justify-between gap-4">
                  {/* Trái: mã + ngày */}
                  <div>
                    <div className="flex items-center gap-1">
                      <span className="text-gray-600">Mã đơn:</span>
                      <span className="font-semibold">#{order.id}</span>
                    </div>
                    <p className="mt-1 text-gray-500">
                      Ngày đặt: {order.created_at?.slice(0, 10)}
                    </p>
                  </div>

                  {/* Phải: tổng tiền + trạng thái + nút */}
                  <div className="flex flex-col items-end">
                    <p className="text-base sm:text-lg font-semibold text-red-600">
                      {Number(order.total_amount).toLocaleString("vi-VN")}đ
                    </p>
                    <span className="mt-1 inline-block rounded-full bg-green-50 text-green-700 text-xs font-medium px-2 py-1">
                      {statusMap[order.status]}
                    </span>

                    <div className="mt-2 flex gap-2">
                      <button
                        onClick={() => fetchOrderDetail(order.id)}
                        className="px-3 py-1 text-xs rounded-md border border-gray-300 bg-white
               text-gray-700 hover:border-red-500 hover:text-red-600 hover:bg-red-50
               transition-colors duration-200"
                      >
                        Xem chi tiết
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>

        {/* Phân trang */}
        {totalPages > 1 && !loading && (
          <div className="flex justify-center gap-2 mt-4">
            <button
              onClick={() => setCurrentPage((p) => Math.max(p - 1, 1))}
              disabled={currentPage === 1}
              className="px-3 py-1.5 border border-gray-300 rounded-lg bg-white disabled:opacity-50"
            >
              ←
            </button>
            {Array.from({ length: totalPages }, (_, i) => (
              <button
                key={i}
                onClick={() => setCurrentPage(i + 1)}
                className={`px-3 py-1.5 rounded-lg border ${
                  currentPage === i + 1
                    ? "bg-red-500 border-red-500 text-white"
                    : "bg-white border-gray-300 hover:border-red-400 hover:text-red-500"
                }`}
              >
                {i + 1}
              </button>
            ))}
            <button
              onClick={() => setCurrentPage((p) => Math.min(p + 1, totalPages))}
              disabled={currentPage === totalPages}
              className="px-3 py-1.5 border border-gray-300 rounded-lg bg-white disabled:opacity-50"
            >
              →
            </button>
          </div>
        )}
      </div>

      {/* Modal chi tiết (Portal) */}
      {openDetail &&
        createPortal(
          <div className="fixed inset-0 z-[1000]">
            <div className="fixed inset-0 bg-black/40" onClick={closeDetail} />
            <div className="fixed inset-0 flex items-center justify-center p-4">
              <div className="w-full max-w-lg rounded-2xl bg-white shadow-xl overflow-hidden">
                {/* Header */}
                <div className="flex items-center justify-between border-b px-5 py-3">
                  <h4 className="font-semibold text-gray-900">
                    {selectedOrder
                      ? `Chi tiết đơn #${selectedOrder.id}`
                      : "Chi tiết đơn hàng"}
                  </h4>
                </div>

                {/* Body */}
                <div className="px-4 py-3 text-sm">
                  {loadingDetail ? (
                    <p>Đang tải...</p>
                  ) : detailError ? (
                    <p className="text-red-600">{detailError}</p>
                  ) : selectedOrder ? (
                    <>
                      {/* Danh sách sản phẩm */}
                      <ul className="space-y-3">
                        {(
                          selectedOrder.orderDetails ||
                          selectedOrder.order_details ||
                          selectedOrder.orderItems ||
                          selectedOrder.order_items ||
                          []
                        ).map((it) => {
                          const variant =
                            it.productVariant || it.product_variant;
                          const product = variant?.product;
                          const qty = Number(it.quantity || 0);

                          const raw = variant?.img || "";
                          const base =
                            (constants.BASE_DOMAIN &&
                              constants.BASE_DOMAIN.replace(/\/$/, "")) ||
                            (constants.BASE_URL &&
                              constants.BASE_URL.replace(/\/api\/?$/, "")) ||
                            "";
                          const imgUrl = raw
                            ? /^https?:\/\//i.test(raw)
                              ? raw
                              : `${base}/storage/${String(raw).replace(
                                  /^\/+/,
                                  ""
                                )}`
                            : null;

                          return (
                            <li
                              key={it.id}
                              className="relative flex gap-3 border rounded-lg p-3 hover:shadow-sm transition"
                            >
                              {imgUrl ? (
                                <div className="w-20 h-20 rounded-lg overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center">
                                  <img
                                    src={imgUrl}
                                    alt={
                                      variant?.name ||
                                      product?.name ||
                                      "Ảnh sản phẩm"
                                    }
                                    className="max-w-full max-h-full object-contain"
                                  />
                                </div>
                              ) : (
                                <div className="w-20 h-20 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">
                                  Không có ảnh
                                </div>
                              )}

                              <div className="flex-1">
                                <div className="font-medium text-gray-900">
                                  {product?.name ||
                                    it.product_name ||
                                    "Sản phẩm"}
                                </div>
                                {variant?.name && (
                                  <div className="text-gray-500 text-sm">
                                    Phiên bản: {variant.name}
                                  </div>
                                )}
                                <div className="text-gray-500 text-sm">
                                  Số lượng: {qty}
                                </div>
                              </div>

                              {/* ✅ Nút Đánh giá ở góc dưới phải mỗi biến thể */}
                              {(selectedOrder?.status || "")
                                .trim()
                                .toLowerCase() === "delivered" && (
                                <button
                                  onClick={() =>
                                    openReviewForItem(it, selectedOrder.id)
                                  }
                                  className="absolute bottom-2 right-2 inline-flex items-center gap-1 px-2.5 py-1
                                             text-xs rounded-md border border-gray-300 bg-white
                                             text-gray-700 hover:border-yellow-500 hover:text-yellow-600 hover:bg-yellow-50
                                             transition-colors"
                                  title="Đánh giá sản phẩm này"
                                >
                                  <FaStar className="text-yellow-400" />
                                  Đánh giá
                                </button>
                              )}
                            </li>
                          );
                        })}
                      </ul>

                      {/* Thông tin nhận hàng (gọn) */}
                      <div className="border-t mt-3 pt-3 space-y-2">
                        <p className="font-medium mb-1">Thông tin đơn hàng</p>
                        <div className="flex justify-between gap-3">
                          <span className="text-gray-600">Tên người nhận:</span>
                          <span className="font-medium">
                            {selectedOrder.recipient_name || "—"}
                          </span>
                        </div>
                        <div className="flex justify-between gap-3">
                          <span className="text-gray-600">Số điện thoại:</span>
                          <span className="font-medium">
                            {selectedOrder.phone || "—"}
                          </span>
                        </div>
                        <div className="flex justify-between gap-3">
                          <span className="text-gray-600">
                            Số nhà, tên đường:
                          </span>
                          <span className="font-medium text-right">
                            {selectedOrder.address || "—"}
                          </span>
                        </div>
                      </div>

                      {/* Ngày/trạng thái/tổng */}
                      <div className="border-t mt-4 pt-3 space-y-2">
                        <div className="flex justify-between">
                          <span className="font-medium">Ngày đặt:</span>
                          <span>{selectedOrder.created_at?.slice(0, 10)}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="font-medium">Trạng thái:</span>
                          <span className="text-green-600 font-medium">
                            {statusMap[selectedOrder.status] ||
                              selectedOrder.status}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="font-medium">Tổng tiền:</span>
                          <span className="text-red-600 font-semibold">
                            {Number(
                              selectedOrder.total_amount || 0
                            ).toLocaleString("vi-VN")}
                            đ
                          </span>
                        </div>
                      </div>
                    </>
                  ) : null}
                </div>

                {/* Footer */}
                <div className="border-t px-5 py-3 text-right">
                  {selectedOrder?.status === "pending" && (
                    <button
                      onClick={() => requestCancel(selectedOrder.id)}
                      className="mr-2 px-4 py-1.5 text-sm rounded-md border border-red-500 text-red-600 hover:bg-red-50"
                    >
                      Huỷ đơn
                    </button>
                  )}
                  <button
                    onClick={closeDetail}
                    className="px-4 py-1.5 text-sm rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                  >
                    Đóng
                  </button>
                </div>
              </div>
            </div>
          </div>,
          document.body
        )}

      {/* Modal đánh giá (Portal) */}
      <ReviewModal
        open={reviewModal.open}
        productId={reviewModal.productId}
        productName={reviewModal.productName}
        orderId={reviewModal.orderId}
        variantId={reviewModal.variantId}
        onClose={closeReview}
      />
    </div>
  );
};

function ReviewModal({
  open,
  onClose,
  productId,
  productName,
  orderId,
  onSuccess,
  variantId,
}) {
  const [rating, setRating] = useState(0);
  const [hover, setHover] = useState(0);
  const [comment, setComment] = useState("");
  const [loading, setLoading] = useState(false);
  if (!open) return null;

  const token = getAuthToken();

  const submit = async (e) => {
    e.preventDefault();
    if (!rating) {
      Swal.fire({
        icon: "warning",
        title: "Thiếu thông tin",
        text: "Vui lòng chọn số sao trước khi gửi đánh giá",
      });
      return;
    }

    try {
      setLoading(true);
      await axios.post(
        `${constants.BASE_URL}/products/${productId}/reviews`,
        { rating, comment, order_id: orderId, product_variant_id: variantId },
        { headers: token ? { Authorization: `Bearer ${token}` } : {} }
      );

      onSuccess?.(productId);
      onClose?.();

      setTimeout(() => {
        Swal.fire({
          icon: "success",
          title: "Đánh giá thành công",
          text: "Cảm ơn bạn đã đánh giá sản phẩm!",
          timer: 2000,
          showConfirmButton: false,
        });
      }, 300);
    } catch (err) {
      const status = err?.response?.status;
      const msg = err?.response?.data?.error;

      if (status === 401) {
        Swal.fire({
          icon: "error",
          title: "Chưa đăng nhập",
          text: "Bạn chưa đăng nhập hoặc token không hợp lệ.",
        });
      } else if (status === 403) {
        Swal.fire({
          icon: "error",
          title: "Không thể đánh giá",
          text: "Chỉ được đánh giá khi đơn hàng đã giao.",
        });
      } else if (status === 409) {
        onClose?.();
        setTimeout(() => {
          Swal.fire({
            icon: "warning",
            title: "Đã đánh giá thất bại",
            text: "Bạn đã đánh giá sản phẩm này rồi.",
            timer: 2000,
            showConfirmButton: false,
          });
        }, 300);
      } else {
        Swal.fire({
          icon: "error",
          title: "Thất bại",
          text: msg || "Gửi đánh giá thất bại",
        });
      }
    } finally {
      setLoading(false);
    }
  };

  return createPortal(
    <div
      className="fixed inset-0 z-[1110] bg-black/40 flex items-center justify-center"
      onClick={onClose}
    >
      <div
        className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl"
        onClick={(e) => e.stopPropagation()}
      >
        <h3 className="text-lg font-semibold mb-1">Đánh giá sản phẩm</h3>
        <p className="text-sm text-gray-600 mb-2">{productName}</p>
        <p className="text-xs text-gray-600 mb-2">Mã đơn hàng: #{orderId}</p>

        {/* Chọn sao */}
        <div className="flex items-center gap-2 mb-3">
          {[1, 2, 3, 4, 5].map((s) => (
            <button
              key={s}
              type="button"
              onMouseEnter={() => setHover(s)}
              onMouseLeave={() => setHover(0)}
              onClick={() => setRating(s)}
              className="p-1"
            >
              <FaStar
                size={26}
                className={
                  (hover || rating) >= s ? "text-yellow-400" : "text-gray-300"
                }
              />
            </button>
          ))}
          <span className="text-sm text-gray-600">{rating}/5</span>
        </div>

        {/* Comment */}
        <textarea
          rows={3}
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          className="w-full border rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
          placeholder="Chia sẻ cảm nhận của bạn..."
        />

        <div className="mt-4 flex justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 rounded-xl border bg-white hover:bg-gray-50"
            disabled={loading}
          >
            Hủy
          </button>
          <button
            onClick={submit}
            disabled={loading || !rating}
            className="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700"
          >
            {loading ? "Đang gửi..." : "Gửi đánh giá"}
          </button>
        </div>
      </div>
    </div>,
    document.body
  );
}

export default OrderHistory;
