import { useContext } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { HomeIcon } from "lucide-react";
import { ProductShopContext } from "../../../contexts/ProductShopContext";
import ProductCard from "../../../components/ProductCard/ProductCard";
import image from "../../../assets/No-products-found.png";
import { toSlug } from "../../../utils/slug";
import { useCategories } from "../../../hooks/useCategories";

const SearchPage = () => {
    const { searchProducts } = useContext(ProductShopContext);
    const [params] = useSearchParams();
    const keyword = params.get("keyword") || "";
    const results = searchProducts(keyword);

    // Lấy categoryMap để suy ra category name từ category_id (giống SearchBox)
    const { data: categoriesData } = useCategories();
    const categoryMap = categoriesData?.categoryMap || {};

    return (
        <div className="max-w-7xl mx-auto px-4 py-6 min-h-[50vh]">
            {/* Breadcrumb */}
            <div className="flex items-center text-sm text-gray-600 mb-2">
                <Link to="/" className="flex items-center hover:text-red-500">
                    <HomeIcon className="w-4 h-4 mr-1" />
                    Trang chủ
                </Link>
                <span className="mx-2">/</span>
                Kết quả tìm kiếm cho: <strong className="text-red-600">"{keyword}"</strong>
            </div>

            {/* Tổng kết quả */}
            <div className="flex flex-wrap justify-between items-center mb-4">
                <div className="text-lg font-semibold">
                    Tìm thấy {results.length} sản phẩm cho từ khoá{" "}
                    <span className="text-red-600">'{keyword}'</span>
                </div>
            </div>

            {/* Thanh sắp xếp (UI giữ nguyên) */}
            <div className="flex items-center gap-3 mb-6">
                <div className="font-semibold text-gray-700">Sắp xếp theo:</div>
                {["Liên quan", "Giá cao", "Giá thấp"].map((sort, i) => (
                    <button key={i} className="px-3 py-1 border rounded-full text-sm hover:bg-red-100 text-gray-800">
                        {sort}
                    </button>
                ))}
            </div>

            {/* Kết quả sản phẩm */}
            {results.length > 0 ? (
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    {results.map((p) => {
                        // Lấy category name từ map (giống SearchBox)
                        const categoryObj = Object.values(categoryMap).find((c) => c.id === p.category_id);
                        const categoryName = categoryObj?.name || "san-pham";
                        const categorySlug = toSlug(categoryName);
                        const productSlug = toSlug(p.product_slug || p.name || "san-pham");
                        const href = `/${categorySlug}/${productSlug}`;

                        return (
                            <Link key={p.id} to={href} state={{ productId: p.id }}>
                                <ProductCard product={p} />
                            </Link>
                        );
                    })}
                </div>
            ) : (
                <div className="flex flex-col items-center justify-center">
                    <img src={image} alt="" className="sm:w-[70%] md:w-[50%] lg:w-[30%]" />
                    <p className="text-gray-600">Không tìm thấy sản phẩm phù hợp.</p>
                </div>
            )}
        </div>
    );
};

export default SearchPage;
