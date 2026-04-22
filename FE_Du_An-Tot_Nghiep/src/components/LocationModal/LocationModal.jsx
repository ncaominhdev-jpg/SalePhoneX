import React, { useState } from 'react';
import { FaSearch, FaTimes, FaCheckCircle } from 'react-icons/fa';
import { useCity } from "../../contexts/CityContext";

const LocationModal = ({ visible, onClose }) => {
    const { cityList, selectedCity, setSelectedCity } = useCity();
    const [search, setSearch] = useState('');

    if (!visible) return null;

    const filteredCities = cityList.filter((city) =>
        city.toLowerCase().includes(search.toLowerCase())
    );

    const handleSelect = (city) => {
        setSelectedCity(city);
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-40 backdrop-blur-sm z-[10000] flex items-center justify-center px-4">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-full sm:max-w-[500px] md:max-w-[640px] p-4 sm:p-5 md:p-6 relative animate-fadeIn max-h-screen overflow-y-auto">

                {/* Header */}
                <div className="flex items-center justify-between mb-4 sm:mb-5">
                    <h2 className="text-base sm:text-lg font-bold text-gray-800">Chọn tỉnh/thành phố</h2>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-red-600 transition"
                        title="Đóng"
                    >
                        <FaTimes className="text-lg sm:text-xl" />
                    </button>
                </div>

                {/* Input Search */}
                <div className="relative mb-3 sm:mb-4">
                    <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm" />
                    <input
                        type="text"
                        placeholder="Tìm theo tên tỉnh thành..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                    />
                </div>

                {/* Description */}
                <p className="text-gray-500 mb-3 text-sm">
                    Vui lòng chọn tỉnh/thành để biết chính xác giá, khuyến mãi và tồn kho:
                </p>

                {/* City List */}
                <div className="grid grid-cols-2 sm:grid-cols-2 gap-2 max-h-[300px] overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    {filteredCities.length > 0 ? (
                        filteredCities.map((city, idx) => (
                            <div
                                key={idx}
                                onClick={() => handleSelect(city)}
                                className={`flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition text-sm
                                ${selectedCity === city
                                        ? "bg-red-100 text-red-600 font-semibold"
                                        : "hover:bg-gray-100 text-gray-700"}
                                `}
                            >
                                <span>{city}</span>
                                {selectedCity === city && (
                                    <FaCheckCircle className="text-red-500 text-xs" />
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="col-span-2 text-center text-gray-400 text-sm py-6">
                            Không tìm thấy tỉnh/thành nào.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default LocationModal;
