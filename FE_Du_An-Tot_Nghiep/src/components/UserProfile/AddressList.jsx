import React, { useContext, useState } from 'react';
import { FaEdit, FaTrash, FaSpinner } from 'react-icons/fa';
import logo from '../../assets/1ce3169b-4ad7-4038-94d9-00ce4bd19d86.png';
import AddressModal from './AddressModal/AddressModal';
import { useShippingAddress } from '../../contexts/ShippingAddressContext';
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const AddressList = () => {
    const { addresses, addOrUpdateAddress, deleteAddress, loading } = useShippingAddress();
    const [currentPage, setCurrentPage] = useState(1);
    const [showModal, setShowModal] = useState(false);
    const [editData, setEditData] = useState(null);
    const perPage = 4;

    const handleAddAddress = () => {
        setEditData(null);
        setShowModal(true);
    };

    const handleEdit = (id) => {
        const selected = addresses.find(addr => addr.id === id);
        setEditData(selected);
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        Swal.fire({
            title: "Xác nhận xoá?",
            text: "Bạn có chắc chắn muốn xoá địa chỉ này?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Xoá ngay",
            cancelButtonText: "Huỷ",
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await deleteAddress(id);
                } catch (err) {
                    console.error("Lỗi khi xoá địa chỉ:", err.response?.data || err.message);
                }
            }
        });
    };

    const totalPages = Math.ceil(addresses.length / perPage);
    const paginate = (items) => items.slice((currentPage - 1) * perPage, currentPage * perPage);

    return (
        <>
            <div className="space-y-4">
                <div className="flex justify-between items-center border-b pb-3">
                    <h3 className="text-base sm:text-lg font-semibold text-gray-800">Sổ địa chỉ</h3>
                    <button
                        onClick={handleAddAddress}
                        className="text-red-600 text-xs sm:text-sm font-medium flex items-center gap-1"
                    >
                        + Thêm địa chỉ
                    </button>
                </div>

                {loading ? (
                    <ul className="space-y-2 min-h-[300px]">
                        {Array.from({ length: 4 }).map((_, index) => (
                            <li
                                key={index}
                                className="border p-3 rounded-md flex justify-between items-center animate-pulse"
                            >
                                <div className="flex-1">
                                    <div className="h-4 bg-gray-300 rounded w-1/2 mb-2"></div>
                                    <div className="h-3 bg-gray-200 rounded w-3/4"></div>
                                    <div className="h-2 bg-gray-200 rounded w-1/4 mt-2"></div>
                                </div>
                                <div className="flex gap-3">
                                    <div className="h-5 w-5 bg-gray-300 rounded"></div>
                                    <div className="h-5 w-5 bg-gray-300 rounded"></div>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : addresses.length === 0 ? (
                    <div className="text-center py-12">
                        <img
                            src={logo}
                            alt="empty-address"
                            className="w-20 h-20 sm:w-28 sm:h-28 mx-auto mb-2"
                        />
                        <p className="text-sm text-gray-500">
                            Bạn chưa có địa chỉ nào được tạo
                        </p>
                    </div>
                ) : (
                    <>
                        <ul className="space-y-2 min-h-[300px]">
                            {paginate(addresses).map((addr) => (
                                <li
                                    key={addr.id}
                                    className="border p-3 rounded-md flex justify-between items-center"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {addr.recipient_name} - {addr.phone}
                                        </p>
                                        <p className="text-gray-600">
                                            {addr.address}, {addr.ward}, {addr.city}
                                        </p>
                                        {addr.is_default && (
                                            <span className="text-xs text-green-600">Mặc định</span>
                                        )}
                                    </div>
                                    <div className="flex gap-3 text-gray-600">
                                        <button
                                            onClick={() => handleEdit(addr.id)}
                                            className="hover:text-blue-600"
                                        >
                                            <FaEdit />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(addr.id)}
                                            className="hover:text-red-600"
                                        >
                                            <FaTrash />
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        {totalPages > 1 && (
                            <div className="flex justify-center gap-2 mt-3">
                                <button
                                    onClick={() => setCurrentPage((p) => Math.max(p - 1, 1))}
                                    disabled={currentPage === 1}
                                    className="px-3 py-1 border rounded disabled:opacity-50"
                                >
                                    ←
                                </button>
                                {Array.from({ length: totalPages }, (_, i) => (
                                    <button
                                        key={i}
                                        onClick={() => setCurrentPage(i + 1)}
                                        className={`px-3 py-1 border rounded ${currentPage === i + 1 ? "bg-red-500 text-white" : ""
                                            }`}
                                    >
                                        {i + 1}
                                    </button>
                                ))}
                                <button
                                    onClick={() => setCurrentPage((p) => Math.min(p + 1, totalPages))}
                                    disabled={currentPage === totalPages}
                                    className="px-3 py-1 border rounded disabled:opacity-50"
                                >
                                    →
                                </button>
                            </div>
                        )}
                    </>
                )}

            </div>

            <AddressModal
                visible={showModal}
                onClose={() => setShowModal(false)}
                onSubmit={addOrUpdateAddress}
                editData={editData}
            />
        </>
    );
};

export default AddressList;
