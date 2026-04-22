import React, { useState, useEffect, useContext, useRef, useMemo } from "react";

import { Fragment } from "react";
import { CheckCircle } from "lucide-react";
import { toast } from "react-toastify";
import constants from "../../../constants/constants";
import { useNavigate } from "react-router-dom";
import banner1 from "../../../assets/iphone-16-pro-max-1-638639190782955686.jpg";
import banner2 from "../../../assets/iphone-16-pro-max-2-638639190801601764.jpg";
import { useAvailableProducts } from "../../../hooks/useAvailableProducts";
import { useCity } from "../../../contexts/CityContext";
import { Listbox } from "@headlessui/react";
import { useWishlistContext } from "../../../contexts/WishlistContext";
import WishlistButton from "../../../components/WishlistButton/WishlistButton";
import axios from "axios";

const ProductInfo = ({
  productDetail,
  selectedVariant,
  setSelectedVariant,
  cartItems,
  setSelectedImage,
}) => {
  // --- CONTEXT & HOOKS CÓ SẴN ---
  const { cityList, selectedCity, setSelectedCity } = useCity();
  const { availableProducts, loading, error } = useAvailableProducts();
  const navigate = useNavigate();

  // Giá & giảm giá từ variant / product
  const basePrice = Number(selectedVariant?.price ?? productDetail?.price ?? 0);
  const discount = Number(
    selectedVariant?.discount ?? productDetail?.discount ?? 0
  );
  const finalPrice = Math.max(0, basePrice - discount);
  const hasDiscount = discount > 0 && basePrice > finalPrice;

  const fmt = (n) => new Intl.NumberFormat("vi-VN").format(n);

  // --- UI STATE CÓ SẴN ---
  const [showMore, setShowMore] = useState(false);

  // --- STATE DROPDOWN QUẬN/HUYỆN ---
  const [selectedWard, setSelectedWard] = useState("");
  // Tạo cityList duy nhất, ưu tiên bản có chữ hoa
  const uniqueCityList = useMemo(() => {
    const map = new Map();
    cityList.forEach((city) => {
      const key = city.toLowerCase();
      if (!map.has(key) || /[A-Z]/.test(city)) {
        map.set(key, city);
      }
    });
    return Array.from(map.values());
  }, [cityList]);

  // --- STATE THANH TÌM KIẾM NHANH ---
  const [searchKeyword, setSearchKeyword] = useState("");

  // --- TẠO DANH SÁCH QUẬN/HUYỆN TỪ DỮ LIỆU KHO (UNIQUE, BỎ RỖNG) ---
  const wards = useMemo(() => {
    return [
      ...new Set(availableProducts.map((b) => b.branch_ward).filter(Boolean)),
    ];
  }, [availableProducts]);

  // --- LỌC CHI NHÁNH THEO WARD + BIẾN THỂ (GIỮ LOGIC CŨ) ---
  // *QUAN TRỌNG*: Khai báo filteredBranches TRƯỚC, để displayedBranches dùng được
  const filteredBranches = useMemo(() => {
    return availableProducts
      .filter((b) => !selectedWard || b.branch_ward === selectedWard)
      .filter(
        (b) =>
          !selectedVariant ||
          String(b.variant_id) === String(selectedVariant.id)
      );
  }, [availableProducts, selectedWard, selectedVariant]);

  // --- LỌC NHANH THEO TỪ KHÓA (KHÔNG PHÁ filteredBranches) ---
  const displayedBranches = useMemo(() => {
    const kw = (searchKeyword || "").toLowerCase().trim();
    if (!kw) return filteredBranches;

    return filteredBranches.filter((b) => {
      const addr = (b?.branch_address || "").toLowerCase();
      const ward = (b?.branch_ward || "").toLowerCase();
      const city = (b?.branch_city || "").toLowerCase();
      const phone = (b?.branch_phone || "").toLowerCase();
      return (
        addr.includes(kw) ||
        ward.includes(kw) ||
        city.includes(kw) ||
        phone.includes(kw)
      );
    });
  }, [filteredBranches, searchKeyword]);

  // --- TÍNH TỒN KHO THEO BIẾN THỂ ĐANG CHỌN (DÙNG LẠI NHIỀU NƠI) ---
  const stockQty = useMemo(() => {
    if (!selectedVariant) return 0;
    return availableProducts
      .filter((p) => +p.variant_id === +selectedVariant.id)
      .reduce((sum, p) => sum + (p.quantity || 0), 0);
  }, [availableProducts, selectedVariant]);

  const isOutOfStock = selectedVariant && stockQty <= 0;

  // --- SCROLL VỀ ĐẦU LIST KHI THAY ĐỔI BỘ LỌC/BIẾN THỂ ---
  const listRef = useRef(null);

  useEffect(() => {
    if (listRef.current) listRef.current.scrollLeft = 0;
  }, [selectedVariant, selectedCity, selectedWard]);

  // --- RESET WARD KHI ĐỔI CITY ---
  useEffect(() => {
    setSelectedWard("");
  }, [selectedCity]);

  // --- THÊM VÀO GIỎ ---
  const handleAddToCart = async () => {
    if (!selectedVariant) {
      toast.error("Vui lòng chọn phiên bản trước khi thêm vào giỏ hàng");
      return;
    }

    // kiểm tra tồn kho hiện tại so với số lượng đang có trong giỏ
    const cartItem = cartItems.find(
      (item) => +item.product_variant_id === +selectedVariant.id
    );
    const currentQty = cartItem ? cartItem.quantity : 0;

    if (stockQty <= 0) {
      toast.error("Phiên bản này hiện không còn hàng.");
      return;
    }

    if (currentQty + 1 > stockQty) {
      toast.error(`Chỉ còn ${stockQty} sản phẩm trong kho.`);
      return;
    }

    try {
      const user = JSON.parse(localStorage.getItem("user"));
      if (!user?.id) throw new Error("Vui lòng đăng nhập lại.");

      const res = await fetch(`/api/carts`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("access_token")}`,
        },
        body: JSON.stringify({
          user_id: user.id,
          product_variant_id: selectedVariant.id,
          quantity: 1,
        }),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data?.message || "Thêm vào giỏ thất bại");

      toast.success("Đã thêm sản phẩm vào giỏ hàng!");
    } catch (error) {
      toast.error(error.message || "Có lỗi xảy ra khi thêm giỏ hàng");
    }
  };

  // --- MUA NGAY ---
  const handleBuyNow = async () => {
    if (!selectedVariant) {
      toast.error("Vui lòng chọn phiên bản trước khi mua ngay");
      return;
    }

    try {
      const user = JSON.parse(localStorage.getItem("user"));
      const token = localStorage.getItem("access_token");

      if (!user?.id || !token) {
        toast.error("Bạn cần đăng nhập");
        navigate("/login");
        return;
      }

      // 1) Thêm 1 cart-item trên BE để lấy ID thật
      const res = await axios.post(
        `${constants.BASE_URL}/carts`,
        {
          user_id: user.id,
          product_variant_id: selectedVariant.id,
          quantity: 1,
        },
        { headers: { Authorization: `Bearer ${token}` } }
      );

      const cartItemId = res?.data?.id;
      if (!cartItemId) {
        throw new Error("Không tạo được cart item cho 'Mua ngay'");
      }

      // 2) Lưu checkoutItems với cart_item_id thật từ BE
      const buyNowItem = [
        {
          // giữ lại id BE để về sau tạo đơn dùng cart_ids hợp lệ
          id: cartItemId, // ⬅ cart_item_id thật (QUAN TRỌNG)
          cart_item_id: cartItemId, // để đề phòng nơi khác dùng key này
          fromCart: true,

          variantId: selectedVariant.id,
          productName: productDetail.name,
          name: selectedVariant.name,
          price:
            (selectedVariant.price || productDetail.price) -
            (selectedVariant.discount || productDetail.discount || 0),
          oldPrice: selectedVariant.price || productDetail.price,
          image: selectedVariant.img
            ? `${constants.BASE_DOMAIN}/storage/${selectedVariant.img}`
            : productDetail.image,
          quantity: 1,
          selected: true,
        },
      ];

      localStorage.setItem("checkoutItems", JSON.stringify(buyNowItem));

      // 3) Điều hướng sang trang thanh toán
      navigate("/checkout");
    } catch (err) {
      console.error(err);
      toast.error(
        err?.response?.data?.message ||
          err?.message ||
          "Có lỗi xảy ra khi mua ngay"
      );
    }
  };

  return (
    <div className="w-full md:flex-1 space-y-4">
      {/* Giá & giảm giá */}
      <div className="bg-white p-3 rounded-lg border border-red-300 shadow-sm space-y-1 w-full sm:w-[280px]">
        <h3 className="text-base font-semibold text-gray-700 mb-1">
          Giá sản phẩm
        </h3>

        <div className="flex items-baseline gap-2 flex-wrap">
          {/* Giá đã giảm (màu đỏ) */}
          <span className="text-2xl font-bold text-red-600">
            {fmt(finalPrice)}₫
          </span>

          {/* Giá gốc gạch ngang (xám) */}
          {hasDiscount && (
            <span className="text-sm text-gray-400 line-through">
              {fmt(basePrice)}₫
            </span>
          )}
        </div>
        {productDetail?.oldPrice && (
          <p className="text-xs text-green-600 font-medium">
            Giá KM chỉ áp dụng hôm nay!
          </p>
        )}
      </div>

      <div className="space-y-4 hidden md:block">
        <h3 className="font-semibold text-base text-gray-800">Phiên Bản</h3>
        {productDetail.product_variants &&
          productDetail.product_variants.length > 0 && (
            <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
              {productDetail.product_variants.map((variant) => {
                const stockQty = availableProducts
                  .filter((p) => +p.variant_id === +variant.id)
                  .reduce((sum, p) => sum + (p.quantity || 0), 0);

                const isOutOfStock = stockQty <= 0;
                const isSelected = selectedVariant?.id === variant.id;

                return (
                  <button
                    key={variant.id}
                    onClick={() => {
                      if (!isOutOfStock) {
                        setSelectedVariant(variant);
                        setSelectedImage(
                          `${constants.BASE_DOMAIN}/storage/${variant.img}`
                        );
                      }
                    }}
                    disabled={isOutOfStock}
                    className={`relative flex flex-col items-center border rounded-xl p-2 shadow-sm transition duration-200
              ${
                isOutOfStock
                  ? "border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-60"
                  : isSelected
                  ? "border-red-500 shadow-md"
                  : "border-gray-200 bg-white hover:border-red-400"
              }`}
                  >
                    {isOutOfStock && (
                      <span className="absolute top-1 left-1 bg-red-600 text-white text-xs px-2 py-0.5 rounded">
                        Hết hàng
                      </span>
                    )}

                    {isSelected && !isOutOfStock && (
                      <div className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-[2px]">
                        <CheckCircle className="w-3 h-3" />
                      </div>
                    )}

                    <div className="w-12 h-12 mb-1 overflow-hidden rounded-md bg-gray-50">
                      <img
                        src={`${constants.BASE_DOMAIN}/storage/${variant.img}`}
                        alt={variant.name}
                        className="w-full h-full object-contain"
                      />
                    </div>
                    <span className="text-xs font-medium text-gray-700 text-center">
                      {variant.name}
                    </span>
                  </button>
                );
              })}
            </div>
          )}
      </div>

      {/* Chỉ hiển thị ở desktop */}
      <div className="hidden sm:flex flex-col sm:flex-row flex-wrap gap-2 mt-4">
        <div className="flex items-center gap-2 px-4 py-2 rounded-lg border border-red-600 bg-white text-red-600 font-semibold hover:bg-red-50 hover:text-red-700 transition-all duration-200">
          <WishlistButton
            productId={selectedVariant?.product_id || productDetail?.id}
            className="flex items-center gap-2 px-4 py-2 rounded-lg border border-red-600 bg-white text-red-600 font-semibold hover:bg-red-50 hover:text-red-700 transition-all duration-200"
          />
        </div>

        <button
          onClick={handleBuyNow}
          className="bg-red-600 text-center text-white rounded-lg px-4 py-2 font-bold hover:bg-red-700 transition flex-1 w-full sm:w-auto text-left sm:text-center"
        >
          MUA NGAY
          <span className="block text-xs font-normal">
            Giao nhanh từ 2 giờ hoặc nhận tại cửa hàng
          </span>
        </button>

        <button
          onClick={handleAddToCart}
          className="border border-red-500 text-red-500 rounded-lg px-4 py-2 font-semibold hover:bg-red-50 transition flex items-center justify-center gap-1 w-full sm:w-auto"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M3 3h2l.4 2M7 13h14l-1.35 5.38a2 2 0 01-1.95 1.62H7.5a2 2 0 01-1.98-1.75L5 6H3"
            />
          </svg>
          Thêm vào giỏ
        </button>
      </div>

      {/* Xem chi nhánh */}
      <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm mt-6 space-y-5">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <h3 className="text-lg font-semibold text-gray-800">
            Xem chi nhánh có hàng
          </h3>

          {/* Bộ lọc */}
          <div className="flex gap-3">
            {/* Thành phố */}
            <div className="relative w-40">
              <Listbox value={selectedCity} onChange={setSelectedCity}>
                <div className="relative">
                  <Listbox.Button className="w-full px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-sm hover:border-gray-400 focus:outline-none">
                    {selectedCity || "Chọn thành phố"}
                    <span className="ml-2 text-gray-500">▼</span>
                  </Listbox.Button>
                  <Listbox.Options className="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-md max-h-60 overflow-auto z-50">
                    {uniqueCityList.map((city, idx) => (
                      <Listbox.Option key={idx} value={city} as={Fragment}>
                        {({ active, selected }) => {
                          const isSelected =
                            selectedCity?.toLowerCase() === city.toLowerCase();

                          return (
                            <li
                              className={`cursor-pointer px-3 py-2 ${
                                active ? "bg-gray-100" : "text-gray-700"
                              } ${isSelected ? "font-semibold" : ""}`}
                            >
                              {city}
                            </li>
                          );
                        }}
                      </Listbox.Option>
                    ))}
                  </Listbox.Options>
                </div>
              </Listbox>
            </div>

            {/* Quận/Huyện */}
            <div className="relative w-40">
              <Listbox value={selectedWard} onChange={setSelectedWard}>
                <div className="relative">
                  <Listbox.Button className="w-full px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-sm hover:border-gray-400 focus:outline-none">
                    {selectedWard || "Tất cả"}
                    <span className="ml-2 text-gray-500">▼</span>
                  </Listbox.Button>
                  <Listbox.Options className="absolute mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-md max-h-60 overflow-auto z-50">
                    {wards.map((ward, idx) => (
                      <Listbox.Option key={idx} value={ward} as={Fragment}>
                        {({ active, selected }) => (
                          <li
                            className={`cursor-pointer px-3 py-2 ${
                              active ? "bg-gray-100" : "text-gray-700"
                            } ${selected ? "font-semibold" : ""}`}
                          >
                            {ward}
                          </li>
                        )}
                      </Listbox.Option>
                    ))}
                  </Listbox.Options>
                </div>
              </Listbox>
            </div>
          </div>
        </div>

        {/* Tổng số cửa hàng + ô tìm kiếm */}
        {!loading && !error && (
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p className="text-sm text-gray-600">
              Có{" "}
              <span className="font-semibold text-red-500">
                {displayedBranches.length}
              </span>{" "}
              chi nhánh có sản phẩm
            </p>

            {/* Thanh tìm kiếm */}
            <input
              type="text"
              placeholder="Tìm nhanh (địa chỉ/điện thoại/city/ward)"
              value={searchKeyword}
              onChange={(e) => setSearchKeyword(e.target.value)}
              className="w-full sm:w-56 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:outline-none transition-colors duration-200"
            />
          </div>
        )}

        {/* Danh sách chi nhánh */}
        <div className="relative h-[70px] sm:h-[90px]">
          {/* Scroller tuyệt đối nằm trong khung cao cố định => không bao giờ đẩy layout */}
          <div className="absolute inset-0 overflow-x-auto overflow-y-hidden scrollbar-none scrollbar-stable">
            {/* 1 hàng, không wrap, snap mượt, full height */}
            <div className="flex flex-nowrap gap-4 snap-x snap-mandatory h-full items-stretch">
              {displayedBranches.length > 0 ? (
                displayedBranches.map((branch, index) => (
                  <div
                    key={index}
                    className="snap-start flex-none w-64 sm:w-72 h-full border rounded-lg p-3 bg-white shadow-sm hover:shadow-md transition"
                    title={`${branch?.branch_address || ""}, ${
                      branch?.branch_ward || ""
                    }, ${branch?.branch_city || ""}`}
                  >
                    <p className="text-sm font-medium text-gray-800 line-clamp-2">
                      {branch.branch_address}, {branch.branch_ward},{" "}
                      {branch.branch_city}
                    </p>
                    <p className="mt-1 text-sm text-gray-600">
                      📞 {branch.branch_phone}
                    </p>
                  </div>
                ))
              ) : (
                <p className="text-gray-500 text-center py-4 w-full">
                  Không có chi nhánh nào hiển thị
                </p>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Giới thiệu sản phẩm */}
      <div className="max-w-4xl mx-auto px-6 py-6 bg-white rounded-2xl shadow-md">
        {/* Ép mọi phần tử trong mô tả không có nền trắng */}
        <style>{`
    .desc-content * { background-color: transparent !important; }
    .desc-content img { max-width: 100%; height: auto; border-radius: 0.5rem; }
  `}</style>

        {/* Tiêu đề */}
        <h2 className="text-2xl font-bold mb-5 text-gray-900 border-b border-gray-200 pb-3">
          Giới thiệu sản phẩm
        </h2>

        {(() => {
          // --- Làm sạch HTML ---
          const raw = productDetail?.description || "";
          let cleaned = raw;
          try {
            const doc = new DOMParser().parseFromString(raw, "text/html");
            doc.querySelectorAll("[style]").forEach((el) => {
              el.style.background = "";
              el.style.backgroundColor = "";
            });
            doc.querySelectorAll("[class]").forEach((el) => {
              el.className = el.className
                .split(" ")
                .filter(
                  (c) =>
                    !/^bg-/i.test(c) && c.toLowerCase() !== "mso-background"
                )
                .join(" ");
            });
            cleaned = doc.body.innerHTML;
          } catch {
            cleaned = raw
              .replace(/background(?:-color)?\s*:\s*[^;"']+;?/gi, "")
              .replace(
                /\s?class="([^"]*?\s)?bg-[^"\s]+([^"]*?)"/gi,
                (m, p1 = "", p2 = "") =>
                  p1.trim() || p2.trim()
                    ? ` class="${(p1 + " " + p2).trim()}"`
                    : ""
              );
          }

          const textOnly = cleaned.replace(/<[^>]+>/g, "").trim();
          const textLen = textOnly.length;
          const hasContent = textLen > 0;
          const canExpand = textLen > 150;

          // Tách câu đầu tiên ra
          const firstSentenceMatch = textOnly.match(/^(.*?\.)\s/);
          const firstSentence = firstSentenceMatch
            ? firstSentenceMatch[1]
            : textOnly;

          // Phần còn lại (bao gồm HTML gốc trừ câu đầu)
          const restHTML = cleaned.replace(firstSentence, "").trim();

          return (
            <>
              {/* Tiêu đề nổi */}
              {firstSentence && (
                <div className="text-lg font-semibold text-gray-900 mb-3">
                  {firstSentence}
                </div>
              )}

              {/* Nội dung còn lại */}
              <div className="relative">
                <div
                  className={[
                    "desc-content prose prose-sm sm:prose lg:prose-lg max-w-none text-gray-800 leading-relaxed",
                    !showMore ? "max-h-56 overflow-hidden" : "",
                  ].join(" ")}
                  dangerouslySetInnerHTML={{
                    __html:
                      restHTML ||
                      (!firstSentence && hasContent
                        ? cleaned
                        : "<p class='text-gray-500 italic'>Chưa có bài viết cho sản phẩm này.</p>"),
                  }}
                />
                {!showMore && canExpand && (
                  <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-white via-white to-transparent" />
                )}
              </div>

              {/* Nút xem thêm / thu gọn */}
              {canExpand && (
                <div className="text-center mt-6">
                  <button
                    onClick={() => setShowMore(!showMore)}
                    className={`inline-flex items-center gap-2 px-5 py-2 rounded-full font-medium shadow-sm transition-all duration-200
                ${
                  showMore
                    ? "bg-red-500 text-white hover:bg-red-600"
                    : "border border-gray-300 text-gray-700 hover:bg-red-500 hover:text-white"
                }`}
                  >
                    {showMore ? "Thu gọn" : "Xem thêm"}
                    <span
                      className={`transition-transform ${
                        showMore ? "rotate-180" : ""
                      }`}
                    >
                      ▾
                    </span>
                  </button>
                </div>
              )}
            </>
          );
        })()}
      </div>
    </div>
  );
};

export default ProductInfo;
