import React, { useState, useEffect, useMemo } from "react";
import { toast } from "react-toastify";
import constants from "../../../constants/constants";
import { useNavigate } from "react-router-dom";
import { CheckCircle } from "lucide-react";
import WishlistButton from "../../../components/WishlistButton/WishlistButton";
const SpecRow = ({ label, value }) => (
  <tr className="border-t">
    <td className="p-3 font-medium bg-gray-50 w-1/3">{label}</td>
    <td className="p-3">{value}</td>
  </tr>
);

const MAX_THUMBS = 7;

const ProductGallery = ({
  productDetail,
  selectedImage,
  setSelectedImage,
  productAttributes,
  selectedVariant,
  setSelectedVariant,
  availableProducts,
  cartItems,
}) => {
  const navigate = useNavigate();
  const [showSpecs, setShowSpecs] = useState(false);
  const [selectedThumbnail, setSelectedThumbnail] = useState(null);
  const isMobile = typeof window !== "undefined" && window.innerWidth < 768;

  // Giá & giảm giá từ variant / product
  const basePrice = Number(selectedVariant?.price ?? productDetail?.price ?? 0);
  const discount = Number(
    selectedVariant?.discount ?? productDetail?.discount ?? 0
  );
  const finalPrice = Math.max(0, basePrice - discount);
  const hasDiscount = discount > 0 && basePrice > finalPrice;

  const fmt = (n) => new Intl.NumberFormat("vi-VN").format(n);

  // --- Thumbnail carousel state ---
  const images = productDetail?.images || [];
  const [thumbStart, setThumbStart] = useState(0); // index ảnh đầu tiên đang hiển thị

  const canPrev = thumbStart > 0;
  const canNext = thumbStart + MAX_THUMBS < images.length;
  const visibleThumbs = images.slice(thumbStart, thumbStart + MAX_THUMBS);

  const clampThumbStart = (val) =>
    Math.max(0, Math.min(val, Math.max(0, images.length - MAX_THUMBS)));

  const goPrev = () => setThumbStart((s) => clampThumbStart(s - MAX_THUMBS));
  const goNext = () => setThumbStart((s) => clampThumbStart(s + MAX_THUMBS));

  const ensureThumbVisible = (index) => {
    if (index < thumbStart) {
      setThumbStart(clampThumbStart(index));
    } else if (index >= thumbStart + MAX_THUMBS) {
      setThumbStart(clampThumbStart(index - (MAX_THUMBS - 1)));
    }
  };

  // === Index ảnh chính để điều hướng bằng mũi tên trên ảnh ===
  const [selectedIndex, setSelectedIndex] = useState(0);

  // Đồng bộ selectedImage -> selectedIndex khi ảnh hoặc danh sách ảnh đổi
  useEffect(() => {
    if (!images.length) return;
    const idx = images.indexOf(selectedImage);
    setSelectedIndex(idx >= 0 ? idx : 0);
  }, [selectedImage, images]);

  const handlePrevMain = () => {
    if (!images.length) return;
    const next = (selectedIndex - 1 + images.length) % images.length;
    setSelectedIndex(next);
    setSelectedImage(images[next]);
    setSelectedThumbnail(images[next]);
    ensureThumbVisible(next);
  };

  const handleNextMain = () => {
    if (!images.length) return;
    const next = (selectedIndex + 1) % images.length;
    setSelectedIndex(next);
    setSelectedImage(images[next]);
    setSelectedThumbnail(images[next]);
    ensureThumbVisible(next);
  };

  // ✅ Tính số lượng tồn kho (memoized)
  const stockQty = useMemo(() => {
    if (!selectedVariant) return 0;
    return availableProducts
      .filter((p) => +p.variant_id === +selectedVariant.id)
      .reduce((sum, p) => sum + (p.quantity || 0), 0);
  }, [selectedVariant, availableProducts]);

  const isOutOfStock = selectedVariant && stockQty <= 0;

  // ✅ Đồng bộ ảnh chính và thumbnail khi có productDetail hoặc chọn variant
  useEffect(() => {
    if (selectedVariant?.img) {
      const url = `${constants.BASE_DOMAIN}/storage/${selectedVariant.img}`;
      setSelectedImage(url);
      setSelectedThumbnail(url);
    } else if (productDetail?.image) {
      setSelectedImage(productDetail.image);
      setSelectedThumbnail(productDetail.image);
    }
  }, [selectedVariant, productDetail, setSelectedImage]);

  // Khi list ảnh thay đổi, đảm bảo thumbStart hợp lệ
  useEffect(() => {
    setThumbStart((s) => clampThumbStart(s));
  }, [images.length]);

  // ✅ Thêm vào giỏ hàng
  const handleAddToCart = async () => {
    if (!selectedVariant) {
      toast.error("Vui lòng chọn phiên bản trước khi thêm vào giỏ hàng");
      return;
    }
    if (isOutOfStock) {
      toast.error("Phiên bản này hiện không còn hàng.");
      return;
    }

    const cartItem = cartItems.find(
      (item) => +item.product_variant_id === +selectedVariant.id
    );
    const currentQty = cartItem ? cartItem.quantity : 0;

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

  // ✅ Mua ngay
  const handleBuyNow = () => {
    if (!selectedVariant) {
      toast.error("Vui lòng chọn phiên bản trước khi mua ngay");
      return;
    }
    const buyNowItem = [
      {
        id: Date.now(),
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
    navigate("/checkout");
  };

  return (
    <div className="w-full md:w-5/5 space-y-6">
      {/* Ảnh chính + mũi tên điều hướng */}
      <div className="rounded-xl border p-2 bg-white shadow-md">
        <div className="relative w-full pt-[60%] overflow-hidden rounded-lg">
          <img
            src={selectedImage}
            alt="Ảnh sản phẩm"
            className="absolute top-0 left-0 w-full h-full object-contain transition-transform duration-300 hover:scale-105"
          />
          {/* Arrow left */}
          {images.length > 1 && (
            <button
              onClick={handlePrevMain}
              className="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white shadow-md flex items-center justify-center"
              aria-label="Ảnh trước"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
          )}

          {/* Arrow right */}
          {images.length > 1 && (
            <button
              onClick={handleNextMain}
              className="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white shadow-md flex items-center justify-center"
              aria-label="Ảnh sau"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          )}
        </div>
      </div>

      {/* ẢNH PHỤ: 7 thumbnail/1 hàng, mũi tên + gradient, kích thước nhỏ gọn */}
      {selectedImage && images.length > 0 && (
        <div className="mt-3">
          <div className="relative w-full">
            {/* Gradient mờ hai bên */}
            <div className="pointer-events-none absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white to-transparent rounded-l-xl" />
            <div className="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white to-transparent rounded-r-xl" />

            {/* Nút trái */}
            {images.length > MAX_THUMBS && (
              <button
                onClick={goPrev}
                disabled={!canPrev}
                className={`absolute left-0 top-1/2 -translate-y-1/2 z-20 rounded-full border w-7 h-7 flex items-center justify-center bg-white shadow
                  ${
                    canPrev
                      ? "hover:bg-gray-50"
                      : "opacity-40 cursor-not-allowed"
                  }`}
                aria-label="Previous thumbnails"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M15 19l-7-7 7-7"
                  />
                </svg>
              </button>
            )}

            {/* Lưới 7 ô – luôn chiếm 100% ngang, có đệm khi thiếu */}
            <div className="w-full">
              <div className="grid grid-cols-7 gap-2 items-center">
                {visibleThumbs.map((img, idx) => {
                  const realIndex = thumbStart + idx;
                  const active =
                    selectedThumbnail === img || selectedImage === img;
                  return (
                    <button
                      key={realIndex}
                      onClick={() => {
                        setSelectedImage(img);
                        setSelectedThumbnail(img);
                        ensureThumbVisible(realIndex);
                      }}
                      className={`w-full h-15 border rounded-xl p-[3px] transition bg-white
                        ${
                          active
                            ? "border-red-500 ring-2 ring-red-200"
                            : "border-gray-300 hover:border-gray-400"
                        }`}
                      title={`Ảnh ${realIndex + 1}`}
                    >
                      <div className="w-full h-full rounded-lg overflow-hidden">
                        <img
                          src={img}
                          alt={`Ảnh phụ ${realIndex + 1}`}
                          className="w-full h-full object-cover"
                        />
                      </div>
                    </button>
                  );
                })}

                {/* Đệm ô trống để đủ 7 cột -> không hở đuôi */}
                {Array.from({
                  length: Math.max(0, MAX_THUMBS - visibleThumbs.length),
                }).map((_, i) => (
                  <div
                    key={`pad-${i}`}
                    className="w-full h-15 rounded-xl border border-transparent"
                    aria-hidden
                  />
                ))}
              </div>
            </div>

            {/* Nút phải */}
            {images.length > MAX_THUMBS && (
              <button
                onClick={goNext}
                disabled={!canNext}
                className={`absolute right-0 top-1/2 -translate-y-1/2 z-20 rounded-full border w-7 h-7 flex items-center justify-center bg-white shadow
                  ${
                    canNext
                      ? "hover:bg-gray-50"
                      : "opacity-40 cursor-not-allowed"
                  }`}
                aria-label="Next thumbnails"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  className="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </button>
            )}
          </div>
        </div>
      )}

      {/* Mobile layout (giá) */}
      <div className="mt-4 block md:hidden space-y-4">
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
        </div>

        <div className="space-y-4 md:hidden">
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
                              ? "border-red-500 shadow-md cursor-pointer"
                              : "border-gray-200 bg-white hover:border-red-400 cursor-pointer"
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

        {/* Nút hành động */}
        <div className="flex flex-col sm:flex-row flex-wrap gap-2">
          <div className="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-red-600 bg-white text-red-600 font-semibold hover:bg-red-50 hover:text-red-700 transition-all duration-200">
            <WishlistButton
              productId={selectedVariant?.product_id || productDetail?.id}
            />
          </div>
          <button
            onClick={handleBuyNow}
            className="bg-red-600 text-center text-white rounded-lg px-4 py-2 font-bold hover:bg-red-700 transition flex-1 w-full sm:w-auto sm:text-center"
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
      </div>

      {/* Thông số kỹ thuật */}
      {productAttributes.length > 0 && (
        <div className="mt-6">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-lg font-semibold">Thông số kỹ thuật</h2>
            {isMobile && (
              <button
                onClick={() => setShowSpecs((prev) => !prev)}
                className="text-gray-600 text-sm font-medium"
              >
                {showSpecs ? "Thu gọn" : "Xem thêm"}
              </button>
            )}
          </div>
          <div className="overflow-x-auto rounded-lg shadow-sm border transition-all duration-500 ease-in-out">
            <table className="w-full text-sm text-left border-collapse">
              <tbody>
                {(showSpecs || !isMobile
                  ? productAttributes
                  : productAttributes.slice(0, 3)
                ).map((attr) => (
                  <SpecRow
                    key={attr.attribute_id}
                    label={attr.attribute_name}
                    value={attr.value}
                  />
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductGallery;
