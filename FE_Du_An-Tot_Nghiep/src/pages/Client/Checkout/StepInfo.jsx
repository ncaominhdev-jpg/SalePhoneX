import React, { useState, useEffect } from "react";
import axios from "axios";
import { FaChevronRight, FaCheckCircle, FaRegFolderOpen } from "react-icons/fa";
import AddressModal from "../../../components/AddressModal/AddressModal";
import { useCity } from "../../../contexts/CityContext";
import Swal from "sweetalert2";
import { useAvailableProducts } from "../../../hooks/useAvailableProducts";

const inputClass =
    "w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm transition";

const normalizeStr = (str) =>
    str
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace(/\s+/g, "");

const StepInfo = ({ onNext }) => {
    const user = JSON.parse(localStorage.getItem("user")) || {};
    const { selectedCity } = useCity();
    const { availableProducts } = useAvailableProducts();

    const [selectedStore, setSelectedStore] = useState("");
    const [storeList, setStoreList] = useState([]);
    const [shipMethod, setShipMethod] = useState("store");
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedAddress, setSelectedAddress] = useState(null);
    const [homeName, setHomeName] = useState("");
    const [homePhone, setHomePhone] = useState("");
    const [homeNote, setHomeNote] = useState("");
    const [homeStreet, setHomeStreet] = useState("");
    const [storeAddress, setStoreAddress] = useState("");
    const [provinces, setProvinces] = useState([]);
    const [wards, setWards] = useState([]);
    const [selectedProvince, setSelectedProvince] = useState("");
    const [selectedWard, setSelectedWard] = useState("");
    const [checkoutItems, setCheckoutItems] = useState([]);
    const [provinceName, setProvinceName] = useState("");
    const [wardName, setWardName] = useState("");
    const [filteredProvinces, setFilteredProvinces] = useState([]);
    const [filteredWards, setFilteredWards] = useState([]);

    useEffect(() => {
        const items = JSON.parse(localStorage.getItem("checkoutItems")) || [];
        setCheckoutItems(items);
    }, []);

    useEffect(() => {
        const savedMethod = localStorage.getItem("returnShipMethod");
        if (savedMethod) {
            setShipMethod(savedMethod);
            localStorage.removeItem("returnShipMethod");
        }
    }, []);

    useEffect(() => {
        const savedInfo = JSON.parse(localStorage.getItem("shippingInfo")) || null;
        if (savedInfo) {
            setShipMethod(savedInfo.method || "store");
            if (savedInfo.method === "store") {
                setSelectedStore(savedInfo.storeId || "");
                setStoreAddress(savedInfo.storeAddress || "");
            } else {
                setHomeName(savedInfo.name || "");
                setHomePhone(savedInfo.phone || "");
                setProvinceName(savedInfo.provinceName || "");
                setWardName(savedInfo.wardName || "");
                setHomeStreet(savedInfo.street || "");
                setHomeNote(savedInfo.note || "");
                setSelectedProvince(savedInfo.province || "");
                setSelectedWard(savedInfo.ward || "");
            }
        }
    }, []);

    useEffect(() => {
        const savedInfo = JSON.parse(localStorage.getItem("shippingInfo")) || null;
        if (savedInfo?.method === "store" && storeList.length > 0) {
            const store = storeList.find(s => s.branch_id === Number(savedInfo.storeId));
            if (store) {
                setSelectedStore(store.branch_id);
                setStoreAddress(
                    `${store.branch_name} - ${store.branch_address} - ${store.branch_ward} - ${selectedCity}`
                );
            }
        }
    }, [storeList, selectedCity]);

    // Fetch provinces
    useEffect(() => {
        axios.get("/vngeo/api/provinces").then((res) => {
            setProvinces(res.data);
        });
    }, []);

    // Fetch wards when province changes
    useEffect(() => {
        if (selectedProvince) {
            axios
                .get(
                    `/vngeo/api/wards?province_code=${selectedProvince}`
                )
                .then((res) => {
                    setWards(res.data);
                    setSelectedWard("");
                });
        }
    }, [selectedProvince]);

    // Load store list theo thành phố
    useEffect(() => {
        if (shipMethod !== "store" || !selectedCity || checkoutItems.length === 0) {
            return;
        }

        const storesByBranch = {};
        availableProducts
            .filter((p) => p.branch_city === selectedCity)
            .forEach((p) => {
                if (!storesByBranch[p.branch_id]) {
                    storesByBranch[p.branch_id] = {
                        branch_id: p.branch_id,
                        branch_name: p.branch_name,
                        branch_address: p.branch_address,
                        branch_ward: p.branch_ward,
                        variants: [],
                    };
                }
                storesByBranch[p.branch_id].variants.push({
                    variant_id: p.variant_id,
                    quantity: Number(p.quantity),
                });
            });

        const allStores = Object.values(storesByBranch);

        const validStores = allStores.filter((store) =>
            checkoutItems.every((item) => {
                const stock = store.variants.find(
                    (v) => v.variant_id === item.variantId
                );
                return stock && stock.quantity >= item.quantity;
            })
        );

        // 🔥 chỉ update khi giá trị khác trước đó
        setStoreList((prev) => {
            const isSame =
                JSON.stringify(prev) === JSON.stringify(validStores);
            return isSame ? prev : validStores;
        });

        if (validStores.length > 0) {
            setSelectedStore((prev) =>
                prev === validStores[0].branch_id ? prev : validStores[0].branch_id
            );
            setStoreAddress((prev) => {
                const newAddress = `${validStores[0].branch_name} - ${validStores[0].branch_address} - ${validStores[0].branch_ward} - ${selectedCity}`;
                return prev === newAddress ? prev : newAddress;
            });
        } else {
            setSelectedStore("");
            setStoreAddress("Không có chi nhánh nào đủ số lượng trong khu vực này");
        }
    }, [shipMethod, selectedCity, availableProducts, checkoutItems]);


    const validateForm = () => {
        if (shipMethod === "store") {
            return selectedCity && selectedStore;
        }
        if (shipMethod === "home") {
            return (
                selectedAddress ||
                (homeName.trim() &&
                    homePhone.trim() &&
                    (selectedProvince || provinceName.trim()) &&
                    (selectedWard || wardName.trim()) &&
                    homeStreet.trim())
            );
        }
        return false;
    };

    const handleNext = () => {
        if (!validateForm()) {
            Swal.fire(
                "Thiếu thông tin",
                "Vui lòng điền đầy đủ thông tin trước khi tiếp tục",
                "warning"
            );
            return;
        }

        // ✅ Lưu lựa chọn vào localStorage cho bước sau
        const shippingInfo =
            shipMethod === "store"
                ? {
                    method: "store",
                    storeId: selectedStore,
                    storeAddress,
                }
                : {
                    method: "home",
                    name: homeName,
                    phone: homePhone,
                    province: selectedProvince,
                    provinceName: provinceName,
                    ward: selectedWard,
                    wardName: wardName,
                    street: homeStreet,
                    note: homeNote,
                };

        localStorage.setItem("shippingInfo", JSON.stringify(shippingInfo));
        onNext();
    };

    const handleSelectSavedAddress = async (addr) => {
        setSelectedAddress(addr);
        setHomeName(addr.recipient_name);  // ✅ đúng field
        setHomePhone(addr.phone);          // ✅ đúng field
        setHomeStreet(addr.address);       // ✅ đúng field

        const province = provinces.find(
            (p) => normalizeStr(p.name) === normalizeStr(addr.city)
        );
        if (province) {
            setSelectedProvince(province.province_code);
            setProvinceName(province.name);
        }

        try {
            const res = await axios.get(
                `/vngeo/api/wards?province_code=${province.province_code}`
            );
            setWards(res.data);

            const ward = res.data.find(
                (w) => normalizeStr(w.ward_name) === normalizeStr(addr.ward)
            );
            if (ward) {
                setSelectedWard(ward.ward_code);
                setWardName(ward.ward_name);
            }
        } catch (err) {
            console.error("Error loading wards", err);
        }
    };

    const handleUnselectSavedAddress = () => {
        setSelectedAddress(null);
        setHomeName("");
        setHomePhone("");
        setHomeStreet("");
        setSelectedProvince("");
        setSelectedWard("");
        setWards([]);
    };

    const handleStoreChange = (e) => {
        const storeId = e.target.value;
        const store = storeList.find((s) => s.branch_id === Number(storeId));
        if (store) {
            setSelectedStore(store.branch_id);
            setStoreAddress(
                `${store.branch_name} - ${store.branch_address} - ${store.branch_ward} - ${selectedCity}`
            );
        }
    };

    return (
        <div className="space-y-2">
            <AddressModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSelect={handleSelectSavedAddress}
                selectedId={selectedAddress?.id}
                onUnselect={handleUnselectSavedAddress}
            />

            {/* Thông tin khách hàng */}
            <div className="space-y-2">
                <h2 className="text-base font-semibold text-gray-800">
                    Thông tin khách hàng
                </h2>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input className={inputClass} value={user.name || ""} readOnly />
                    <input className={inputClass} value={user.phone || ""} readOnly />
                    <input className={inputClass} value={user.email || ""} readOnly />
                </div>
            </div>

            {/* Thông tin nhận hàng */}
            <div className="space-y-2">
                <h2 className="text-base font-semibold text-gray-800">
                    Thông tin nhận hàng
                </h2>
                <div className="flex flex-row items-center gap-2 sm:gap-4 text-sm">
                    <label
                        className={`flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer ${shipMethod === "store"
                            ? "bg-gray-100 border-red-500 text-red-600 font-medium"
                            : "border-gray-300"
                            }`}
                    >
                        <input
                            type="radio"
                            name="method"
                            checked={shipMethod === "store"}
                            onChange={() => setShipMethod("store")}
                        />
                        Nhận tại cửa hàng
                    </label>
                    <label
                        className={`flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer ${shipMethod === "home"
                            ? "bg-gray-100 border-red-500 text-red-600 font-medium"
                            : "border-gray-300"
                            }`}
                    >
                        <input
                            type="radio"
                            name="method"
                            checked={shipMethod === "home"}
                            onChange={() => setShipMethod("home")}
                        />
                        Giao hàng tận nơi
                    </label>
                </div>
            </div>

            {shipMethod === "home" && (
                <div className="space-y-2">
                    {/* Tên & SĐT */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div className="sm:col-span-2">
                            <label htmlFor="homeName" className="block mb-1 text-sm font-medium text-gray-700">
                                Tên người nhận
                            </label>
                            <input
                                id="homeName"
                                className={inputClass}
                                placeholder="Tên người nhận"
                                value={selectedAddress ? selectedAddress.recipient_name : homeName}
                                readOnly={!!selectedAddress}
                                onChange={(e) => setHomeName(e.target.value)}
                            />
                        </div>

                        <div>
                            <label htmlFor="homePhone" className="block mb-1 text-sm font-medium text-gray-700">
                                Số điện thoại
                            </label>
                            <input
                                id="homePhone"
                                className={inputClass}
                                placeholder="Số điện thoại"
                                value={selectedAddress ? selectedAddress.phone : homePhone}
                                readOnly={!!selectedAddress}
                                onChange={(e) => setHomePhone(e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Địa chỉ chi tiết */}
                    <div className="grid grid-cols-1 sm:grid-cols-7 gap-4">
                        {selectedAddress ? (
                            <>
                                <div className="sm:col-span-2">
                                    <label className="block mb-1 text-sm font-medium text-gray-700">Tỉnh / Thành phố</label>
                                    <input className={inputClass} value={selectedAddress.city} readOnly />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="block mb-1 text-sm font-medium text-gray-700">Phường / Xã</label>
                                    <input className={inputClass} value={selectedAddress.ward} readOnly />
                                </div>
                                <div className="sm:col-span-3">
                                    <label className="block mb-1 text-sm font-medium text-gray-700">Số nhà, tên đường</label>
                                    <input className={inputClass} value={selectedAddress.address} readOnly />
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="relative sm:col-span-2">
                                    <label htmlFor="province" className="block mb-1 text-sm font-medium text-gray-700">
                                        Tỉnh / Thành phố
                                    </label>
                                    <input
                                        id="province"
                                        className={inputClass}
                                        placeholder="Nhập Tỉnh / TP"
                                        value={provinceName}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            setProvinceName(value);
                                            setFilteredProvinces(
                                                provinces.filter((p) =>
                                                    p.name.toLowerCase().includes(value.toLowerCase())
                                                )
                                            );
                                        }}
                                    />
                                    {provinceName && filteredProvinces.length > 0 && (
                                        <ul className="absolute z-10 bg-white border border-gray-200 rounded-md shadow-md max-h-48 overflow-y-auto w-full mt-1">
                                            {filteredProvinces.map((p) => (
                                                <li
                                                    key={p.province_code}
                                                    onClick={() => {
                                                        setProvinceName(p.name);
                                                        setSelectedProvince(p.province_code);
                                                        setFilteredProvinces([]);
                                                    }}
                                                    className="px-3 py-2 cursor-pointer hover:bg-red-100"
                                                >
                                                    {p.name}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="relative sm:col-span-2">
                                    <label htmlFor="ward" className="block mb-1 text-sm font-medium text-gray-700">
                                        Phường / Xã
                                    </label>
                                    <input
                                        id="ward"
                                        className={inputClass}
                                        placeholder="Nhập Phường / Xã"
                                        value={wardName}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            setWardName(value);
                                            setFilteredWards(
                                                wards.filter((w) =>
                                                    w.ward_name.toLowerCase().includes(value.toLowerCase())
                                                )
                                            );
                                        }}
                                        disabled={!wards.length}
                                    />
                                    {wardName && filteredWards.length > 0 && (
                                        <ul className="absolute z-10 bg-white border border-gray-200 rounded-md shadow-md max-h-48 overflow-y-auto w-full mt-1">
                                            {filteredWards.map((w) => (
                                                <li
                                                    key={w.ward_code}
                                                    onClick={() => {
                                                        setWardName(w.ward_name);
                                                        setSelectedWard(w.ward_code);
                                                        setFilteredWards([]);
                                                    }}
                                                    className="px-3 py-2 cursor-pointer hover:bg-red-100"
                                                >
                                                    {w.ward_name}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>

                                <div className="sm:col-span-3">
                                    <label htmlFor="street" className="block mb-1 text-sm font-medium text-gray-700">
                                        Số nhà, tên đường
                                    </label>
                                    <input
                                        id="street"
                                        className={inputClass}
                                        placeholder="Số nhà, tên đường"
                                        value={homeStreet}
                                        onChange={(e) => setHomeStreet(e.target.value)}
                                    />
                                </div>
                            </>
                        )}
                    </div>

                    {/* Ghi chú */}
                    <div className="mt-2">
                        <label htmlFor="note" className="block mb-1 text-sm font-medium text-gray-700">
                            Ghi chú thêm
                        </label>
                        <textarea
                            id="note"
                            rows={3}
                            className={`${inputClass}`}
                            placeholder="Ghi chú thêm (nếu có)"
                            value={homeNote}
                            onChange={(e) => setHomeNote(e.target.value)}
                        />
                    </div>


                    <button
                        onClick={() => setIsModalOpen(true)}
                        className={`flex items-center justify-center gap-2 w-full sm:w-[280px] px-4 py-3 rounded-lg transition text-sm border 
                            ${selectedAddress
                                ? "bg-green-100 border-green-400 text-green-700 hover:bg-green-200"
                                : "bg-gray-100 border-gray-300 text-gray-700 hover:bg-gray-200"
                            }`}
                    >
                        {selectedAddress ? (
                            <>
                                <FaCheckCircle className="text-green-500" />
                                Đã chọn địa chỉ
                            </>
                        ) : (
                            <>
                                <FaRegFolderOpen className="text-gray-500" />
                                Chọn địa chỉ đã lưu
                            </>
                        )}
                    </button>
                </div>
            )}

            {shipMethod === "store" && (
                <div className="space-y-2">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {/* Thành phố đã chọn */}
                        <div>
                            <label className="block mb-1 text-sm font-medium text-gray-700">
                                Thành phố
                            </label>
                            <input
                                className={`${inputClass} bg-gray-100 cursor-not-allowed`}
                                value={selectedCity || ""}
                                readOnly
                            />
                        </div>

                        {/* Chọn chi nhánh hoặc hiển thị thông báo */}
                        <div className="sm:col-span-2">
                            <label className="block mb-1 text-sm font-medium text-gray-700">
                                Chọn chi nhánh
                            </label>
                            {storeList.length > 0 ? (
                                <div className="relative">
                                    <div className="relative">
                                        <select
                                            className={`${inputClass}`}
                                            value={selectedStore}
                                            onChange={handleStoreChange}
                                        >
                                            {storeList.length === 0 ? (
                                                <option disabled className="text-red-600 font-medium">
                                                    Xin lỗi, không có chi nhánh nào ở {selectedCity} đủ số lượng bạn yêu cầu
                                                </option>
                                            ) : (
                                                storeList.map((store) => (
                                                    <option
                                                        key={store.branch_id}
                                                        value={store.branch_id}
                                                        className="text-gray-700 font-medium bg-white hover:bg-red-100"
                                                    >
                                                        {store.branch_name} - {store.branch_address} - {store.branch_ward}
                                                    </option>
                                                ))
                                            )}
                                        </select>

                                    </div>
                                </div>

                            ) : (
                                <input
                                    className={`${inputClass} bg-gray-100 cursor-not-allowed`}
                                    value={storeAddress}
                                    readOnly
                                />
                            )}
                        </div>
                    </div>

                    {/* Ghi chú thêm */}
                    <div>
                        <label htmlFor="storeNote" className="block mb-1 text-sm font-medium text-gray-700">
                            Ghi chú cho cửa hàng
                        </label>
                        <textarea
                            id="storeNote"
                            rows={3}
                            className={`${inputClass} resize-none`}
                            placeholder="VD: Mở cửa 8h-21h hàng ngày, gọi trước khi đến..."
                        />
                    </div>
                </div>
            )}

            <div className="text-right">
                <button
                    onClick={handleNext}
                    disabled={!validateForm()}
                    className={`inline-flex items-center px-6 py-3 rounded-full text-sm font-medium transition ${validateForm()
                        ? "bg-red-600 hover:bg-red-700 text-white"
                        : "bg-gray-300 text-gray-500 cursor-not-allowed"
                        }`}
                >
                    Tiếp tục <FaChevronRight className="ml-2" />
                </button>
            </div>
        </div>
    );
};

export default StepInfo;
