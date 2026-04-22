import React, { useContext, useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  FaTrashAlt,
  FaArrowLeft,
  FaShoppingCart,
  FaClipboardList,
  FaSpinner,
} from "react-icons/fa";
import { HiMinusSm, HiPlusSm } from "react-icons/hi";
import iphone from "../../../assets/iphone-16-pro-max_2.webp";
import { useProductVariant } from "../../../contexts/ProductVariantContextCart";
import { ProductShopContext } from "../../../contexts/ProductShopContext";
import { useCart } from "../../../contexts/CartContext";
import { useCity } from "../../../contexts/CityContext";
import constants from "../../../constants/constants";
import Advice from "../../../components/Advice/Advice";
import SummaryItem from "../../../components/SummaryItem/SummaryItem";
import { useAvailableProducts } from "../../../hooks/useAvailableProducts";
import { toast } from "react-toastify";

const Cart = () => {
  const navigate = useNavigate();
  const { cartItems, updateCartItem, removeCartItem, fetchCart } = useCart();
  const { variants } = useProductVariant();
  const { products } = useContext(ProductShopContext);
  const { selectedCity } = useCity();
  const [displayItems, setDisplayItems] = useState([]);
  const [showAdviceModal, setShowAdviceModal] = useState(false);
  const [adviceItem, setAdviceItem] = useState(null);
  const { availableProducts } = useAvailableProducts();
  const [loading, setLoading] = useState(true);

  // Fetch cart when page loads
  useEffect(() => {
    const loadCart = async () => {
      try {
        setLoading(true);
        await fetchCart();
      } finally {
        setLoading(false);
      }
    };
    loadCart();
  }, []);

  useEffect(() => {
    if (!cartItems || cartItems.length === 0 || variants.length === 0 || products.length === 0) {
      return;
    }

    const mapped = cartItems
      .map((item) => {
        const variant = variants.find((v) => v.id === item.product_variant_id);
        if (!variant) return null;

        const product = products.find((p) => p.id === variant.product_id);
        const price = parseFloat(variant.price || 0);
        const discount = parseFloat(variant.discount || 0);
        const finalPrice = price - discount;

        const quantity = item.quantity || 1;
        const maxAffordableQty = Math.floor(100_000_000 / finalPrice);
        const stockQty = availableProducts
          .filter((p) => +p.variant_id === +variant.id)
          .reduce((sum, p) => sum + (p.quantity || 0), 0);
        const maxQty = Math.min(5, maxAffordableQty, stockQty);

        return {
          id: item.id,
          variantId: item.product_variant_id,
          name: variant.name,
          productName: product?.name || "Sản phẩm",
          price: finalPrice,
          oldPrice: price,
          image: variant.img
            ? `${constants.BASE_DOMAIN}/storage/${variant.img}`
            : iphone,
          quantity: quantity > maxQty ? maxQty : quantity,
          maxQty,
          stockQty,
          selected: false,
        };
      })
      .filter(Boolean);

    setDisplayItems((prev) => {
      const isSame =
        prev.length === mapped.length &&
        prev.every((p, i) => JSON.stringify(p) === JSON.stringify(mapped[i]));
      return isSame ? prev : mapped;
    });
  }, [cartItems, variants, products, selectedCity, availableProducts]);

  const updateQty = async (id, currentQty, amount) => {
    const item = displayItems.find((i) => i.id === id);
    if (!item) return;

    const nextQty = currentQty + amount;
    if (nextQty < 1) return;

    if (nextQty > item.stockQty) {
      toast.warn(
        `Vượt quá số lượng tồn kho. Chỉ còn ${item.stockQty} sản phẩm tại ${selectedCity}.`
      );
      return;
    }

    if (nextQty > item.maxQty) {
      setAdviceItem(item);
      setShowAdviceModal(true);
      return;
    }

    try {
      const updatedItem = await updateCartItem(id, nextQty);
      setDisplayItems((prev) =>
        prev.map((p) =>
          p.id === id ? { ...p, quantity: updatedItem.quantity } : p
        )
      );
    } catch (err) {
      console.error("Không thể cập nhật giỏ hàng:", err);
    }
  };

  const removeItem = (id) => {
    removeCartItem(id);
    setDisplayItems((prev) => prev.filter((item) => item.id !== id));
    toast.success("Đã xoá sản phẩm khỏi giỏ hàng.");
  };

  const toggleSelect = (id) => {
    setDisplayItems((prev) =>
      prev.map((item) =>
        item.id === id ? { ...item, selected: !item.selected } : item
      )
    );
  };

  const toggleSelectAll = () => {
    const inStockItems = displayItems.filter((item) => item.stockQty > 0);
    const isAllSelected =
      inStockItems.length > 0 && inStockItems.every((item) => item.selected);

    setDisplayItems((prev) =>
      prev.map((item) =>
        item.stockQty > 0 ? { ...item, selected: !isAllSelected } : item
      )
    );
  };

  const total = displayItems.reduce(
    (sum, item) => (item.selected ? sum + item.price * item.quantity : sum),
    0
  );
  const selectedCount = displayItems.filter((i) => i.selected).length;

  function renderCartItem(item) {
    return (
      <div
        key={item.id}
        className="relative bg-white border border-gray-200 rounded-xl shadow-md p-3 flex items-center justify-between"
      >
        <div className="flex items-start gap-3 flex-1">
          {item.stockQty > 0 && (
            <input
              type="checkbox"
              checked={item.selected}
              onChange={() => toggleSelect(item.id)}
              className="w-4 h-4 accent-red-600 mt-1"
            />
          )}

          <div className="relative w-20 h-20 sm:w-24 sm:h-24 lg:w-28 lg:h-28 rounded-lg overflow-hidden bg-gray-50 border flex-shrink-0">
            <img
              src={item.image}
              alt={item.name}
              className={`w-full h-full object-contain ${item.stockQty <= 0 ? "opacity-40" : ""
                }`}
            />
            {item.stockQty <= 0 && (
              <div className="absolute top-1 left-1 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow">
                Hết hàng
              </div>
            )}
          </div>

          <div className="flex flex-col justify-between flex-1 min-w-0">
            <div>
              <h3 className="font-semibold text-gray-900 text-sm sm:text-base lg:text-base line-clamp-1">
                {item.productName}
              </h3>
              <p className="text-xs sm:text-sm lg:text-xs text-gray-500">
                {item.name}
              </p>
              <div className="mt-1 flex items-center gap-2">
                <span className="text-red-600 font-bold text-sm sm:text-base lg:text-base">
                  {item.price.toLocaleString()}đ
                </span>
                {item.oldPrice > item.price && (
                  <span className="line-through text-gray-400 text-xs sm:text-sm lg:text-xs">
                    {item.oldPrice.toLocaleString()}đ
                  </span>
                )}
              </div>
            </div>

            <div className="mt-2 flex items-center gap-2 sm:gap-3">
              <button
                onClick={() => updateQty(item.id, item.quantity, -1)}
                className="w-7 h-7 sm:w-8 sm:h-8 lg:w-8 lg:h-8 border border-gray-300 rounded-md 
                         flex items-center justify-center text-gray-600 bg-white 
                         hover:bg-gray-100 transition disabled:opacity-50"
                disabled={item.stockQty <= 0}
              >
                <HiMinusSm className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              </button>

              <span className="w-7 sm:w-8 text-center font-semibold text-gray-800 text-xs sm:text-sm lg:text-sm">
                {item.quantity}
              </span>

              <button
                onClick={() => updateQty(item.id, item.quantity, 1)}
                className="w-7 h-7 sm:w-8 sm:h-8 lg:w-8 lg:h-8 border border-gray-300 rounded-md 
                         flex items-center justify-center text-gray-600 bg-white 
                         hover:bg-gray-100 transition disabled:opacity-50"
                disabled={item.stockQty <= 0}
              >
                <HiPlusSm className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              </button>
            </div>
          </div>
        </div>

        <button
          onClick={() => removeItem(item.id)}
          title="Xoá"
          className="ml-3 w-9 h-9 flex items-center justify-center text-gray-500 bg-white border rounded-md 
                   hover:text-white hover:bg-red-500 hover:shadow-md transition-all duration-200"
        >
          <FaTrashAlt className="w-4 h-4" />
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-[1200px] mx-auto min-h-[70vh] px-4 py-6 sm:py-10">
      <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <span className="w-1.5 h-6 bg-red-600 rounded-full"></span>
          Giỏ hàng của bạn
        </h2>

        <button
          onClick={() => navigate(-1)}
          className="flex items-center gap-2 px-4 py-2 rounded-full border border-gray-300 text-sm font-medium 
          text-gray-600 hover:text-red-600 hover:border-red-400 hover:shadow-md 
          transition-all duration-200"
        >
          <FaArrowLeft className="w-4 h-4" />
          Quay lại mua sắm
        </button>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center h-[400px] bg-white border border-gray-200 rounded-xl shadow-lg">
          <FaSpinner className="animate-spin text-4xl text-red-600 mb-4" />
          <p className="text-gray-500 font-medium">Đang tải giỏ hàng...</p>
        </div>
      ) : (
        <div
          className={`grid gap-6 items-start ${displayItems.length > 0 ? "grid-cols-1 lg:grid-cols-3" : "grid-cols-1"
            }`}
        >
          <div
            className={`bg-white border border-gray-300 rounded-xl shadow-xl p-4 sm:p-6 ${displayItems.length === 0
              ? "flex items-center justify-center h-[400px]"
              : "lg:col-span-2"
              }`}
          >
            {displayItems.length > 0 ? (
              <>
                <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                  <label className="flex items-center gap-3 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={
                        displayItems.filter((item) => item.stockQty > 0).length > 0 &&
                        displayItems
                          .filter((item) => item.stockQty > 0)
                          .every((item) => item.selected)
                      }
                      onChange={toggleSelectAll}
                      className="w-5 h-5 accent-red-600 rounded cursor-pointer transition-all duration-200"
                    />
                    <span className="text-base font-semibold text-gray-800">
                      Chọn tất cả ({selectedCount})
                    </span>
                  </label>
                </div>

                <div className="space-y-5 max-h-[420px] overflow-y-auto pr-2 scroll-smooth w-full custom-scrollbar">
                  {displayItems.map((item) => renderCartItem(item))}
                </div>
              </>
            ) : (
              <div className="flex flex-col items-center justify-center text-gray-500">
                <FaShoppingCart className="text-6xl text-gray-300 mb-4" />
                <p className="text-lg font-medium">Giỏ hàng của bạn đang trống</p>
                <p className="text-sm mt-1 text-gray-400">
                  Hãy thêm sản phẩm yêu thích vào giỏ hàng để bắt đầu mua sắm!
                </p>
                <button
                  onClick={() => navigate("/")}
                  className="mt-6 px-6 py-2 rounded-lg bg-red-600 text-white font-semibold shadow hover:bg-red-700 transition"
                >
                  Mua sắm ngay
                </button>
              </div>
            )}
          </div>

          {displayItems.length > 0 && (
            <div className="bg-white border border-gray-200 rounded-xl shadow-xl p-4 sm:p-6">
              <h3 className="text-lg sm:text-xl font-semibold text-gray-800 mb-4 sm:mb-6 flex items-center gap-2">
                <FaClipboardList className="text-red-600 text-xl" />
                Thông tin đơn hàng
              </h3>

              <div className="max-h-[200px] overflow-y-auto pr-1 custom-scrollbar">
                {displayItems.filter((item) => item.selected).length > 0 ? (
                  displayItems
                    .filter((item) => item.selected)
                    .map((item) => <SummaryItem key={item.id} item={item} />)
                ) : (
                  <div className="flex flex-col items-center justify-center py-6 text-gray-500">
                    <p className="font-medium">Chưa có sản phẩm nào được chọn</p>
                    <p className="text-sm text-gray-400 mt-1">
                      Vui lòng thêm sản phẩm vào giỏ hàng để tiến hành thanh toán
                    </p>
                  </div>
                )}
              </div>

              <div className="space-y-2 text-sm sm:text-base">
                <div className="flex justify-between mt-2">
                  <span>Tạm tính</span>
                  <span className="font-medium text-gray-700">
                    {total.toLocaleString()}đ
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Tiết kiệm</span>
                  <span className="text-green-600 font-medium">
                    {displayItems
                      .reduce(
                        (sum, item) =>
                          item.selected
                            ? sum + (item.oldPrice - item.price) * item.quantity
                            : sum,
                        0
                      )
                      .toLocaleString()}đ
                  </span>
                </div>
              </div>

              <hr className="my-4" />

              <div className="flex justify-between items-center mb-4">
                <span className="text-base font-bold text-gray-800">
                  Tổng thanh toán
                </span>
                <span className="text-lg sm:text-xl font-bold text-red-600">
                  {total.toLocaleString()}đ
                </span>
              </div>

              <button
                disabled={
                  selectedCount === 0 ||
                  displayItems.some((item) => item.selected && item.quantity === 0)
                }
                onClick={() => {
                  const selected = displayItems.filter((i) => i.selected);
                  localStorage.setItem("checkoutItems", JSON.stringify(selected));
                  navigate("/checkout");
                }}
                className="w-full bg-red-600 text-white py-3 rounded-lg text-sm sm:text-base font-semibold 
           hover:bg-red-700 disabled:opacity-50 transition"
              >
                {selectedCount > 0
                  ? `Xác nhận đơn (${selectedCount})`
                  : "Chọn sản phẩm để thanh toán"}
              </button>
            </div>
          )}
        </div>
      )}

      {showAdviceModal && (
        <Advice onClose={() => setShowAdviceModal(false)} item={adviceItem} />
      )}
    </div>
  );
};

export default Cart;
