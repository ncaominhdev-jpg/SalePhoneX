import React from "react";
import { FaHeart } from "react-icons/fa";
import { useWishlistContext } from "../../contexts/WishlistContext";

const WishlistButton = ({ productId }) => {
    const { wishlist, addToWishlist, removeFromWishlist } = useWishlistContext();

    // Kiểm tra sản phẩm đã có trong wishlist chưa
    const isInWishlist = wishlist.some((w) => w.product_id === productId);

    return (
        <button
            onClick={(e) => {
                e.preventDefault();
                isInWishlist
                    ? removeFromWishlist(productId)
                    : addToWishlist(productId);
            }}
            className="flex items-center gap-1 group"
            title={isInWishlist ? "Bỏ khỏi yêu thích" : "Thêm vào yêu thích"}
        >
            <FaHeart
                size={18}
                className={`transition-colors duration-150 ${isInWishlist
                    ? "text-red-600"
                    : "text-gray-400 group-hover:text-red-600 group-hover:animate-pulse"
                    }`}
            />
            <span
                className={`hidden lg:inline text-xs font-medium transition-colors duration-150 ${isInWishlist
                        ? "text-red-600"
                        : "text-gray-500 group-hover:text-red-600 group-hover:animate-pulse"
                    }`}
            >
                {isInWishlist ? "Đã thích" : "Yêu thích"}
            </span>

        </button>
    );
};

export default WishlistButton;
