import { useQuery } from "@tanstack/react-query";
import axios from "axios";
import constants from "../constants/constants";

export const useAttributes = () => {
    // Gọi song song attributes và attribute-values
    return useQuery({
        queryKey: ["attributes"],
        queryFn: async () => {
            const [attrRes, attrValRes] = await Promise.all([
                axios.get(`${constants.BASE_URL}/attributes`),
                axios.get(`${constants.BASE_URL}/attribute-values`)
            ]);

            return {
                attributes: attrRes.data,
                attributeValues: attrValRes.data,
            };
        },
        staleTime: 5 * 60 * 1000, // cache 5 phút
        cacheTime: 30 * 60 * 1000, // lưu cache 30 phút
        refetchOnWindowFocus: false, // không refetch khi đổi tab
    });
};
