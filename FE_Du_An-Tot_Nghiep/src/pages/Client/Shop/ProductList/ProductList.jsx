import { useState, useEffect } from "react";
import { useAllProducts } from "../../../../hooks/useProducts";
import { useCity } from "../../../../contexts/CityContext";
import { useAvailableProducts } from "../../../../hooks/useAvailableProducts";
import SortBar from "../../../../components/SortBar/SortBar";
import ProductCard from "../../../../components/ProductCard/ProductCard";
import { useAttributes } from "../../../../hooks/useAttributes";
import { Link } from "react-router-dom";
import { toSlug } from "../../../../utils/slug";
import { useCategories } from "../../../../hooks/useCategories";
import { FaSpinner, FaArrowLeft, FaArrowRight, FaBoxOpen } from "react-icons/fa";

const PRODUCTS_PER_PAGE = 20;

const ProductList = ({ categoryId, displayName, brandId, minPrice = 0, maxPrice = 50000000, selectedOptions = {} }) => {
  const { data: categoriesData } = useCategories();
  const { data: allProducts = [], isLoading } = useAllProducts();
  const { selectedCity } = useCity();
  const { availableProducts } = useAvailableProducts();
  const { data, isLoading: loadingAttributes } = useAttributes();
  const attributes = data?.attributes || [];
  const attributeValues = data?.attributeValues || [];

  const [sortType, setSortType] = useState("default");
  const [currentPage, setCurrentPage] = useState(1);
  const [filteredProducts, setFilteredProducts] = useState([]);

  const filterByBrand = function (products) {
    return brandId != null
      ? products.filter((p) => Number(p.brands_id) === Number(brandId))
      : products;
  };

  const filterByPrice = function (products) {
    return products.filter((p) => p.price >= minPrice && p.price <= maxPrice);
  };

  const filterByAvailability = function (products) {
    if (selectedOptions["Sẵn hàng"]?.includes("Còn hàng")) {
      const productIdsInStock = [...new Set(availableProducts.map((p) => Number(p.product_id)))];
      return products.filter((p) => productIdsInStock.includes(Number(p.id)));
    }
    return products;
  };

  const filterByAttributes = function (products) {
    return products.filter(function (product) {
      return Object.entries(selectedOptions)
        .filter(function ([name, values]) {
          return values.length > 0 && name !== "Sẵn hàng";
        })
        .every(function ([attributeName, values]) {
          const attr = attributes.find((a) => a.name === attributeName);
          if (!attr) return false;
          return attributeValues.some(
            (av) =>
              av.product_id === product.id &&
              av.attribute_id === attr.id &&
              values.includes(av.value)
          );
        });
    });
  };

  useEffect(() => {
    if (!allProducts || allProducts.length === 0) return;
    if (loadingAttributes) return;

    let result = categoryId
      ? allProducts.filter((p) => Number(p.category_id) === Number(categoryId))
      : [...allProducts];

    result = filterByBrand(result);
    result = filterByPrice(result);
    result = filterByAvailability(result);
    result = filterByAttributes(result);

    setFilteredProducts(result);
  }, [
    categoryId,
    brandId,
    minPrice,
    maxPrice,
    selectedOptions,
    availableProducts,
    attributes,
    attributeValues,
    allProducts,
    loadingAttributes,
  ]);

  const sortedProducts = [...filteredProducts].sort(function (a, b) {
    if (sortType === "price-desc") return b.price - a.price;
    if (sortType === "price-asc") return a.price - b.price;
    return 0;
  });

  const totalPages = Math.ceil(sortedProducts.length / PRODUCTS_PER_PAGE);

  const paginatedProducts = sortedProducts.slice(
    (currentPage - 1) * PRODUCTS_PER_PAGE,
    currentPage * PRODUCTS_PER_PAGE
  );

  //Hàm render 1 sản phẩm
  function renderProductItem(item) {
    const categoryObj = categoriesData?.categories.find(
      (c) => Number(c.id) === Number(item.category_id)
    );
    const categorySlug = categoryObj ? categoryObj.name : "san-pham";

    return (
      <Link
        key={item.id}
        to={`/${toSlug(categorySlug)}/${toSlug(item.name)}`}
        className="block"
      >
        <ProductCard data={item} />
      </Link>
    );
  }

  return (
    <div className="w-full mx-auto px-2 pt-4 space-y-4 text-sm sm:text-base">
      <SortBar
        sortType={sortType}
        setSortType={function (type) {
          setSortType(type);
          setCurrentPage(1);
        }}
        displayName={displayName}
      />

      {isLoading || loadingAttributes ? (
        <div className="py-6 animate-pulse">
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {Array.from({ length: 10 }).map((_, idx) => (
              <div key={idx} className="bg-white rounded-lg shadow border border-gray-200 p-4">
                {/* ảnh giả */}
                <div className="w-full h-40 bg-gray-200 rounded-md mb-3"></div>
                {/* tên sản phẩm giả */}
                <div className="h-4 bg-gray-200 rounded mb-2"></div>
                <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                {/* giá giả */}
                <div className="h-5 bg-gray-300 rounded mt-3 w-1/2"></div>
              </div>
            ))}
          </div>
          <div className="flex justify-center mt-8">
            <FaSpinner className="animate-spin text-red-500 text-3xl" />
          </div>
        </div>
      ) : sortedProducts.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-10 text-center">
          <FaBoxOpen className="text-gray-400 text-6xl mb-3" />
          <p className="text-lg text-gray-500 font-medium">Không tìm thấy sản phẩm nào.</p>
          <p className="text-sm text-gray-400 mt-1">
            Hãy thử chọn bộ lọc khác hoặc quay lại trang trước.
          </p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            {paginatedProducts.map(renderProductItem)}
          </div>

          <div className="flex flex-wrap justify-center gap-2 mt-6">
            <button
              onClick={function () { setCurrentPage(currentPage - 1); }}
              disabled={currentPage === 1}
              className="flex items-center gap-1 px-3 py-1 border rounded disabled:opacity-50 text-sm sm:text-base"
            >
              <FaArrowLeft /> Trước
            </button>
            {Array.from({ length: totalPages }).map(function (_, i) {
              return (
                <button
                  key={i}
                  onClick={function () { setCurrentPage(i + 1); }}
                  className={`px-3 py-1 border rounded text-sm sm:text-base ${currentPage === i + 1 ? "bg-red-500 text-white" : ""}`}
                >
                  {i + 1}
                </button>
              );
            })}
            <button
              onClick={function () { setCurrentPage(currentPage + 1); }}
              disabled={currentPage === totalPages}
              className="flex items-center gap-1 px-3 py-1 border rounded disabled:opacity-50 text-sm sm:text-base"
            >
              Sau <FaArrowRight />
            </button>
          </div>
        </>
      )}
    </div>
  );
};

export default ProductList;
