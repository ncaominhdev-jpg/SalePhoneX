import { createContext, useContext } from "react";
import { useBrands } from "../hooks/useBrands";

const BrandShopContext = createContext();

export const BrandShopProvider = ({ children }) => {
    const { data: brands = [], isLoading, refetch } = useBrands();

    return (
        <BrandShopContext.Provider value={{ brands, loading: isLoading, fetchBrands: refetch }}>
            {children}
        </BrandShopContext.Provider>
    );
};

export const useBrand = () => {
    const context = useContext(BrandShopContext);
    if (!context) throw new Error("useBrand must be used within a BrandProvider");
    return context;
};
