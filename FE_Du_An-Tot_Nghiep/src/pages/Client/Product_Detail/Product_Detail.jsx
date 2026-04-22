// src/pages/ProductDetail/components/ProductDetail.jsx
import React, { useState, useEffect, useContext } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { Home } from "lucide-react";
import { ProductContext } from "../../../contexts/ProductHomeContext";
import { useCategories } from "../../../hooks/useCategories";
import { useAvailableProducts } from "../../../hooks/useAvailableProducts";
import { useCart } from "../../../contexts/CartContext";
import ProductGallery from "./ProductGallery";
import ProductInfo from "./ProductInfo";
import Comment from "./Comment";
import RelatedProducts from "./RelatedProducts";
import { toSlug } from "../../../utils/slug";
import { toast } from "react-toastify";
import Review from "./Review";

const ProductDetail = () => {
  const { categorySlug, productSlug } = useParams();

  // id hiện tại để dùng cho Comment/Review
  const [productId, setProductId] = useState(null);

  // cờ loading cho giai đoạn "đang tra ID từ slug"
  const [slugLoading, setSlugLoading] = useState(false);

  const {
    productDetail,
    fetchProductById,
    fetchAttributesByProductId,
    fetchAllProducts,
    productAttributes,
    loadingDetail, // loading khi gọi fetchProductById
  } = useContext(ProductContext);

  const { data: categoriesData } = useCategories();
  const { availableProducts } = useAvailableProducts();
  const { cartItems } = useCart();

  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedImage, setSelectedImage] = useState(null);
  const [relatedProducts, setRelatedProducts] = useState([]);
  const navigate = useNavigate();

  const category = categoriesData?.categoryMap?.[categorySlug];
  const displayName = category?.name || "Danh mục";

  // Khi slug đổi: reset state hiển thị ngay lập tức để tránh show dữ liệu cũ
  useEffect(() => {
    setSlugLoading(true);
    setSelectedVariant(null);
    setSelectedImage(null);
    setProductId(null);
  }, [productSlug]);

  // Lấy ID từ slug & fetch chi tiết (tránh race bằng cờ alive)
  useEffect(() => {
    let alive = true;

    const fetchBySlug = async () => {
      if (!productSlug) {
        setSlugLoading(false);
        return;
      }
      try {
        const all = await fetchAllProducts();
        if (!alive) return;

        const found = all.find((p) => toSlug(p.name) === productSlug);
        if (!found) {
          toast.error("Không tìm thấy sản phẩm");
          setSlugLoading(false);
          return;
        }

        setProductId(found.id);

        // gọi song song để nhanh hơn
        await Promise.all([
          fetchProductById(found.id),
          fetchAttributesByProductId(found.id),
        ]);
      } catch (err) {
        if (alive) toast.error("Lỗi khi tải sản phẩm");
      } finally {
        if (alive) setSlugLoading(false);
      }
    };

    fetchBySlug();
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [productSlug]); // chỉ phụ thuộc slug để tránh refetch không cần thiết

  // Đặt biến thể mặc định khi productDetail đổi
  useEffect(() => {
    if (productDetail?.product_variants?.length > 0) {
      setSelectedVariant(productDetail.product_variants[0]);
    } else {
      setSelectedVariant(null);
    }
  }, [productDetail?.id]);

  // Đặt ảnh mặc định khi productDetail đổi
  useEffect(() => {
    if (productDetail?.images?.length > 0) {
      setSelectedImage(productDetail.images[0]);
    } else if (productDetail?.image) {
      setSelectedImage(productDetail.image);
    } else {
      setSelectedImage(null);
    }
  }, [productDetail?.id]);

  // Sản phẩm liên quan: theo category + ngẫu nhiên 5 sản phẩm
  useEffect(() => {
    let alive = true;
    const run = async () => {
      if (!productDetail) {
        setRelatedProducts([]);
        return;
      }
      const data = await fetchAllProducts();
      if (!alive) return;

      const filtered = (data || []).filter(
        (p) =>
          p.id !== productDetail.id &&
          p.category_id === productDetail.category_id
      );
      const shuffled = filtered.sort(() => Math.random() - 0.5);
      setRelatedProducts(shuffled.slice(0, 5));
    };
    run();
    return () => {
      alive = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [productDetail?.id, productDetail?.category_id]);

  // Skeleton khi đang tra slug, đang tải chi tiết hoặc chưa có dữ liệu
  if (slugLoading || loadingDetail || !productDetail) {
    return (
      <div className="p-6 max-w-7xl mx-auto space-y-8 animate-pulse">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Hình ảnh sản phẩm */}
          <div className="w-full lg:w-1/2 space-y-4">
            <div className="w-full h-[400px] bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-lg relative overflow-hidden">
              <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
            </div>
            <div className="flex gap-3">
              {Array.from({ length: 4 }).map((_, idx) => (
                <div
                  key={idx}
                  className="w-20 h-20 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-md relative overflow-hidden"
                >
                  <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
                </div>
              ))}
            </div>
          </div>

          {/* Thông tin sản phẩm */}
          <div className="w-full lg:w-1/2 space-y-5">
            <div className="h-8 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded w-3/4 relative overflow-hidden">
              <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
            </div>
            <div className="h-10 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded w-1/2 relative overflow-hidden">
              <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
            </div>
            <div className="flex gap-3">
              {Array.from({ length: 2 }).map((_, idx) => (
                <div
                  key={idx}
                  className="w-24 h-14 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-lg relative overflow-hidden"
                >
                  <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
                </div>
              ))}
            </div>
            <div className="h-12 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-lg w-full sm:w-2/3 relative overflow-hidden">
              <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
            </div>
            <div className="space-y-3">
              {Array.from({ length: 3 }).map((_, idx) => (
                <div
                  key={idx}
                  className="h-16 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-lg relative overflow-hidden"
                >
                  <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/50 to-transparent"></div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center text-sm text-gray-600 mb-4">
        <Link to="/" className="flex items-center hover:text-red-500">
          <Home className="w-4 h-4 mr-1" />
          Trang chủ
        </Link>
        {category && (
          <>
            <span className="mx-2">/</span>
            <Link to={`/${categorySlug}`} className="hover:text-red-500">
              {displayName}
            </Link>
          </>
        )}
        <span className="mx-2">/</span>
        <span
          className="
            text-red-600 font-medium 
            text-base
            block max-w-[190px] sm:max-w-none truncate
          "
        >
          {productDetail?.name || "Đang tải..."}
        </span>
      </div>

      <h1 className="text-2xl font-bold">{productDetail?.name}</h1>

      {/* Gallery + Info */}
      <div className="flex flex-col md:flex-row md:flex-nowrap items-start gap-6">
        <div className="w-full md:w-1/2 min-w-0">
          <ProductGallery
            productDetail={productDetail}
            selectedImage={selectedImage}
            setSelectedImage={setSelectedImage}
            selectedVariant={selectedVariant}
            productAttributes={productAttributes}
            setSelectedVariant={setSelectedVariant}
            availableProducts={availableProducts}
            cartItems={cartItems}
          />
        </div>

        <div className="w-full md:flex-1 min-w-0">
          <ProductInfo
            selectedVariantId={selectedVariant?.id}
            productDetail={productDetail}
            selectedVariant={selectedVariant}
            setSelectedVariant={setSelectedVariant}
            availableProducts={availableProducts}
            cartItems={cartItems}
            setSelectedImage={setSelectedImage}
          />
        </div>
      </div>

      {/* Related */}
      <RelatedProducts relatedProducts={relatedProducts} />

      {/* Đánh giá */}
      <Review productId={productId} />

      {/* Comment */}
      <Comment productId={productId} />
    </div>
  );
};

export default ProductDetail;
