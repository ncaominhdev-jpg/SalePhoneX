import { useParams, Link, Navigate } from "react-router-dom";
import { useState } from "react";
import BrandList from "./BrandList/BrandList";
import FilterTabs from "./FilterTabs/FilterTabs";
import ProductList from "./ProductList/ProductList";
import BannerSlider from "../../../components/BannerSlider/BannerSlider";
import { HomeIcon } from "@heroicons/react/24/solid";
import { useCategories } from "../../../hooks/useCategories";
import { useAllProducts } from "../../../hooks/useProducts";

const Shop = () => {
  const { slug } = useParams();

  const { data: categoriesData, isLoading: loadingCategories } = useCategories();
  const category = categoriesData?.categoryMap[slug];
  const displayName = category?.name || "Danh mục";

  const { data: allProducts = [], isLoading: loadingProducts } = useAllProducts();

  const products = category
    ? allProducts.filter((p) => Number(p.category_id) === Number(category.id))
    : [];

  const [minPrice, setMinPrice] = useState(0);
  const [maxPrice, setMaxPrice] = useState(50000000);
  const [selectedOptions, setSelectedOptions] = useState({});
  const [selectedBrandId, setSelectedBrandId] = useState(null);

  // ✅ Thêm check loading
  if (loadingCategories || loadingProducts) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50">
        {/* Spinner */}
        <div className="w-12 h-12 border-4 border-red-500 border-t-transparent rounded-full animate-spin"></div>
        <p className="mt-4 text-gray-600 text-sm sm:text-base animate-pulse">
          Đang tải sản phẩm, vui lòng chờ...
        </p>

        {/* Skeleton Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-8 w-full max-w-6xl px-4">
          {Array.from({ length: 8 }).map((_, idx) => (
            <div
              key={idx}
              className="bg-white shadow rounded-lg p-4 animate-pulse"
            >
              <div className="w-full h-40 bg-gray-200 rounded-md mb-4"></div>
              <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
              <div className="h-4 bg-gray-200 rounded w-1/2"></div>
            </div>
          ))}
        </div>
      </div>
    );
  }


  if (!category) {
    return <Navigate to="/404" replace />;
  }

  return (
    <div className="max-w-7xl mx-auto px-4 pt-4">
      {/* Breadcrumb */}
      <div className="flex items-center text-sm text-gray-600 mb-4">
        <Link to="/" className="flex items-center hover:text-red-500">
          <HomeIcon className="w-4 h-4 mr-1" />
          Trang chủ
        </Link>
        <span className="mx-2">/</span>
        <Link to={`/${slug}`} className="text-red-600 font-medium hover:underline">
          {displayName}
        </Link>
      </div>

      {/* Banner */}
      <BannerSlider />

      {/* Danh sách thương hiệu */}
      <BrandList
        categoryId={category?.id}
        selectedBrandId={selectedBrandId}
        onSelect={setSelectedBrandId}
      />

      {/* Bộ lọc */}
      <h2 className="text-sm sm:text-xl font-bold text-gray-800 mt-4 mb-4">
        Chọn theo tiêu chí
      </h2>
      <FilterTabs
        products={products}
        minPrice={minPrice}
        maxPrice={maxPrice}
        setMinPrice={setMinPrice}
        setMaxPrice={setMaxPrice}
        selectedOptions={selectedOptions}
        setSelectedOptions={setSelectedOptions}
      />

      {/* Danh sách sản phẩm */}
      <ProductList
        products={products}
        categoryId={category?.id}
        brandId={selectedBrandId}
        minPrice={minPrice}
        maxPrice={maxPrice}
        selectedOptions={selectedOptions}
        displayName={displayName}
      />
    </div>
  );
};

export default Shop;
