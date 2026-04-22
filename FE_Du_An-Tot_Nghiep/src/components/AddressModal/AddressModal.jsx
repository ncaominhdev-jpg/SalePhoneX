import React from "react";
import { useShippingAddress } from "../../contexts/ShippingAddressContext";
import { FaMapMarkerAlt, FaPhoneAlt, FaCheckCircle, FaTimes } from "react-icons/fa"

const AddressModal = ({ isOpen, onClose, selectedId, onSelect, onUnselect }) => {
    const { addresses } = useShippingAddress();

    if (!isOpen) return null;

    const handleClick = (addr) => {
        if (selectedId === addr.id) {
            onUnselect();
        } else {
            onSelect(addr);
        }
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 px-4">
            <div className="bg-white rounded-2xl w-full max-w-md sm:max-w-lg p-5 sm:p-6 shadow-2xl relative max-h-[80vh] overflow-y-auto animate-fadeIn">
                {/* Tiêu đề */}
                <h2 className="text-lg sm:text-xl font-bold mb-5 text-center text-gray-800 flex items-center justify-center gap-2">
                    <FaMapMarkerAlt className="text-red-500" /> Chọn địa chỉ giao hàng
                </h2>

                {/* Danh sách địa chỉ */}
                {addresses.length === 0 ? (
                    <p className="text-center text-sm text-gray-500 bg-gray-50 py-6 rounded-lg">
                        Chưa có địa chỉ nào
                    </p>
                ) : (
                    <div className="space-y-4">
                        {addresses.map((addr) => (
                            <div
                                key={addr.id}
                                onClick={() => handleClick(addr)}
                                className={`p-4 border rounded-xl cursor-pointer transition-all duration-200 shadow-sm ${selectedId === addr.id
                                        ? "bg-green-50 border-green-500 shadow-md hover:bg-green-100"
                                        : "border-gray-200 hover:bg-gray-50"
                                    }`}
                            >
                                <div className="flex justify-between items-center">
                                    <div>
                                        <div className="font-semibold text-gray-800 flex items-center gap-2">
                                            <FaCheckCircle
                                                className={`text-lg ${selectedId === addr.id ? "text-green-600" : "text-gray-300"
                                                    }`}
                                            />
                                            {addr.recipient_name}
                                        </div>
                                        <div className="text-gray-600 text-sm flex items-center gap-2 mt-1">
                                            <FaPhoneAlt className="text-gray-500" />
                                            {addr.phone}
                                        </div>
                                    </div>
                                </div>

                                <div className="text-gray-600 text-xs sm:text-sm mt-2 flex gap-2 items-start">
                                    <FaMapMarkerAlt className="text-red-500 mt-0.5" />
                                    <span>
                                        {addr.address}, {addr.ward}, {addr.district}, {addr.city}
                                    </span>
                                </div>

                                {selectedId === addr.id && (
                                    <p className="text-green-600 text-xs mt-2 font-medium flex items-center gap-1">
                                        <FaCheckCircle /> Đã chọn
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {/* Nút đóng */}
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition"
                >
                    <FaTimes className="text-xl" />
                </button>
            </div>
        </div>
    );
};

export default AddressModal;
