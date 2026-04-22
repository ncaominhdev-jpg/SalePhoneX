// src/hooks/useBrands.js
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import constants from '../constants/constants';

export const useBrands = () => {
    return useQuery({
        queryKey: ['brands'],
        queryFn: async () => {
            const res = await axios.get(`${constants.BASE_URL}/brands`);
            return res.data.filter(b => b.status === true);
        },
        staleTime: 1000 * 60 * 10, // cache 10 phút
    });
};
