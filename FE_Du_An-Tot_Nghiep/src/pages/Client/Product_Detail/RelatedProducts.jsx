import React from "react";
import { Link } from "react-router-dom";
import { FaStar } from "react-icons/fa";
import { toSlug } from "../../../utils/slug";
import ProductCard from "../../../components/ProductCard/ProductCard";
const RelatedProducts = ({ relatedProducts }) => {
  // Chỉ hiển thị 5 sản phẩm
  const displayedProducts = relatedProducts.slice(0, 5);

  return (
    <div className="mt-10">
      <h2 className="text-2xl font-bold mb-4 text-gray-800">
        SẢN PHẨM LIÊN QUAN
      </h2>
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        {displayedProducts.map((product) => {
          const categorySlug = product?.category_name
            ? toSlug(product.category_name)
            : "san-pham";
          const productSlug = product?.name ? toSlug(product.name) : "unknown";

          return (
            <Link
              key={product.id}
              to={`/${categorySlug}/${productSlug}`}
              
              onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
            >
              <ProductCard data={product} />
            </Link>
          );
        })}
      </div>
    </div>
  );
};

export default RelatedProducts;
