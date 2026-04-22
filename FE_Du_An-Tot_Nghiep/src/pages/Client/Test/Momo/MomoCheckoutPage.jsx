// src/pages/Client/Test/Momo/MomoCheckoutPage.jsx
import React, { useState } from 'react';
import MomoPayButton from './MomoPayButton';
import { Link } from 'react-router-dom';

const MomoCheckoutPage = () => {
  const [amount, setAmount] = useState(10000);
  const [orderInfo, setOrderInfo] = useState('Thanh toán đơn hàng qua MoMo');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handlePaymentStart = () => {
    setError('');
    setSuccess('');
  };

  const handleError = (message) => {
    setError(message);
  };

  return (
    <div className="max-w-md mx-auto p-6 bg-white rounded-lg shadow-lg">
      <div className="mb-4">
        <Link to="/client/test" className="text-blue-600 hover:underline flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clipRule="evenodd" />
          </svg>
          Quay lại
        </Link>
      </div>
      
      <h1 className="text-2xl font-bold text-center text-gray-800 mb-6">Thanh Toán MoMo</h1>
      
      <div className="mb-6">
        <label className="block text-gray-700 mb-2">Số tiền (VND):</label>
        <input
          type="number"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
          className="w-full p-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
          min="10000"
        />
      </div>
      
      <div className="mb-6">
        <label className="block text-gray-700 mb-2">Mô tả đơn hàng:</label>
        <textarea
          value={orderInfo}
          onChange={(e) => setOrderInfo(e.target.value)}
          className="w-full p-3 border rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
          rows="2"
        />
      </div>
      
      <div className="mb-6">
        <MomoPayButton
          amount={amount}
          orderInfo={orderInfo}
          onPaymentStart={handlePaymentStart}
          onError={handleError}
        />
      </div>
      
      {error && (
        <div className="p-4 bg-red-50 text-red-700 rounded-lg mb-4">
          <p className="font-medium">Lỗi:</p>
          <p>{error}</p>
        </div>
      )}
      
      {success && (
        <div className="p-4 bg-green-50 text-green-700 rounded-lg">
          <p className="font-medium">Thành công:</p>
          <p>{success}</p>
        </div>
      )}
    </div>
  );
};

export default MomoCheckoutPage;