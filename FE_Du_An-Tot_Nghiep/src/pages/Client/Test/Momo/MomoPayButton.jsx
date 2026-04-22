// src/pages/Client/Test/Momo/MomoPayButton.jsx
import React, { useState } from 'react';
import { createMomoPayment } from './MomoPaymentService';

const MomoPayButton = ({ 
  amount, 
  orderInfo, 
  extraData = '', 
  onPaymentStart, 
  onError 
}) => {
  const [isLoading, setIsLoading] = useState(false);

  const handlePayment = async () => {
    setIsLoading(true);
    onPaymentStart?.();
    
    try {
      const paymentData = {
        amount: amount.toString(),
        orderInfo,
        extraData
      };
      
      const result = await createMomoPayment(paymentData);
      
      if (result.resultCode === 0 && result.payUrl) {
        window.location.href = result.payUrl;
      } else {
        onError?.(result.message || 'Lỗi khởi tạo thanh toán MoMo');
      }
    } catch (error) {
      onError?.(error.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <button 
      onClick={handlePayment}
      disabled={isLoading}
      className="momo-button bg-pink-600 text-white px-6 py-3 rounded-lg shadow hover:bg-pink-700 transition w-full"
    >
      {isLoading ? (
        <span className="flex items-center justify-center">
          <svg className="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Đang xử lý...
        </span>
      ) : (
        <span className="flex items-center justify-center">
          <img 
            src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-MoMo.png" 
            alt="MoMo" 
            className="w-8 h-8 mr-2"
          />
          Thanh toán bằng MoMo
        </span>
      )}
    </button>
  );
};

export default MomoPayButton;