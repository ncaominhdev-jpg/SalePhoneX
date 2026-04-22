// src/contexts/ProductHomeContext.jsx
import { createContext, useState, useEffect, useRef } from "react";
import axios from "axios";
import constants from "../constants/constants";

export const ProductContext = createContext();

const ProductHomeProvider = ({ children }) => {
  // ----- List -----
  const [products, setProducts] = useState([]);
  const [categoryId, setCategoryId] = useState(null);

  // ----- Detail -----
  const [productDetail, setProductDetail] = useState(null);
  const [productMedia, setProductMedia] = useState([]);
  const [loadingDetail, setLoadingDetail] = useState(false);

  // ----- Brand filter -----
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [selectedBrandId, setSelectedBrandId] = useState(null);

  // ----- Attributes -----
  const [productAttributes, setProductAttributes] = useState([]);
  const [loadingAttributes, setLoadingAttributes] = useState(false);

  // ----- Race-condition & Abort -----
  const lastReqRef = useRef(0);       // id của request fetch detail mới nhất
  const abortRef = useRef(null);    // AbortController cho fetch detail hiện tại

  // =========================
  // Fetch list (theo danh mục)
  // =========================
  useEffect(() => {
    const abort = new AbortController();

    const fetchProducts = async () => {
      try {
        const url = categoryId
          ? `${constants.BASE_URL}/products/category/${categoryId}`
          : `${constants.BASE_URL}/products`;
        const res = await axios.get(url, { signal: abort.signal });
        setProducts(res.data);
      } catch (err) {
        if (err.name !== "CanceledError" && err.name !== "AbortError") {
          console.error("Lỗi khi lấy sản phẩm:", err);
        }
      }
    };

    fetchProducts();
    return () => abort.abort();
  }, [categoryId]);

  // =========================
  // Helpers / APIs phụ trợ
  // =========================
  const fetchAllProducts = async () => {
    const abort = new AbortController();
    try {
      const res = await axios.get(`${constants.BASE_URL}/products`, {
        signal: abort.signal,
      });
      return res.data;
    } catch (error) {
      if (error.name !== "CanceledError" && error.name !== "AbortError") {
        console.error("Lỗi khi lấy tất cả sản phẩm:", error);
      }
      return [];
    }
  };

  const getMediaByProductId = async (productId, signal) => {
    try {
      const response = await axios.get(
        `${constants.BASE_URL}/media/${productId}`,
        { signal }
      );
      return response.data;
    } catch (error) {
      if (error.name !== "CanceledError" && error.name !== "AbortError") {
        console.error("Lỗi khi lấy media:", error);
      }
      return [];
    }
  };

  const fetchAttributesByProductId = async (productId) => {
    const abort = new AbortController();
    setLoadingAttributes(true);
    try {
      const res = await axios.get(
        `${constants.BASE_URL}/products/${productId}/attributes`,
        { signal: abort.signal }
      );
      setProductAttributes(res.data?.attributes || []);
    } catch (error) {
      if (error.name !== "CanceledError" && error.name !== "AbortError") {
        console.error("Lỗi khi lấy thuộc tính sản phẩm:", error);
      }
      setProductAttributes([]);
    } finally {
      setLoadingAttributes(false);
    }
  };

  // ==========================================
  // Fetch chi tiết sản phẩm (fix flicker + race + abort)
  // ==========================================
  const fetchProductById = async (id) => {
    if (!id) return;

    // đánh dấu request mới nhất
    const reqId = ++lastReqRef.current;

    // 🔴 Huỷ request chi tiết trước (nếu còn)
    if (abortRef.current) {
      try { abortRef.current.abort(); } catch { }
    }
    // 🟢 Controller mới cho lần gọi này
    abortRef.current = new AbortController();
    const { signal } = abortRef.current;

    // Reset UI NGAY ⇒ tránh hiển thị sản phẩm cũ
    setLoadingDetail(true);
    setProductDetail(null);
    setProductMedia([]);

    try {
      // 1) Chi tiết sản phẩm
      const res = await axios.get(`${constants.BASE_URL}/products/${id}`, {
        signal,
      });
      const found = res.data;

      // 2) Media
      const mediaRes = await getMediaByProductId(id, signal);
      found.images = (mediaRes || []).map((m) => m.url);

      // 3) Variants
      try {
        const variantsRes = await axios.get(
          `${constants.BASE_URL}/product-variants?product_id=${id}`,
          { signal }
        );
        found.product_variants = variantsRes.data || [];
      } catch (variantErr) {
        if (
          variantErr.name !== "CanceledError" &&
          variantErr.name !== "AbortError"
        ) {
          console.warn("Không lấy được danh sách biến thể:", variantErr.message);
        }
        found.product_variants = [];
      }

      // Commit chỉ khi vẫn là request mới nhất
      if (lastReqRef.current === reqId) {
        setProductDetail(found);
        setProductMedia(mediaRes || []);
      }
    } catch (err) {
      if (err.name === "CanceledError" || err.name === "AbortError") {
        // bị huỷ vì user chuyển sản phẩm nhanh ⇒ bỏ qua
      } else {
        console.error("Lỗi khi lấy chi tiết sản phẩm:", err.message);
        if (lastReqRef.current === reqId) {
          setProductDetail(null);
          setProductMedia([]);
        }
      }
    } finally {
      if (lastReqRef.current === reqId) {
        setLoadingDetail(false);
        // Tuỳ chọn: abortRef.current = null;
      }
    }
  };

  // ==========================================
  // Fetch một biến thể & đồng bộ chi tiết + thuộc tính
  // ==========================================
  const fetchProductVariantById = async (variantId) => {
    const abort = new AbortController();
    try {
      const res = await axios.get(
        `${constants.BASE_URL}/product-variants/${variantId}`,
        { signal: abort.signal }
      );
      const variant = res.data;

      if (variant?.product_id) {
        await Promise.all([
          fetchProductById(variant.product_id),
          fetchAttributesByProductId(variant.product_id),
        ]);
      } else {
        console.warn("Variant không có product_id!");
      }

      return variant;
    } catch (err) {
      if (err.name !== "CanceledError" && err.name !== "AbortError") {
        console.error(
          "Lỗi khi lấy chi tiết biến thể:",
          err.response?.data || err.message
        );
      }
      return null;
    }
  };

  // ==========================================
  // Lọc theo brand
  // ==========================================
  const handleBrandClick = (brandId) => {
    setSelectedBrandId(brandId);

    const url =
      brandId === null
        ? `${constants.BASE_URL}/products`
        : `${constants.BASE_URL}/products/brand/${brandId}`;

    axios
      .get(url)
      .then((res) => setFilteredProducts(res.data))
      .catch((err) => console.error("Lỗi khi lọc sản phẩm:", err));
  };

  return (
    <ProductContext.Provider
      value={{
        // list
        products,
        setCategoryId,

        // detail
        productDetail,
        fetchProductById,
        loadingDetail,
        productMedia,

        // brand filter
        filteredProducts,
        selectedBrandId,
        handleBrandClick,
        setSelectedBrandId,

        // attributes
        productAttributes,
        fetchAttributesByProductId,
        loadingAttributes,

        // helpers
        fetchProductVariantById,
        fetchAllProducts,
        getMediaByProductId,
      }}
    >
      {children}
    </ProductContext.Provider>
  );
};

export default ProductHomeProvider;
