import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { FaSearch, FaTrash, FaTimes } from 'react-icons/fa';
import axios from 'axios';
import Fuse from 'fuse.js';
import constants from '../../../constants/constants';
import { toSlug } from '../../../utils/slug';
import { useCategories } from '../../../hooks/useCategories';

const SearchBox = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [products, setProducts] = useState([]);
    const [searchHistory, setSearchHistory] = useState([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const wrapperRef = useRef(null);
    const navigate = useNavigate();

    const { data: categoriesData } = useCategories();
    const categoryMap = categoriesData?.categoryMap || {};

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        const delayDebounce = setTimeout(async () => {
            if (searchTerm.trim()) {
                try {
                    const res = await axios.get(`${constants.BASE_URL}/products`);
                    const fuse = new Fuse(res.data, {
                        keys: ['name'],
                        threshold: 0.4,
                    });
                    const result = fuse.search(searchTerm.toLowerCase());
                    const matchedProducts = result.map(r => r.item);
                    setProducts(matchedProducts.slice(0, 5));
                    setShowDropdown(true);
                } catch (err) {
                    console.error("Lỗi tìm kiếm:", err);
                }
            } else {
                setProducts([]);
            }
        }, 300);

        return () => clearTimeout(delayDebounce);
    }, [searchTerm]);

    const handleFocus = () => {
        const history = JSON.parse(localStorage.getItem('search_history') || '[]');
        setSearchHistory(history);
        setShowDropdown(true);
    };

    const handleSearch = () => {
        if (searchTerm.trim()) {
            const history = JSON.parse(localStorage.getItem('search_history') || '[]');
            const updated = [searchTerm, ...history.filter(item => item !== searchTerm)].slice(0, 10);
            localStorage.setItem('search_history', JSON.stringify(updated));
            setSearchHistory(updated);
            navigate(`/search?keyword=${encodeURIComponent(searchTerm)}`);
            setShowDropdown(false);
        }
    };

    return (
        <div className="relative w-full max-w-xl" ref={wrapperRef}>
            <div className="flex items-center bg-white rounded-md shadow border border-gray-300 w-full overflow-hidden">
                <input
                    type="text"
                    placeholder="Bạn muốn mua gì hôm nay?"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                    onFocus={handleFocus}
                    className="flex-grow px-3 py-2 text-sm sm:text-sm text-gray-800 outline-none"
                />
                <button
                    onClick={handleSearch}
                    className="flex items-center justify-center px-3 py-2 text-gray-600 hover:text-red-600 flex-shrink-0 w-10 sm:w-12"
                >
                    <FaSearch className="hidden sm:inline text-base sm:text-lg" />
                </button>
            </div>

            {showDropdown && (
                <div className="absolute top-full left-0 mt-1 w-full bg-white border rounded-lg shadow-lg z-50 max-h-[80vh] overflow-y-auto text-base sm:text-sm">

                    {/* Lịch sử tìm kiếm */}
                    {searchTerm.trim() === '' && searchHistory.length > 0 && (
                        <>
                            <div className="flex justify-between items-center px-5 py-3 border-b text-gray-800 font-semibold text-xs sm:text-sm">
                                <span className="flex items-center gap-2">
                                    Lịch sử tìm kiếm
                                </span>
                                <button
                                    className="flex items-center text-blue-500 hover:underline"
                                    onClick={() => {
                                        localStorage.removeItem('search_history');
                                        setSearchHistory([]);
                                    }}
                                >
                                    {/* Mobile: chỉ hiện icon */}
                                    <FaTrash className="text-[14px] text-gray-800 sm:hidden" />
                                    {/* Tablet/desktop: hiện chữ */}
                                    <span className="hidden sm:inline text-[11px] sm:text-xs">
                                        Xoá tất cả
                                    </span>
                                </button>
                            </div>
                            <ul className="text-gray-700 divide-y">
                                {searchHistory.map((item, idx) => (
                                    <li
                                        key={idx}
                                        className="px-5 py-2 sm:py-3 flex justify-between items-center hover:bg-gray-100 active:bg-gray-200 transition text-xs sm:text-sm"
                                    >
                                        <span
                                            className="truncate flex-1 cursor-pointer"
                                            onClick={() => {
                                                navigate(`/search?keyword=${encodeURIComponent(item)}`);
                                                setShowDropdown(false);
                                            }}
                                        >
                                            {item}
                                        </span>

                                        <button
                                            className="ml-3 text-gray-400 hover:text-red-500 transition"
                                            onClick={(e) => {
                                                e.stopPropagation(); // ngăn không trigger navigate
                                                const updated = searchHistory.filter((_, i) => i !== idx);
                                                localStorage.setItem("search_history", JSON.stringify(updated));
                                                setSearchHistory(updated);
                                            }}
                                        >
                                            <FaTimes size={12} />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </>
                    )}
                    {/* Gợi ý sản phẩm */}
                    {products.length > 0 && (
                        <>
                            <div className="px-5 py-4 border-t font-semibold text-gray-700 bg-gray-50 text-lg sm:text-base">
                                Sản phẩm gợi ý
                            </div>
                            <ul className="divide-y">
                                {products.map((p) => {
                                    // lấy category từ categoryMap theo category_id
                                    const categoryObj = Object.values(categoryMap).find(c => c.id === p.category_id);
                                    const categoryName = categoryObj?.name || "san-pham";
                                    const categorySlug = toSlug(categoryName);
                                    const productSlug = toSlug(p.name || "san-pham");

                                    return (
                                        <li key={p.id}>
                                            <Link
                                                to={`/${categorySlug}/${productSlug}`}
                                                className="flex items-center gap-4 px-5 py-4 hover:bg-gray-100 active:bg-gray-200 cursor-pointer transition"
                                                onClick={() => setShowDropdown(false)}
                                            >
                                                <img
                                                    src={p.image}
                                                    alt={p.name}
                                                    className="w-16 h-16 rounded-md border object-cover flex-shrink-0 sm:w-14 sm:h-14"
                                                />
                                                <div className="flex flex-col justify-center overflow-hidden flex-grow min-w-0">
                                                    <span className="font-medium text-gray-800 text-lg sm:text-base truncate">
                                                        {p.name}
                                                    </span>
                                                    <div className="text-red-600 font-semibold text-base sm:text-sm">
                                                        {Number(p.price).toLocaleString('vi-VN')} đ
                                                    </div>
                                                    {p.original_price && p.original_price > p.price && (
                                                        <div className="text-sm text-gray-400 line-through">
                                                            {Number(p.original_price).toLocaleString('vi-VN')} đ
                                                        </div>
                                                    )}
                                                </div>
                                            </Link>
                                        </li>
                                    );
                                })}


                            </ul>
                        </>
                    )}
                </div>
            )}
        </div>
    );
};

export default SearchBox;
