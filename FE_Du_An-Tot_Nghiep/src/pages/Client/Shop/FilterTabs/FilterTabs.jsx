import { useState, useEffect } from "react";
import axios from "axios";
import { useMediaQuery } from "react-responsive";
import { FaFilter, FaTruck, FaMoneyBillWave } from "react-icons/fa";
import { XMarkIcon } from "@heroicons/react/24/solid";
import FilterButton from "../../../../components/FilterButton/FilterButton";
import constants from "../../../../constants/constants";

const staticFilters = [
    { name: "Bộ lọc", icon: <FaFilter /> },
    { name: "Giá", icon: <FaMoneyBillWave /> },
    { name: "Sẵn hàng", icon: <FaTruck />, isToggle: true }
];

//Tách logic gọi API ra hàm riêng
async function fetchDynamicFilters(productIds) {
    const [valueRes, attrRes] = await Promise.all([
        axios.get(`${constants.BASE_URL}/attribute-values`),
        axios.get(`${constants.BASE_URL}/attributes`)
    ]);

    const values = valueRes.data.filter(v => productIds.includes(v.product_id));
    const attributes = attrRes.data;

    const grouped = {};
    values.forEach(v => {
        const attr = attributes.find(a => a.id === v.attribute_id);
        if (!attr) return;

        if (!grouped[attr.name]) {
            grouped[attr.name] = new Set();
        }
        grouped[attr.name].add(v.value);
    });

    return Object.entries(grouped).map(([name, set]) => ({
        name,
        subOptions: [...set]
    }));
}

function FilterTabs({
    products,
    minPrice,
    maxPrice,
    setMinPrice,
    setMaxPrice,
    selectedOptions,
    setSelectedOptions
}) {
    const isMobile = useMediaQuery({ maxWidth: 767 });
    const [open, setOpen] = useState(null);
    const [dynamicFilters, setDynamicFilters] = useState([]);

    // Load filter từ localStorage khi reload
    useEffect(() => {
        const savedFilters = JSON.parse(localStorage.getItem("selectedFilters")) || {};
        if (Object.keys(savedFilters).length > 0) {
            setSelectedOptions(savedFilters);
        }
    }, [setSelectedOptions]);

    // Lưu filter vào localStorage mỗi khi thay đổi
    useEffect(() => {
        localStorage.setItem("selectedFilters", JSON.stringify(selectedOptions));
    }, [selectedOptions]);

    useEffect(() => {
        if (!products || products.length === 0) return;
        const productIds = products.map(p => p.id);

        fetchDynamicFilters(productIds)
            .then(dynamic => setDynamicFilters(dynamic))
            .catch(err => console.error("Lỗi khi load bộ lọc:", err));
    }, [products]);

    const allFilters = [...staticFilters, ...dynamicFilters];

    function toggleDropdown(name) {
        setOpen(prev => (prev === name ? null : name));
    }

    function toggleOption(filterName, option) {
        setSelectedOptions(prev => {
            const current = prev[filterName] || [];
            const updated = current.includes(option)
                ? current.filter(o => o !== option)
                : [...current, option];
            return { ...prev, [filterName]: updated };
        });
    }

    function handleMinChange(e) {
        setMinPrice(Number(e.target.value));
    }

    function handleMaxChange(e) {
        setMaxPrice(Number(e.target.value));
    }

    function handleSliderChange(values) {
        setMinPrice(values[0]);
        setMaxPrice(values[1]);
    }

    function resetOptions() {
        setSelectedOptions({});
        setMinPrice(0);
        setMaxPrice(50000000);
    }

    function closeDropdown() {
        setOpen(null);
    }

    function removeSelected(filterName, option) {
        setSelectedOptions(prev => {
            if (option === null) {
                const updated = { ...prev };
                delete updated[filterName];
                return updated;
            }
            return {
                ...prev,
                [filterName]: prev[filterName].filter(o => o !== option)
            };
        });
    }

    function renderFilterButtons() {
        return allFilters.map((filter, index) => {
            const showOnMobile = ["Sẵn hàng", "Giá", "Bộ lọc"].includes(filter.name);
            if (isMobile && !showOnMobile) return null;

            return (
                <FilterButton
                    key={index}
                    filter={filter}
                    filters={allFilters}
                    open={open}
                    toggleDropdown={toggleDropdown}
                    toggleOption={toggleOption}
                    selectedOptions={selectedOptions}
                    closeDropdown={closeDropdown}
                    resetOptions={resetOptions}
                    isPrice={filter.name === "Giá"}
                    minPrice={minPrice}
                    maxPrice={maxPrice}
                    handleMinChange={handleMinChange}
                    handleMaxChange={handleMaxChange}
                    handleSliderChange={handleSliderChange}
                    setSelectedOptions={setSelectedOptions}
                />
            );
        });
    }

    function renderSelectedFilters() {
        return Object.entries(selectedOptions).map(([filterName, options]) => {
            if (options.length === 0) return null;
            return (
                <div
                    key={filterName}
                    className="border border-blue-500 text-blue-600 px-3 py-1 rounded-full flex items-center text-sm"
                >
                    <button
                        onClick={() => removeSelected(filterName, null)}
                        className="bg-gray-200 text-black text-xs rounded-full w-4 h-4 flex items-center justify-center mr-2 transition-colors duration-200 hover:bg-gray-300 hover:text-red-600"
                    >
                        <XMarkIcon className="w-3 h-3" />
                    </button>
                    <span className="font-medium mr-1">{filterName}:</span>
                    <span>{options.join(" | ")}</span>
                </div>
            );
        });
    }

    return (
        <>
            {/* Bộ nút lọc */}
            <div className="flex flex-wrap gap-2">{renderFilterButtons()}</div>

            {/* Đang lọc theo */}
            {Object.values(selectedOptions).some(arr => arr.length > 0) && (
                <div className="mt-6">
                    <h1 className="mb-3 text-lg sm:text-xl font-bold text-gray-900 flex items-center gap-2">
                        <span className="w-1.5 h-6 bg-red-600 rounded-full"></span>
                        Đang lọc theo
                    </h1>
                    <div className="flex flex-wrap gap-2 items-center">
                        {renderSelectedFilters()}

                        {/* Nút bỏ chọn */}
                        <button
                            onClick={() => setSelectedOptions({})}
                            className="flex items-center gap-1 px-4 py-2 rounded-full 
                                    bg-gradient-to-r from-red-600 to-red-500 
                                    text-white text-sm font-medium 
                                    shadow-sm hover:shadow-md transition-all duration-200 
                                    hover:from-red-700 hover:to-red-600"
                        >
                            <XMarkIcon className="w-4 h-4" />
                            Bỏ chọn tất cả
                        </button>
                    </div>
                </div>
            )}
        </>
    );

}

export default FilterTabs;
