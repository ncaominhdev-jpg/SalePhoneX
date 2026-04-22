// src/hooks/useProducts.js
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import constants from '../constants/constants';

export const useAllProducts = () => {
    return useQuery({
        queryKey: ['allProducts'],
        queryFn: async () => {
            const res = await axios.get(`${constants.BASE_URL}/products`);
            return res.data
                .filter((p) => p.status === 1)
                .map((p) => ({
                    ...p,
                    brand_id: p.brand_id ?? p.brands_id,
                }));
        },
        staleTime: 1000 * 60 * 5, // cache 5 phút
    });
};

export const useProductsByCategory = (categoryId) => {
    return useQuery({
        queryKey: ['products', categoryId],
        queryFn: async () => {
            if (!categoryId) return [];
            const res = await axios.get(`${constants.BASE_URL}/products/category/${categoryId}`);
            return res.data.filter(p => p.status === 1);
        },
        enabled: !!categoryId, // chỉ chạy khi có categoryId
        staleTime: 1000 * 60 * 5,
    });
};
