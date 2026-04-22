import React, { useEffect } from "react";
import { useBrands } from "../../../../hooks/useBrands";
import { useAllProducts } from "../../../../hooks/useProducts";

const BrandList = ({ categoryId, selectedBrandId, onSelect }) => {
  const { data: brands = [], isLoading: loadingBrands } = useBrands();
  const { data: allProducts = [], isLoading: loadingProducts } = useAllProducts();

  // 🔹 Khi load lại trang: lấy brandId đã lưu trong localStorage
  useEffect(() => {
    const savedBrands = JSON.parse(localStorage.getItem("selectedBrands")) || {};
    if (savedBrands[categoryId] && onSelect) {
      onSelect(savedBrands[categoryId]);
    }
  }, [categoryId, onSelect]);

  // 🔹 Khi user chọn brand: lưu vào localStorage
  const handleClick = (brandId) => {
    const newBrandId = brandId === selectedBrandId ? null : brandId;

    if (onSelect) {
      onSelect(newBrandId);
    }

    const savedBrands = JSON.parse(localStorage.getItem("selectedBrands")) || {};
    savedBrands[categoryId] = newBrandId;
    localStorage.setItem("selectedBrands", JSON.stringify(savedBrands));
  };

  if (loadingBrands || loadingProducts) {
    return (
      <div className="animate-pulse">
        <h1 className="text-sm sm:text-xl font-bold text-gray-800 mb-4">
          Thương Hiệu
        </h1>
        <div className="flex md:hidden overflow-x-auto gap-3 py-2">
          {Array.from({ length: 5 }).map((_, idx) => (
            <div key={idx} className="flex-shrink-0 w-[100px] h-[60px] bg-gray-200 rounded-lg"></div>
          ))}
        </div>
        <div className="hidden md:grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-4">
          {Array.from({ length: 10 }).map((_, idx) => (
            <div key={idx} className="w-[100px] h-[60px] bg-gray-200 rounded-lg"></div>
          ))}
        </div>
      </div>
    );
  }

  const brandIdsInCategory = new Set(
    allProducts
      .filter((p) => Number(p.category_id) === Number(categoryId))
      .map((p) => p.brand_id)
  );
  const filteredBrands = brands.filter((b) => brandIdsInCategory.has(b.id));

  const renderBrandMobile = () => (
    <div className="flex md:hidden overflow-x-auto gap-3 py-2">
      {filteredBrands.map((brand) => (
        <div
          key={brand.id}
          onClick={() => handleClick(brand.id)}
          className={`flex-shrink-0 border rounded-lg overflow-hidden cursor-pointer transition-transform duration-300 ease-in-out flex items-center justify-center p-2 w-[100px]
            ${brand.id === selectedBrandId
              ? "border-red-500 ring-2 ring-red-500"
              : "border-gray-200 bg-white hover:shadow-md"}`}
        >
          <img
            src={brand.image}
            alt={brand.name}
            className="h-[45px] object-contain mx-auto"
          />
        </div>
      ))}
    </div>
  );

  const renderBrandDesktop = () => (
    <div className="hidden md:grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-4">
      {filteredBrands.map((brand) => (
        <div
          key={brand.id}
          onClick={() => handleClick(brand.id)}
          className={`border rounded-lg overflow-hidden cursor-pointer transition-transform duration-300 ease-in-out flex items-center justify-center p-2
            ${brand.id === selectedBrandId
              ? "border-red-500 ring-2 ring-red-500"
              : "border-gray-300 bg-white hover:shadow-xl hover:-translate-y-1"}`}
        >
          <img
            src={brand.image}
            alt={brand.name}
            className="w-[100px] h-[45px] object-contain"
          />
        </div>
      ))}
    </div>
  );

  return (
    <>
      <h1 className="text-sm sm:text-xl font-bold text-gray-800 mb-4">Thương Hiệu</h1>
      {filteredBrands.length === 0 ? (
        <p className="text-center text-gray-500 italic">
          Không có thương hiệu nào trong danh mục này.
        </p>
      ) : (
        <>
          {renderBrandMobile()}
          {renderBrandDesktop()}
        </>
      )}
    </>
  );
};

export default BrandList;
