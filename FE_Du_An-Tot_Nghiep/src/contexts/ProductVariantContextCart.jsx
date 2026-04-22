import { createContext, useContext, useEffect, useState } from "react";
import axios from "axios";
import constants from "../constants/constants";

const ProductVariantContextCart = createContext();

export const ProductVariantProviderCart = ({ children }) => {
    const [variants, setVariants] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchVariants = async (productId = null) => {
        setLoading(true);
        try {
            const url = productId
                ? `${constants.BASE_URL}/product-variants?product_id=${productId}`
                : `${constants.BASE_URL}/product-variants`;

            const response = await axios.get(url);
            setVariants(response.data || []);
        } catch (error) {
            console.error("Lỗi khi fetch product variants:", error);
        } finally {
            setLoading(false);
        }
    };


    useEffect(() => {
        fetchVariants(); // Load tất cả lúc đầu
    }, []);

    return (
        <ProductVariantContextCart.Provider value={{ variants, loading, fetchVariants }}>
            {children}
        </ProductVariantContextCart.Provider>
    );
};

export const useProductVariant = () => useContext(ProductVariantContextCart);
