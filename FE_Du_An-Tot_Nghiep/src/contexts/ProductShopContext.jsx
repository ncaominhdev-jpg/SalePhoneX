import { createContext, useContext } from "react";
import Fuse from "fuse.js";
import { useAllProducts } from "../hooks/useProducts";

const ProductShopContext = createContext();

const ProductShopProvider = ({ children }) => {
    const { data: allProducts = [], isLoading } = useAllProducts();

    const getProductsByCategory = (categoryId) => {
        return allProducts.filter(
            (p) => Number(p.category_id) === Number(categoryId)
        );
    };

    const searchProducts = (keyword = "") => {
        if (!keyword.trim()) return [];

        const fuse = new Fuse(allProducts, {
            keys: ["name"],
            threshold: 0.4,
        });

        const result = fuse.search(keyword);
        return result.map((r) => r.item);
    };

    return (
        <ProductShopContext.Provider
            value={{
                products: allProducts,
                loading: isLoading,
                getProductsByCategory,
                searchProducts,
            }}
        >
            {children}
        </ProductShopContext.Provider>
    );
};

export { ProductShopContext, ProductShopProvider };

export const useProductShop = () => {
    const context = useContext(ProductShopContext);
    if (!context) {
        throw new Error("useProductShop phải được dùng trong ProductShopProvider");
    }
    return context;
};
