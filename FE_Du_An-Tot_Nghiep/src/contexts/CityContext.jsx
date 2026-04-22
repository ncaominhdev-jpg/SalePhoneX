import React, { createContext, useContext, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import constants from "../constants/constants";

const CityContext = createContext();

export const CityProvider = ({ children }) => {
    const [selectedCity, setSelectedCity] = useState(() => {
        return localStorage.getItem("selectedCity") || "Hồ Chí Minh";
    });

    // Lấy danh sách thành phố bằng react-query
    const { data: cityList = [], isLoading, error } = useQuery({
        queryKey: ["cityList"],
        queryFn: async () => {
            const res = await fetch(`${constants.BASE_URL}/branches`);
            if (!res.ok) throw new Error("Lỗi tải danh sách city");
            const data = await res.json();
            return [...new Set(data.map((b) => b.city))].filter(Boolean).sort();
        },
        staleTime: 10 * 60 * 1000, // cache 10 phút
    });

    // Lưu city vào localStorage khi đổi
    React.useEffect(() => {
        if (selectedCity) {
            localStorage.setItem("selectedCity", selectedCity);
        }
    }, [selectedCity]);

    return (
        <CityContext.Provider
            value={{
                cityList,
                selectedCity,
                setSelectedCity,
                loading: isLoading,
                error,
            }}
        >
            {children}
        </CityContext.Provider>
    );
};

export const useCity = () => {
    const context = useContext(CityContext);
    if (!context) {
        throw new Error("useCity phải được dùng trong CityProvider");
    }
    return context;
};
