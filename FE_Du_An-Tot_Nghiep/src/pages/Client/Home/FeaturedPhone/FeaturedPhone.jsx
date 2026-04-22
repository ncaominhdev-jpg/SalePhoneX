import React, { useState, useEffect } from "react";
import { useAllProducts } from "../../../../hooks/useProducts";
import { useBrands } from "../../../../hooks/useBrands";
import { useCategories } from "../../../../hooks/useCategories";
import { Link } from "react-router-dom";
import { toSlug } from "../../../../utils/slug";
import ProductCard from "../../../../components/ProductCard/ProductCard";

const FeaturedPhone = () => {
  const [selectedBrands, setSelectedBrands] = useState({});
  const [visibleCategories, setVisibleCategories] = useState(5);
  const [visibleProducts, setVisibleProducts] = useState({});

  const { data: allProducts = [], isLoading: loadingProducts } = useAllProducts();
  const { data: brands = [], isLoading: loadingBrands } = useBrands();
  const { data: categoriesData, isLoading: loadingCategories } = useCategories();
  const categories = categoriesData?.categories || [];

  useEffect(() => {
    if (categories.length === 0) return; // chưa có categories thì thôi

    try {
      const savedBrands = JSON.parse(localStorage.getItem("selectedBrands")) || {};
      const updatedBrands = { ...savedBrands };

      categories.forEach((cat) => {
        if (!(cat.id in updatedBrands)) {
          updatedBrands[cat.id] = null; // mặc định là "Tất cả"
        }
      });

      setSelectedBrands(updatedBrands);
    } catch (error) {
      console.error("Lỗi parse localStorage:", error);
      const defaultBrands = {};
      categories.forEach((cat) => {
        defaultBrands[cat.id] = null;
      });
      setSelectedBrands(defaultBrands);
    }
  }, [categories]);

  useEffect(() => {
    if (Object.keys(selectedBrands).length > 0) {
      localStorage.setItem("selectedBrands", JSON.stringify(selectedBrands));
    }
  }, [selectedBrands]);

  if (loadingProducts || loadingBrands || loadingCategories) {
    return (
      <div className="p-6">
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
          {Array.from({ length: 10 }).map((_, idx) => (
            <div
              key={idx}
              className="bg-white rounded-lg shadow-md p-4 border border-gray-200 animate-pulse"
            >
              {/* Ảnh giả */}
              <div className="w-full h-36 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded-md mb-3 relative overflow-hidden">
                <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
              </div>

              {/* Tên sản phẩm giả */}
              <div className="h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded mb-2 relative overflow-hidden">
                <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
              </div>

              <div className="h-4 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 rounded w-2/3 relative overflow-hidden">
                <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  const handleBrandClick = (categoryId, brandId) => {
    setSelectedBrands((prev) => ({
      ...prev,
      [categoryId]: brandId,
    }));
    setVisibleProducts((prev) => ({
      ...prev,
      [categoryId]: 10,
    }));
  };

  const handleSeeMoreCategory = () => {
    setVisibleCategories((prev) => Math.min(prev + 2, 10));
  };

  const handleCollapseCategory = () => {
    setVisibleCategories(5);
  };

  const handleSeeMoreProducts = (categoryId, totalProducts) => {
    setVisibleProducts((prev) => ({
      ...prev,
      [categoryId]: Math.min((prev[categoryId] || 10) + 5, totalProducts),
    }));
  };

  return (
    <div className="p-4">
      {categories.slice(0, visibleCategories).map((category) => {
        const selectedBrandId = selectedBrands[category.id] ?? null;

        let categoryProducts = allProducts.filter(
          (p) => p.category_id === category.id
        );

        if (selectedBrandId !== null) {
          categoryProducts = categoryProducts.filter(
            (p) => p.brand_id === selectedBrandId
          );
        }

        const categoryBrands = brands.filter((brand) =>
          allProducts.some(
            (p) => p.category_id === category.id && p.brand_id === brand.id
          )
        );

        const visibleCount = visibleProducts[category.id] || 10;

        return (
          <div key={category.id} className="mb-10">
            <div className="mb-6">
              <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                {/* Tiêu đề danh mục */}
                <h1 className="text-xl sm:text-2xl font-bold text-gray-900 text-center sm:text-left flex items-center gap-2">
                  <span className="w-1.5 h-6 bg-red-600 rounded-full"></span>
                  {category.name}
                </h1>

                {/* Danh sách brand */}
                <div className="flex overflow-x-auto gap-3 pb-2 sm:pb-0 scrollbar-hide">
                  {/* Tất cả */}
                  <button
                    onClick={() => handleBrandClick(category.id, null)}
                    className={`flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium transition-all duration-200 
                    ${selectedBrandId === null
                        ? "bg-gradient-to-r from-red-600 to-red-500 text-white shadow-md"
                        : "bg-gray-100 text-gray-700 hover:bg-red-50 hover:text-red-600 border border-gray-200"}
                    `}
                  >
                    Tất cả
                  </button>

                  {/* Thương hiệu */}
                  {categoryBrands.map((brand) => (
                    <button
                      key={brand.id}
                      onClick={() => handleBrandClick(category.id, brand.id)}
                      className={`flex-shrink-0 px-5 py-2 rounded-full text-sm font-medium transition-all duration-200 
                      ${selectedBrandId === brand.id
                          ? "bg-gradient-to-r from-red-600 to-red-500 text-white shadow-md"
                          : "bg-gray-100 text-gray-700 hover:bg-red-50 hover:text-red-600 border border-gray-200"}
                      `}
                    >
                      {brand.name}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            {/* Grid sản phẩm */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
              {categoryProducts.slice(0, visibleCount).map((product) => (
                <Link
                  key={product.id}
                  to={`/${toSlug(category.name)}/${toSlug(product.name)}`}
                  className="block"
                >
                  <ProductCard data={product} />
                </Link>
              ))}
            </div>

          </div>
        );
      })}

      <div className="text-center mt-6 flex justify-center gap-4">
        {visibleCategories < Math.min(categories.length, 10) && (
          <button
            onClick={handleSeeMoreCategory}
            className="px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white font-semibold rounded-full shadow-md hover:shadow-lg hover:from-red-600 hover:to-red-700 transform hover:-translate-y-0.5 transition duration-300"
          >
            Xem thêm
          </button>
        )}
        {visibleCategories > 5 && (
          <button
            onClick={handleCollapseCategory}
            className="px-6 py-2 bg-gradient-to-r from-gray-200 to-gray-300 text-gray-700 font-semibold rounded-full shadow-md hover:shadow-lg hover:from-gray-300 hover:to-gray-400 transform hover:-translate-y-0.5 transition duration-300"
          >
            Thu gọn
          </button>
        )}
      </div>
    </div>
  );
};

export default FeaturedPhone;
