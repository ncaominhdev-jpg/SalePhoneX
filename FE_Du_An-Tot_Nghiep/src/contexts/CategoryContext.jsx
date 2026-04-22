import { createContext, useContext } from "react";
import { useCategories } from "../hooks/useCategories";

const CategoryContext = createContext();

export const CategoryProvider = ({ children }) => {
    const { data, isLoading } = useCategories();
    const categories = data?.categories || [];
    const categoryMap = data?.categoryMap || {};

    return (
        <CategoryContext.Provider value={{ categories, categoryMap, loading: isLoading }}>
            {children}
        </CategoryContext.Provider>
    );
};

export const useCategory = () => {
    const context = useContext(CategoryContext);
    if (!context) {
        throw new Error("useCategory phải được dùng trong CategoryProvider");
    }
    return context;
};
