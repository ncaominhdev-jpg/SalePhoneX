import { FaMoneyBillWave, FaChevronLeft } from "react-icons/fa";
import VNPAY from "../../../assets/Logo-VNPAY-QR-1-300x96.webp";
import MOMO from "../../../assets/MoMo_Logo.png";

const StepPayment = ({ data, onBack }) => {
  return (
    <div className="bg-white p-2 rounded-2xl flex flex-col justify-between min-h-[400px]">
      {/* Nội dung trên */}
      <div>
        <h3 className="text-xl text-center font-semibold text-gray-800 border-b pb-6">
          PHƯƠNG THỨC THANH TOÁN
        </h3>

        <div className="grid grid-cols-1 sm:grid-cols-7 gap-5 mt-6">
          {/* COD */}
          <label
            className={`group flex items-center gap-4 border rounded-xl p-5 cursor-pointer transition col-span-2 sm:col-span-3
              ${data.paymentMethod === "cod"
                ? "border-red-500 bg-red-50 shadow-md"
                : "border-gray-200 hover:border-red-300 hover:bg-gray-50"
              }`}
          >
            <input
              type="radio"
              className="hidden"
              checked={data.paymentMethod === "cod"}
              onChange={() => data.setPaymentMethod("cod")}
            />
            <FaMoneyBillWave
              className={`text-2xl transition-colors 
                ${data.paymentMethod === "cod"
                  ? "text-red-600"
                  : "text-gray-400 group-hover:text-red-500"}`}
            />
            <span
              className={`font-medium transition-colors 
                ${data.paymentMethod === "cod"
                  ? "text-red-600"
                  : "text-gray-700 group-hover:text-red-500"}`}
            >
              Thanh toán khi nhận hàng
            </span>
          </label>

          {/* VNPay */}
          <label
            className={`group flex items-center justify-center border rounded-xl p-5 cursor-pointer transition col-span-2
              ${data.paymentMethod === "vnpay"
                ? "border-red-500 bg-red-50 shadow-md"
                : "border-gray-200 hover:border-red-300 hover:bg-gray-50"
              }`}
          >
            <input
              type="radio"
              className="hidden"
              checked={data.paymentMethod === "vnpay"}
              onChange={() => data.setPaymentMethod("vnpay")}
            />
            <img src={VNPAY} alt="VNPay" className="w-20 h-10 object-contain" />
          </label>

          {/* MoMo */}
          <label
            className={`group flex items-center justify-center border rounded-xl p-5 cursor-pointer transition col-span-2
              ${data.paymentMethod === "momo"
                ? "border-red-500 bg-red-50 shadow-md"
                : "border-gray-200 hover:border-red-300 hover:bg-gray-50"
              }`}
          >
            <input
              type="radio"
              className="hidden"
              checked={data.paymentMethod === "momo"}
              onChange={() => data.setPaymentMethod("momo")}
            />
            <img src={MOMO} alt="MoMo" className="w-20 h-10 object-contain" />
          </label>
        </div>
      </div>

      {/* Nút quay lại */}
      <div className="flex justify-start pt-8">
        <button
          onClick={() => {
            const shippingInfo =
              JSON.parse(localStorage.getItem("shippingInfo")) || {};
            if (shippingInfo.method) {
              localStorage.setItem("returnShipMethod", shippingInfo.method);
            }
            onBack();
          }}
          className="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm bg-gray-200 hover:bg-red-500 hover:text-white text-gray-700 font-medium shadow-md transition-all duration-200"
        >
          <FaChevronLeft className="text-base" />
          Quay lại
        </button>
      </div>
    </div>
  );
};

export default StepPayment;
