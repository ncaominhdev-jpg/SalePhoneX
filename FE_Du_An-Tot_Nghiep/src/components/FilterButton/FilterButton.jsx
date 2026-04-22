import React from "react";
import { X } from "lucide-react";
import { RotateCcw, Eye } from "lucide-react";
import Slider from "rc-slider";
import "rc-slider/assets/index.css";

function FilterButton(props) {
    function handleClick() {
        if (props.filter.isToggle) {
            const isSelected = props.selectedOptions[props.filter.name]?.includes("Còn hàng");
            const updated = isSelected ? [] : ["Còn hàng"];
            props.setSelectedOptions(prev => ({
                ...prev,
                [props.filter.name]: updated
            }));
        } else {
            props.toggleDropdown(props.filter.name);
        }
    }


    const hasSelection = props.selectedOptions[props.filter.name]?.length > 0;

    return (
        <div className="relative">
            <button
                onClick={handleClick}
                className={
                    "flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition-colors duration-200 " +
                    (props.open === props.filter.name
                        ? "bg-red-600 text-white border-red-600 shadow-md"
                        : hasSelection
                            ? "bg-red-50 border-red-500 text-red-600"
                            : "bg-gray-100 border-gray-200 text-gray-800 hover:bg-gray-200")
                }
            >
                {props.filter.icon && <span className="text-base">{props.filter.icon}</span>}
                <span>{props.filter.name}</span>
            </button>

            {/* Bộ lọc tổng dropdown responsive */}
            {props.open === props.filter.name && props.filter.name === "Bộ lọc" && (
                <div className="fixed inset-0 bg-black bg-opacity-40 z-40 flex justify-center items-center md:absolute md:inset-auto md:left-0 md:top-full md:mt-3 md:bg-transparent md:block px-4 md:px-0">
                    <div className="relative bg-white rounded-2xl p-0 w-full max-w-full sm:max-w-[520px] md:w-[760px] lg:w-[820px] h-auto max-h-[75vh] md:max-h-[55vh] overflow-y-auto shadow-xl border border-gray-200 transition-transform duration-300 ease-out">
                        {/* Header */}
                        <div className="sticky top-0 bg-white px-5 py-3 border-b flex justify-between items-center shadow-sm">
                            <h3 className="text-lg md:text-xl font-bold text-gray-800">
                                Bộ lọc sản phẩm
                            </h3>
                            <button
                                onClick={props.closeDropdown}
                                className="md:hidden text-gray-500 hover:text-red-600 transition-colors p-2 rounded-full hover:bg-red-50"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Content */}
                        <div className="px-5 py-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                                {props.filters.map((filter, index) => {
                                    if (!filter.subOptions) return null;
                                    return (
                                        <div key={index}>
                                            <h4 className="font-semibold mb-3 text-gray-800 text-sm md:text-base flex items-center gap-2">
                                                <span className="w-1.5 h-4 bg-red-600 rounded-full"></span>
                                                {filter.name}
                                            </h4>

                                            <div className="flex flex-wrap gap-2">
                                                {filter.subOptions.map((option, idx) => {
                                                    const isSelected = props.selectedOptions[filter.name]?.includes(option);
                                                    return (
                                                        <div
                                                            key={idx}
                                                            onClick={() => props.toggleOption(filter.name, option)}
                                                            className={
                                                                "px-4 py-2 rounded-full text-xs md:text-sm font-medium cursor-pointer select-none transition-all duration-200 shadow-sm " +
                                                                (isSelected
                                                                    ? "bg-gradient-to-r from-red-600 to-red-500 text-white shadow-md scale-105 border border-red-600"
                                                                    : "bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200 hover:shadow-md hover:scale-105")
                                                            }
                                                        >
                                                            {option}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="sticky bottom-0 bg-white px-4 py-3 border-t flex justify-between items-center gap-3 shadow-md rounded-b-2xl">
                            <button
                                onClick={props.resetOptions}
                                className="w-1/2 flex items-center justify-center gap-2 
                                bg-gray-100 text-gray-700 font-medium px-4 py-2 rounded-full 
                                hover:bg-gray-200 hover:shadow transition-all duration-200 text-sm"
                            >
                                <RotateCcw className="w-4 h-4" />
                                Thiết lập lại
                            </button>

                            <button
                                onClick={props.closeDropdown}
                                className="w-1/2 flex items-center justify-center gap-2 
                                bg-gradient-to-r from-red-600 to-red-500 text-white font-semibold 
                                px-4 py-2 rounded-full shadow 
                                hover:from-red-700 hover:to-red-600 hover:shadow-lg transition-all duration-200 text-sm"
                            >
                                <Eye className="w-4 h-4" />
                                Xem kết quả
                            </button>
                        </div>
                    </div>
                </div>
            )}


            {/* Filter giá */}
            {props.open === props.filter.name && props.isPrice && (
                <div
                    className="absolute left-1/2 sm:left-0 top-full mt-3 z-40 
                bg-white rounded-2xl px-6 py-4 w-[280px] sm:w-[360px] 
                transform -translate-x-1/2 sm:translate-x-0 
                shadow-xl border border-gray-200 transition-transform duration-300 ease-out"
                >
                    <h3 className="text-center font-semibold mb-4 text-gray-900 text-base">
                        Chọn khoảng giá
                    </h3>

                    {/* Input giá */}
                    <div className="flex items-center justify-between mb-5 gap-3">
                        <input
                            type="text"
                            value={new Intl.NumberFormat("vi-VN").format(props.minPrice)}
                            onChange={(e) => {
                                const raw = e.target.value.replace(/\D/g, "");
                                props.handleMinChange({ target: { value: Number(raw) } });
                            }}
                            className="border border-gray-300 rounded-lg px-3 py-2 w-[48%] text-sm 
                    focus:ring-2 focus:ring-red-200 focus:border-red-500 outline-none"
                            placeholder="Từ"
                        />
                        <span className="text-gray-500 font-medium">-</span>
                        <input
                            type="text"
                            value={new Intl.NumberFormat("vi-VN").format(props.maxPrice)}
                            onChange={(e) => {
                                const raw = e.target.value.replace(/\D/g, "");
                                props.handleMaxChange({ target: { value: Number(raw) } });
                            }}
                            className="border border-gray-300 rounded-lg px-3 py-2 w-[48%] text-sm 
                    focus:ring-2 focus:ring-red-200 focus:border-red-500 outline-none"
                            placeholder="Đến"
                        />
                    </div>

                    {/* Slider */}
                    <Slider
                        range
                        min={0}
                        max={50000000}
                        step={100000}
                        value={[props.minPrice, props.maxPrice]}
                        onChange={props.handleSliderChange}
                        trackStyle={[{ backgroundColor: "#dc2626", height: 6 }]}
                        handleStyle={[
                            { borderColor: "#dc2626", backgroundColor: "#dc2626" },
                            { borderColor: "#dc2626", backgroundColor: "#dc2626" },
                        ]}
                        railStyle={{ height: 6 }}
                    />

                    {/* Footer */}
                    <div className="mt-6 flex flex-col sm:flex-row justify-between items-center gap-3">
                        <button
                            onClick={props.resetOptions}
                            className="w-full sm:w-1/2 flex items-center justify-center gap-2 
                    bg-gray-100 text-gray-700 font-medium px-4 py-2 rounded-full 
                    hover:bg-gray-200 hover:shadow transition-all duration-200 text-sm"
                        >
                            <RotateCcw className="w-4 h-4" />
                            Thiết lập lại
                        </button>

                        <button
                            onClick={props.closeDropdown}
                            className="w-full sm:w-1/2 flex items-center justify-center gap-2 
                    bg-gradient-to-r from-red-600 to-red-500 text-white font-semibold 
                    px-4 py-2 rounded-full shadow 
                    hover:from-red-700 hover:to-red-600 hover:shadow-lg transition-all duration-200 text-sm"
                        >
                            <X className="w-4 h-4" />
                            Đóng
                        </button>
                    </div>
                </div>
            )}

            {/* Filter subOptions nhỏ */}
            {props.open === props.filter.name &&
                props.filter.subOptions &&
                props.filter.name !== "Bộ lọc" &&
                !props.isPrice && (
                    <div className="absolute left-1/2 top-full mt-3 -translate-x-1/2 z-40 bg-white rounded-2xl p-4 w-[340px] shadow-xl border border-gray-200 transition-transform duration-300 ease-out">
                        <div className="flex flex-wrap gap-2">
                            {props.filter.subOptions.map((option, idx) => {
                                const isSelected = props.selectedOptions[props.filter.name]?.includes(option);
                                return (
                                    <div
                                        key={idx}
                                        onClick={() => props.toggleOption(props.filter.name, option)}
                                        className={
                                            "px-4 py-2 rounded-full text-sm font-medium cursor-pointer select-none transition-all duration-200 shadow-sm " +
                                            (isSelected
                                                ? "bg-gradient-to-r from-red-600 to-red-500 text-white shadow-md scale-105 border border-red-600"
                                                : "bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-md hover:scale-105 border border-gray-200")
                                        }
                                    >
                                        {option}
                                    </div>

                                );
                            })}
                        </div>
                        <div className="flex justify-between items-center mt-5 gap-3">
                            <button
                                onClick={props.resetOptions}
                                className="w-1/2 md:w-auto flex items-center justify-center gap-2 
                                bg-gray-100 text-gray-700 font-medium px-4 py-2 rounded-full 
                                hover:bg-gray-200 hover:shadow transition-all duration-200 text-sm"
                            >
                                <RotateCcw className="w-4 h-4" />
                                Thiết lập lại
                            </button>

                            <button
                                onClick={props.closeDropdown}
                                className="w-1/2 md:w-auto flex items-center justify-center gap-2 
                                bg-gradient-to-r from-red-600 to-red-500 text-white font-semibold 
                                px-4 py-2 rounded-full shadow 
                                hover:from-red-700 hover:to-red-600 hover:shadow-lg transition-all duration-200 text-sm"
                            >
                                <X className="w-4 h-4" />
                                Đóng
                            </button>
                        </div>
                    </div>

                )}
        </div>
    );

}

export default FilterButton;
