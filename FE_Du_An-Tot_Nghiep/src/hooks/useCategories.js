import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import constants from '../constants/constants';
import { toSlug } from '../utils/slug';

export const useCategories = () => {
    return useQuery({
        queryKey: ['categories'],
        queryFn: async () => {
            const res = await axios.get(`${constants.BASE_URL}/categories`);
            const filtered = res.data.filter((c) => c.status === 1);

            // tạo map slug -> category
            const categoryMap = {};
            for (const cat of filtered) {
                const slug = toSlug(cat.name);
                categoryMap[slug] = cat;
            }

            return { categories: filtered, categoryMap };
        },
        staleTime: 1000 * 60 * 30, // cache 30 phút
    });
};
