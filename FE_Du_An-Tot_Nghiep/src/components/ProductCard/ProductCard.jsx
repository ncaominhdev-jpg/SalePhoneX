import React, { useEffect, useMemo } from "react";
import { useBrand } from "../../contexts/BrandShopContext";
import WishlistButton from "../WishlistButton/WishlistButton";
// import { useWishlistContext } from "../../contexts/WishlistContext"; // <- không dùng thì bỏ
import { useReviews } from "../../contexts/ReviewContext";
import { FaStar, FaRegStar } from "react-icons/fa";

const ProductCard = ({ data, product }) => {
  const item = data || product;
  if (!item) return null;

  const { id, name, price, image, brand_id } = item;
  const { brands } = useBrand();
  const brand = brands.find((b) => Number(b.id) === Number(brand_id));

  const { fetchReviews, getAverage, getCount } = useReviews();

  useEffect(() => {
    if (id) fetchReviews(id);
  }, [id, fetchReviews]);

  const avg = getAverage(id);
  const count = getCount(id);

  // Làm tròn và clamp về [0,5] để tránh lỗi hiển thị
  const filledStars = useMemo(() => {
    const n = Math.round(Number.isFinite(avg) ? avg : 0);
    return Math.max(0, Math.min(5, n));
  }, [avg]);

  // Khi chưa có review, cho sao mờ 50% để vẫn “nhìn thấy sao”
  const starWrapClass =
    "flex items-center gap-1 text-yellow-400 text-sm " +
    (count === 0 ? "opacity-50" : "");

  return (
    <div className="w-full rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 bg-white flex flex-col relative group overflow-hidden">
      {/* Hình ảnh */}
      <div className="w-full h-56 flex items-center justify-center overflow-hidden bg-white">
        <img
          src={image}
          alt={name || "product"}
          className="max-h-full max-w-full object-contain group-hover:scale-105 transition-transform duration-300"
          loading="lazy"
        />
      </div>

      {/* Nội dung */}
      <div className="p-3 flex flex-col flex-grow">
        <h3 className="line-clamp-2 min-h-[56px] text-base sm:text-lg md:text-xl font-semibold text-gray-900 leading-snug">
          {name}
        </h3>

        {brand && <span className="text-xs text-gray-500">{brand.name}</span>}

        <div>
          <span className="text-red-600 font-bold text-lg">
            {Math.round(Number(price))?.toLocaleString("vi-VN")}
          </span>
        </div>

        {/* Rating + Wishlist */}
        <div className="px-1 flex justify-between items-center mt-2">
          <div
            className={starWrapClass}
            aria-label={`Đánh giá ${avg || 0} trên 5`}
          >
            {[...Array(5)].map((_, i) =>
              i < filledStars ? (
                <FaStar key={i} className="text-yellow-400" />
              ) : (
                <FaRegStar key={i} className="text-yellow-400" />
              )
            )}
            <span className="text-xs text-gray-500">({count})</span>
          </div>

          <WishlistButton productId={id} />
        </div>
      </div>
    </div>
  );
};

export default ProductCard;
