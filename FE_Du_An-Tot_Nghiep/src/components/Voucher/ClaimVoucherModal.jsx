import React, { useEffect, useState } from "react";
import { FaTimes, FaGift } from "react-icons/fa";
import axios from "axios";
import constants from "../../constants/constants";
import { toast } from "react-toastify";

const ClaimVoucherModal = ({ isOpen, onClose, onClaimSuccess }) => {
  const [vouchers, setVouchers] = useState([]);
  const [loading, setLoading] = useState(true);

  const user = JSON.parse(localStorage.getItem("user"));
  const token = localStorage.getItem("access_token");

  useEffect(() => {
    if (isOpen) {
      const fetchVouchers = async () => {
        try {
          const allRes = await axios.get(`${constants.BASE_URL}/vouchers`, {
            headers: { Authorization: `Bearer ${token}` },
          });

          const userRes = await axios.get(
            `${constants.BASE_URL}/user/${user.id}/vouchers`,
            { headers: { Authorization: `Bearer ${token}` } }
          );

          const claimedCodes = userRes.data.map((v) => v.code);

          // lọc bỏ đã nhận & hết lượt
          const mapped = allRes.data
            .map((v) => {
              const outOfQuota =
                v.usage_limit !== null && v.used >= v.usage_limit;
              return {
                ...v,
                claimed: claimedCodes.includes(v.code),
                outOfQuota,
              };
            })
            .filter((v) => !v.outOfQuota && !v.claimed);

          setVouchers(mapped);
        } catch (err) {
          console.error("Lỗi tải voucher:", err);
          toast.error("Không thể tải danh sách voucher");
        } finally {
          setLoading(false);
        }
      };

      fetchVouchers();
    }
  }, [isOpen, token, user?.id]);

  const handleClaimVoucher = async (voucherId) => {
    try {
      await axios.post(
        `${constants.BASE_URL}/user/${user.id}/vouchers/${voucherId}`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      );

      toast.success("Voucher đã được thêm vào ví của bạn!");

      setVouchers((prev) =>
        prev.filter((v) => v.id !== voucherId) // ẩn khỏi danh sách ngay sau khi nhận
      );

      if (onClaimSuccess) onClaimSuccess();
      onClose();
    } catch (err) {
      toast.error(err.response?.data?.message || "Không thể nhận voucher");
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 px-4"  style={{ marginTop: "0px" }}>
      <div className="bg-white rounded-lg w-full max-w-md sm:max-w-lg p-4 sm:p-6 shadow-xl relative max-h-[80vh] overflow-y-auto">
        <button
          onClick={onClose}
          className="absolute top-3 right-3 text-gray-500 hover:text-red-500"
        >
          <FaTimes size={20} />
        </button>

        <h2 className="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <FaGift className="text-red-600" /> Nhận Voucher
        </h2>

        {loading ? (
          <p className="text-gray-500 text-center py-6">Đang tải voucher...</p>
        ) : vouchers.length === 0 ? (
          <p className="text-gray-500 text-center py-6">
            Không còn voucher nào để nhận
          </p>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-80 overflow-y-auto pr-1">
            {vouchers.map((v) => (
              <div
                key={v.id}
                className="p-4 border rounded-xl bg-white shadow-sm hover:border-red-400 transition flex flex-col justify-between"
              >
                <div>
                  <p className="text-sm font-bold text-gray-800">{v.code}</p>
                  <p className="text-xs text-gray-500 mt-1">
                    {v.type === "percent"
                      ? `Giảm ${Number(v.value)}%`
                      : `Giảm ${Number(v.value).toLocaleString()}đ`}
                  </p>
                  <p className="text-xs text-gray-400 mt-1">
                    HSD: {new Date(v.end_date).toLocaleDateString("vi-VN")}
                  </p>
                </div>

                <button
                  onClick={() => handleClaimVoucher(v.id)}
                  className="mt-4 px-4 py-2 rounded-lg text-sm font-medium bg-red-500 text-white hover:bg-red-600 transition"
                >
                  Nhận
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default ClaimVoucherModal;
