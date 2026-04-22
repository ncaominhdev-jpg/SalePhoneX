import React, { useEffect, useState } from "react";
import OrderSummary from "./OrderSummary";
import StepInfo from "./StepInfo";
import StepPayment from "./StepPayment";
import { useCity } from "../../../contexts/CityContext";
import { useNavigate } from "react-router-dom";
import { toast } from "react-toastify"; // giữ 1 lần thôi
import Swal from "sweetalert2";
import axios from "axios";
import constants from "../../../constants/constants";

const Checkout = () => {
  const [step, setStep] = useState(1);
  const [paymentMethod, setPaymentMethod] = useState("");
  const [checkoutItems, setCheckoutItems] = useState([]);
  const { selectedCity } = useCity();
  const navigate = useNavigate();

  const [userInfo, setUserInfo] = useState({
    recipient_name: "",
    phone: "",
    address: "",
    province: "",
    city: "",
    ward: "",
    note: "",
  });

  // kiểm tra giỏ hàng
  useEffect(() => {
    const stored = localStorage.getItem("checkoutItems");
    const parsed = stored ? JSON.parse(stored) : [];
    if (parsed.length > 0) {
      setCheckoutItems(parsed);
    } else {
      Swal.fire({
        title: "Giỏ hàng trống!",
        html: `
          <div style="font-size:15px; color:#444; margin-top:10px;">
            Có vẻ như bạn chưa chọn sản phẩm nào.<br/>
            <span style="color:#e53e3e; font-weight:bold;">
              Khám phá ngay những ưu đãi hấp dẫn hôm nay!
            </span>
          </div>
        `,
        background: "#fff",
        iconColor: "#e53e3e",
        showCancelButton: true,
        confirmButtonText: "Tiếp tục mua sắm",
        cancelButtonText: "Trang chủ",
        reverseButtons: true,
        customClass: {
          popup: "rounded-2xl shadow-2xl p-6",
          title: "text-xl font-bold text-gray-800 mb-2",
          confirmButton:
            "px-6 py-3 rounded-lg font-semibold bg-red-600 text-white hover:bg-red-700 transition transform hover:-translate-y-0.5",
          cancelButton:
            "px-6 py-3 rounded-lg font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300 transition transform hover:-translate-y-0.5",
        },
      }).then(() => navigate("/"));
    }
  }, [navigate]);

  // kiểm tra tỉnh thành
  useEffect(() => {
    if (!selectedCity) {
      toast.error("Vui lòng chọn Tỉnh/Thành trước khi tiến hành thanh toán!");
      navigate(-1);
    }
  }, [selectedCity, navigate]);

  // quản lý voucher
  const [voucherCode, setVoucherCode] = useState("");
  const [discount, setDiscount] = useState(0);
  const [finalTotal, setFinalTotal] = useState(0);
  const [vouchers, setVouchers] = useState([]);
  const [showVoucherList, setShowVoucherList] = useState(false);

  useEffect(() => {
    const stored = localStorage.getItem("checkoutItems");
    if (stored) setCheckoutItems(JSON.parse(stored));

    const user = JSON.parse(localStorage.getItem("user"));
    const token = localStorage.getItem("access_token");
    if (user && token) {
      axios
        .get(`${constants.BASE_URL}/user/${user.id}/vouchers`, {
          headers: { Authorization: `Bearer ${token}` },
        })
        .then((res) => setVouchers(res.data))
        .catch(() => setVouchers([]));
    }
  }, []);

  const applyVoucher = async (code) => {
    try {
      // Nếu user muốn bỏ voucher
      if (!code) {
        setVoucherCode("");
        setDiscount(0);
        // Tính lại subtotal gốc
        const subtotal = checkoutItems.reduce(
          (acc, p) => acc + p.price * p.quantity,
          0
        );
        setFinalTotal(subtotal);
        toast.info("Đã bỏ áp dụng voucher");
        return; // ✅ thoát hàm, không gọi API
      }

      // Nếu user áp dụng voucher
      const subtotal = checkoutItems.reduce(
        (acc, p) => acc + p.price * p.quantity,
        0
      );
      const res = await axios.post(`${constants.BASE_URL}/vouchers/apply`, {
        code,
        total_amount: subtotal,
      });

      setVoucherCode(code);
      setDiscount(res.data.discount);
      setFinalTotal(res.data.final_total);

      toast.success(
        <div>
          <div>Áp dụng thành công!</div>
          <div>Giảm {Number(res.data.discount).toLocaleString("vi-VN")} đ</div>
        </div>
      );
    } catch (err) {
      toast.error(err.response?.data?.message || "Voucher không hợp lệ");
      setVoucherCode("");
      setDiscount(0);
      setFinalTotal(0);
    }
  };

  return (
    <div className="max-w-[1280px] mx-auto py-2 px-2 sm:py-6 sm:px-4 lg:py-8 lg:px-6">
      <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 flex items-center gap-3 mb-6">
        <span className="w-2 h-8 sm:h-10 bg-gradient-to-b from-red-600 to-red-400 rounded-full shadow-md"></span>
        <span className="tracking-wide uppercase">Thanh Toán</span>
      </h2>

      <div className=" grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-8">
        {/* form thông tin + thanh toán */}
        <div className="lg:col-span-2 bg-white rounded-xl sm:rounded-2xl shadow-lg p-4 sm:p-4 lg:p-6 border border-neutral-200">
          <div className="relative">
            {/* Các step */}
            <div className="flex items-center justify-between mb-4 relative">
              {/* Step 1 */}
              <div className="flex flex-col items-center flex-1">
                <div
                  className={`w-10 h-10 flex items-center justify-center rounded-full font-bold ${step === 1
                    ? "bg-red-600 text-white shadow-lg"
                    : "bg-gray-200 text-gray-500"
                    }`}
                >
                  1
                </div>
                <p
                  className={`mt-3 mb-2 text-sm font-semibold ${step === 1 ? "text-red-600" : "text-gray-400"
                    }`}
                >
                  THÔNG TIN
                </p>
              </div>

              {/* Step 2 */}
              <div className="flex flex-col items-center flex-1">
                <div
                  className={`w-10 h-10 flex items-center justify-center rounded-full font-bold ${step === 2
                    ? "bg-red-600 text-white shadow-lg"
                    : "bg-gray-200 text-gray-500"
                    }`}
                >
                  2
                </div>
                <p
                  className={`mt-3 mb-2 text-sm font-semibold ${step === 2 ? "text-red-600" : "text-gray-400"
                    }`}
                >
                  THANH TOÁN
                </p>
              </div>
            </div>

            {/* Thanh gạch đỏ */}
            <div className="absolute bottom-0 left-0 w-full h-1 bg-gray-200 rounded">
              <div
                className="h-1 bg-red-600 rounded transition-all duration-500"
                style={{
                  width: "50%", // vì có 2 step → mỗi step chiếm 50%
                  transform: step === 1 ? "translateX(0%)" : "translateX(100%)",
                }}
              />
            </div>
          </div>

          {/* Nội dung Step */}
          <div className="">
            {step === 1 ? (
              <StepInfo
                onNext={() => setStep(2)}
                setUserInfo={setUserInfo}
                userInfo={userInfo}
              />
            ) : (
              <StepPayment
                data={{ items: checkoutItems, paymentMethod, setPaymentMethod, userInfo }}
                onBack={() => setStep(1)}
              />
            )}
          </div>
        </div>

        {/* tóm tắt + voucher */}
        <div>
          <OrderSummary
            paymentMethod={paymentMethod}
            checkoutItems={checkoutItems}
            discount={discount}
            finalTotal={finalTotal}
            voucherCode={voucherCode}
            vouchers={vouchers}
            onApplyVoucher={applyVoucher}
            onPaymentSuccess={(usedVoucher) => {
              if (usedVoucher) {
                setVouchers((prev) => prev.filter((v) => v.code !== usedVoucher));
                setVoucherCode("");
                setDiscount(0);
                setFinalTotal(0);
              }
            }}
          />

        </div>
      </div>
    </div>
  );
};

export default Checkout;