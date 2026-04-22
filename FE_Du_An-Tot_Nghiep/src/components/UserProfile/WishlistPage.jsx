import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { FaHeart, FaRegHeart } from "react-icons/fa";
import { toSlug } from "../../utils/slug";
import { useWishlist } from "../../hooks/useWishlist";
import { useCategories } from "../../hooks/useCategories";

const WishlistPage = () => {
    const { wishlist, loading, addToWishlist, removeFromWishlist } = useWishlist();
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage, setItemsPerPage] = useState(9);

    const { data: categoriesData } = useCategories();
    const categoryMap = categoriesData?.categoryMap || {};

    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth < 640) setItemsPerPage(4);
            else if (window.innerWidth < 1024) setItemsPerPage(6);
            else setItemsPerPage(6);
        };

        handleResize();
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);

    const totalPages = Math.ceil((wishlist?.length || 0) / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const currentItems = wishlist?.slice(startIndex, startIndex + itemsPerPage);

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center border-b pb-3">
                <h3 className="text-base sm:text-lg font-semibold text-gray-800">
                    Sản phẩm yêu thích
                </h3>
            </div>

            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 min-h-[280px]">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div
                            key={i}
                            className="bg-white border border-gray-200 rounded-xl shadow-sm p-3 flex items-center animate-pulse"
                        >
                            <div className="w-20 h-20 bg-gray-200 rounded-md mr-3"></div>
                            <div className="flex-1 space-y-2">
                                <div className="h-4 bg-gray-300 rounded w-3/4"></div>
                                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                                <div className="h-4 bg-gray-300 rounded w-1/3"></div>
                            </div>
                            <div className="ml-2 w-8 h-8 bg-gray-200 rounded-full"></div>
                        </div>
                    ))}
                </div>
            ) : !wishlist || wishlist.length === 0 ? (
                <div className="text-center py-16 px-4">
                    <FaRegHeart className="text-6xl text-gray-300 mx-auto mb-4" />
                    <h2 className="text-lg font-semibold text-gray-700">
                        Bạn chưa có sản phẩm yêu thích
                    </h2>
                    <Link
                        to="/"
                        className="inline-block mt-6 bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-full font-semibold hover:from-red-600 hover:to-red-700 shadow-md transition transform hover:-translate-y-1"
                    >
                        Mua sắm ngay
                    </Link>
                </div>
            ) : (
                <>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 min-h-[280px]">
                        {currentItems.map((item) => {
                            const product = item.product || {};
                            const isLiked = wishlist.some((w) => w.product_id === product.id);

                            // ✅ Lấy category từ categoryMap bằng category_id
                            const categoryObj = Object.values(categoryMap).find(
                                (c) => c.id === product.category_id
                            );
                            const categorySlug = toSlug(categoryObj?.name || "san-pham");
                            const productSlug = toSlug(product?.name || "san-pham");

                            return (
                                <div
                                    key={item.id}
                                    className="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition p-2 flex items-center relative"
                                >
                                    <Link
                                        to={`/${categorySlug}/${productSlug}`}
                                        className="flex items-center gap-3 flex-1"
                                    >
                                        <img
                                            src={product.image_url || "/fallback.png"}
                                            alt={product.name || "Không có tên"}
                                            className="w-20 h-20 object-contain rounded-md border"
                                            onError={(e) => {
                                                e.target.src = "/fallback.png";
                                            }}
                                        />

                                        <div className="flex flex-col flex-1 min-w-0">
                                            <h3 className="text-sm sm:text-base font-semibold text-gray-900 line-clamp-2 hover:text-red-600 transition">
                                                {product.name || "Không rõ tên"}
                                            </h3>

                                            <div className="mt-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                <span className="text-red-600 font-bold text-sm sm:text-base">
                                                    {product.price
                                                        ? new Intl.NumberFormat("vi-VN", {
                                                            style: "currency",
                                                            currency: "VND",
                                                        }).format(product.price)
                                                        : "Liên hệ"}
                                                </span>
                                                {product.old_price && (
                                                    <span className="line-through text-gray-400 text-xs sm:text-sm">
                                                        {new Intl.NumberFormat("vi-VN", {
                                                            style: "currency",
                                                            currency: "VND",
                                                        }).format(product.old_price)}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </Link>

                                    <button
                                        onClick={() =>
                                            isLiked
                                                ? removeFromWishlist(product.id)
                                                : addToWishlist(product.id)
                                        }
                                        className={`ml-2 p-2 rounded-full transition ${isLiked
                                                ? "text-red-600 hover:bg-red-50"
                                                : "text-gray-400 hover:text-red-500 hover:bg-gray-50"
                                            }`}
                                        title={isLiked ? "Bỏ yêu thích" : "Thêm vào yêu thích"}
                                    >
                                        {isLiked ? (
                                            <FaHeart className="text-lg" />
                                        ) : (
                                            <FaRegHeart className="text-lg" />
                                        )}
                                    </button>
                                </div>
                            );
                        })}
                    </div>

                    {totalPages > 1 && (
                        <div className="flex justify-center gap-2 mt-4">
                            <button
                                onClick={() => setCurrentPage((p) => Math.max(p - 1, 1))}
                                disabled={currentPage === 1}
                                className="px-3 py-1 border rounded text-sm font-medium disabled:opacity-50 hover:bg-gray-100"
                            >
                                ←
                            </button>
                            {Array.from({ length: totalPages }, (_, i) => (
                                <button
                                    key={i}
                                    onClick={() => setCurrentPage(i + 1)}
                                    className={`px-3 py-1 border rounded text-sm font-medium transition ${currentPage === i + 1
                                            ? "bg-red-500 text-white border-red-500"
                                            : "hover:bg-gray-100"
                                        }`}
                                >
                                    {i + 1}
                                </button>
                            ))}
                            <button
                                onClick={() =>
                                    setCurrentPage((p) => Math.min(p + 1, totalPages))
                                }
                                disabled={currentPage === totalPages}
                                className="px-3 py-1 border rounded text-sm font-medium disabled:opacity-50 hover:bg-gray-100"
                            >
                                →
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default WishlistPage;
