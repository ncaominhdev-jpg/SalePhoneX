import { useQuery } from "@tanstack/react-query";
import { useCity } from "../contexts/CityContext";
import constants from "../constants/constants";

export const useAvailableProducts = () => {
    const { selectedCity } = useCity();

    // Gọi API với react-query
    const { data, isLoading, error, refetch } = useQuery({
        queryKey: ["availableProducts", selectedCity],
        queryFn: async () => {
            if (!selectedCity) return [];
            const res = await fetch(
                `${constants.BASE_DOMAIN}/api/available-products/city/${encodeURIComponent(selectedCity)}`
            );
            if (!res.ok) throw new Error("Lỗi tải sản phẩm sẵn hàng");
            return res.json();
        },
        enabled: !!selectedCity, // chỉ gọi khi đã có selectedCity
        staleTime: 5 * 60 * 1000, // cache 5 phút
    });

    return {
        availableProducts: data || [],
        loading: isLoading,
        error,
        selectedCity,
        refetch,
    };
};
