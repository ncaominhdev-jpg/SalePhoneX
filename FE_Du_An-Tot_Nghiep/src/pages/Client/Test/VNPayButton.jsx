// src/components/Payment/VNPayButton.jsx
import React, { useState } from 'react';
import axios from 'axios';

const VNPayButton = ({ amount, orderId, userId }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handlePayment = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // Lấy token từ localStorage
      const token = localStorage.getItem('access_token');
      
      if (!token) {
        setError('Vui lòng đăng nhập để thực hiện thanh toán');
        setLoading(false);
        return;
      }

      // Gọi API tạo thanh toán VNPay với authentication
      const response = await axios.post('http://localhost:8000/api/payments/vnpay/create', {
        amount: amount,
        order_id: orderId,
        user_id: userId,
        language: 'vn', // hoặc 'en'
        // bank_code: 'NCB' // (tùy chọn)
      }, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      // Nếu response có access_token thì lưu vào localStorage giống Login.jsx
      if (response.data.access_token) {
        localStorage.setItem('access_token', response.data.access_token);
      }
      if (response.data.user) {
        localStorage.setItem('user', JSON.stringify(response.data.user));
      }

      if (response.data.success) {
        // Chuyển hướng đến trang thanh toán VNPay
        window.location.href = response.data.payment_url;
      } else {
        setError('Không thể tạo liên kết thanh toán');
      }
    } catch (err) {
      if (err.response?.status === 401) {
        setError('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
      } else {
        setError('Lỗi kết nối máy chủ: ' + err.message);
      }
      console.error('Lỗi thanh toán VNPay:', err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="vnpay-button">
      <button 
        onClick={handlePayment}
        disabled={loading}
        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:bg-gray-400"
      >
        {loading ? (
          <span className="flex items-center">
            <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
              <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Đang xử lý...
          </span>
        ) : (
          <span className="flex items-center">
            <img 
              src="https://sandbox.vnpayment.vn/apis/assets/images/logo.svg" 
              alt="VNPay" 
              className="h-6 mr-2"
            />
            Thanh toán VNPay
          </span>
        )}
      </button>
      
      {error && (
        <div className="mt-2 text-red-600 text-sm">
          {error}
        </div>
      )}
      
      <div className="mt-4 text-sm text-gray-600">
        <p>Thanh toán an toàn qua cổng VNPay</p>
        <p>Hỗ trợ thẻ ATM, Visa, Mastercard, Ví điện tử</p>
      </div>
    </div>
  );
};

export default VNPayButton;