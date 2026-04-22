import { useState } from "react";
import { FaFilter, FaTruck, FaMoneyBillWave } from "react-icons/fa";
import FilterButton from "../../../../components/FilterButton/FilterButton";

const filters = [
    { name: "Bộ lọc", icon: <FaFilter /> },
    { name: "Sẵn hàng", icon: <FaTruck /> },
    { name: "Giá", icon: <FaMoneyBillWave /> },
    {
        name: "Nhu cầu sử dụng",
        subOptions: [
            "Chơi game", "Pin trâu", "Dung lượng lớn", "Cấu hình cao",
            "Mỏng nhẹ", "Chụp ảnh đẹp", "Nhỏ gọn", "Livestream"
        ],
    },
    { name: "Chip xử lí", subOptions: ["Snapdragon", "MediaTek", "Apple", "Exynos"] },
    { name: "Loại điện thoại", subOptions: ["Android", "iPhone", "Phổ thông"] },
    { name: "Dung lượng RAM", subOptions: ["2GB", "4GB", "6GB", "8GB", "12GB"] },
    { name: "Bộ nhớ trong", subOptions: ["32GB", "64GB", "128GB", "256GB"] },
    { name: "Tính năng đặc biệt", subOptions: ["Kháng nước", "Mở khóa vân tay", "Sạc nhanh"] },
    { name: "Tính năng camera", subOptions: ["Chụp đêm", "Zoom quang học", "Camera góc rộng"] },
    { name: "Tần số quét", subOptions: ["60Hz", "90Hz", "120Hz"] },
    { name: "Kích thước màn hình", subOptions: ["< 6 inch", "6 - 6.5 inch", "> 6.5 inch"] },
    { name: "Kiểu màn hình", subOptions: ["AMOLED", "LCD", "OLED"] },
    { name: "Công nghệ NFC", subOptions: ["Hỗ trợ NFC", "Không hỗ trợ"] },
];

function FilterTabs() {
    const [open, setOpen] = useState(null);
    const [selectedOptions, setSelectedOptions] = useState({});
    const [minPrice, setMinPrice] = useState(0);
    const [maxPrice, setMaxPrice] = useState(50000000);

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

    return (
        <div className="flex flex-wrap gap-2">
            {filters.map((filter, index) => {
                // Responsive: chỉ hiện Sẵn hàng, Giá, Bộ lọc trên mobile
                if (
                    window.innerWidth < 768 &&
                    filter.name !== "Sẵn hàng" &&
                    filter.name !== "Giá" &&
                    filter.name !== "Bộ lọc"
                ) {
                    return null;
                }

                return (
                    <FilterButton
                        key={index}
                        filter={filter}
                        filters={filters}
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
                    />
                );
            })}
        </div>
    );
}

export default FilterTabs;
