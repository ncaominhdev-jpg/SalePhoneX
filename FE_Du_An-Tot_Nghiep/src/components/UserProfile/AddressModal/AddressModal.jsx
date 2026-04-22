import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import axios from 'axios';
import { toast } from 'react-toastify';
import { useShippingAddress } from "../../../contexts/ShippingAddressContext";

const AddressModal = ({ visible, onClose, editData }) => {
    const { register, handleSubmit, formState: { errors }, setValue, reset, watch } = useForm();
    const [loading, setLoading] = useState(false);
    const { addOrUpdateAddress } = useShippingAddress();

    const [provinces, setProvinces] = useState([]);
    const [wards, setWards] = useState([]);
    const selectedProvinceCode = watch("city");

    // Lấy danh sách tỉnh
    useEffect(() => {
        axios.get("/vngeo/api/provinces")
            .then((res) => {
                // console.log("✅ Provinces loaded:", res.data);
                setProvinces(res.data);
            })
            .catch(err => console.error("❌ Failed to load provinces", err));
    }, []);

    // Lấy danh sách xã
    useEffect(() => {
        if (selectedProvinceCode) {
            console.log("🔄 Getting wards for province_code:", selectedProvinceCode);
            axios.get(`/vngeo/api/wards?province_code=${selectedProvinceCode}`)
                .then((res) => {
                    // console.log("✅ Wards loaded:", res.data);
                    setWards(res.data);
                })
                .catch(err => console.error("❌ Failed to load wards", err));
        } else {
            setWards([]);
        }
    }, [selectedProvinceCode]);

    // Load dữ liệu chỉnh sửa
    useEffect(() => {
        if (visible && editData && provinces.length > 0) {
            const matchedProvince = provinces.find(p => p.name === editData.city);
            const cityCode = matchedProvince?.province_code || "";

            // console.log("✏️ Edit data:", editData);
            // console.log("👉 Matched cityCode:", cityCode);

            // Đặt city trước để kích hoạt useEffect load wards
            reset({
                ...editData,
                city: cityCode,
                ward: '', // reset tạm thời để tránh giữ giá trị cũ
                is_default: editData.is_default ? 'true' : '',
            });

            if (cityCode) {
                axios.get(`/vngeo/api/wards?province_code=${cityCode}`)
                    .then((res) => {
                        const loadedWards = res.data;
                        // console.log("✅ Wards for edit loaded:", loadedWards);
                        setWards(loadedWards);

                        // ⏳ Đợi wards được set xong mới gán lại giá trị
                        const matchedWard = loadedWards.find(w =>
                            w.ward_name?.toLowerCase().trim() === editData.ward?.toLowerCase().trim()
                        );
                        // console.log("✅ Matched ward:", matchedWard);

                        if (matchedWard) {
                            setTimeout(() => setValue("ward", matchedWard.ward_name), 0); // đảm bảo state đã cập nhật
                        }
                    });
            }

        }
    }, [editData, provinces, visible, reset, setValue]);

    // Reset khi đóng
    useEffect(() => {
        if (!visible) {
            reset({
                recipient_name: "",
                phone: "",
                city: "",
                ward: "",
                address: "",
                is_default: "",
            });
            setWards([]);
        }
    }, [visible, reset]);

    // Submit
    const onInternalSubmit = async (data) => {
        setLoading(true);
        try {
            const provinceName = provinces.find(p => p.province_code === data.city)?.name || "";
            const finalData = {
                ...data,
                city: provinceName,
            };

            console.log("📦 Submitting address:", finalData);

            await addOrUpdateAddress(finalData, editData?.id);

            onClose();
        } catch (err) {
            console.error("❌ Submit error", err);
            toast.error(err.response?.data?.message || "Lưu địa chỉ thất bại.");
        } finally {
            setLoading(false);
        }
    };

    if (!visible) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 px-4">
            <div className="bg-white rounded-xl shadow-lg w-full max-w-md p-4 sm:p-6">
                <h3 className="text-lg font-semibold mb-4">
                    {editData ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới'}
                </h3>
                <form onSubmit={handleSubmit(onInternalSubmit)} className="space-y-3">
                    <input
                        {...register("recipient_name", { required: "Vui lòng nhập họ tên" })}
                        placeholder="Họ tên người nhận"
                        className={inputClass}
                    />
                    {errors.recipient_name && <p className="text-red-500 text-xs">{errors.recipient_name.message}</p>}

                    <input
                        {...register("phone", {
                            required: "Vui lòng nhập số điện thoại",
                            pattern: { value: /^[0-9]{10,11}$/, message: "Số điện thoại không hợp lệ" }
                        })}
                        placeholder="Số điện thoại"
                        className={inputClass}
                    />
                    {errors.phone && <p className="text-red-500 text-xs">{errors.phone.message}</p>}

                    <select
                        {...register("city", { required: "Vui lòng chọn Tỉnh/Thành phố" })}
                        className={inputClass}
                    >
                        <option value="">-- Chọn Tỉnh/Thành phố --</option>
                        {provinces.map((p) => (
                            <option key={p.province_code} value={p.province_code}>
                                {p.name}
                            </option>
                        ))}
                    </select>
                    {errors.city && <p className="text-red-500 text-xs">{errors.city.message}</p>}

                    <select
                        {...register("ward", { required: "Vui lòng chọn Phường/Xã" })}
                        className={inputClass}
                        disabled={!wards.length}
                    >
                        <option value="">-- Chọn Phường/Xã --</option>
                        {wards.map((w, index) => (
                            <option key={`${w.ward_code}-${index}`} value={w.ward_name}>
                                {w.ward_name}
                            </option>
                        ))}
                    </select>

                    {errors.ward && <p className="text-red-500 text-xs">{errors.ward.message}</p>}

                    <textarea
                        {...register("address", { required: "Vui lòng nhập địa chỉ" })}
                        placeholder="Địa chỉ (số nhà, tên đường...)"
                        className={`${inputClass} h-20`}
                    />
                    {errors.address && <p className="text-red-500 text-xs">{errors.address.message}</p>}

                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            value="true"
                            {...register("is_default")}
                            defaultChecked={editData?.is_default}
                        />
                        <label>Đặt làm địa chỉ mặc định</label>
                    </div>

                    <div className="flex justify-end gap-3 pt-2 border-t">
                        <button type="button" onClick={onClose} className="px-4 py-2 rounded-md border">Huỷ</button>
                        <button type="submit" disabled={loading} className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                            {loading ? 'Đang lưu...' : 'Lưu'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

const inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500';

export default AddressModal;