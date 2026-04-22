import { createContext, useState, useEffect } from "react";
import axios from "axios";
import constants from "../constants/constants";

export const BrandContext = createContext();

const BrandHomeProvider = ({ children }) => {
  const [brands, setBrands] = useState([]);
  const [categoryId, setCategoryId] = useState(null); // categoryId hiện tại

  useEffect(() => {
    const fetchBrands = async () => {
      try {
        if (!categoryId) {
          setBrands([]);
          return;
        }

        const url = `${constants.BASE_URL}/brands/category/${categoryId}`;
        const res = await axios.get(url);
        setBrands(res.data);
      } catch (err) {
        console.error("Lỗi tải thương hiệu:", err);
      }
    };

    fetchBrands();
  }, [categoryId]);

  return (
    <BrandContext.Provider value={{ brands, setCategoryId }}>
      {children}
    </BrandContext.Provider>
  );
};

export default BrandHomeProvider;
