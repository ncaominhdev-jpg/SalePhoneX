// src/pages/Client/Test/Momo/MomoPaymentReturnPage.jsx
import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { verifyMomoPayment } from './MomoPaymentService';

const MomoPaymentReturnPage = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [paymentStatus, setPaymentStatus] = useState('pending');
  const [message, setMessage] = useState('');
  const [orderDetails, setOrderDetails] = useState(null);

  useEffect(() => {
    const queryParams = new URLSearchParams(location.search);
    const orderId = queryParams.get('orderId');
    const resultCode = queryParams.get('resultCode');

    if (!orderId || !resultCode) {
      setPaymentStatus('invalid');
      setMessage('Thông tin thanh toán không hợp lệ');
      return;
    }

    const processPayment = async () => {
      try {
        const result = await verifyMomoPayment(orderId);
        
        if (result.status === 'success') {
          setPaymentStatus('success');
          setMessage('Thanh toán MoMo thành công!');
          setOrderDetails({
            orderId: result.orderId,
            amount: result.amount,
            transactionId: result.transactionId,
            timestamp: new Date().toLocaleString()
          });
        } else {
          setPaymentStatus('failed');
          setMessage(`Thanh toán thất bại: ${result.message || 'Lỗi không xác định'}`);
        }
      } catch (error) {
        setPaymentStatus('error');
        setMessage('Lỗi xác minh thanh toán: ' + error.message);
      }
    };

    processPayment();
  }, [location]);

  return (
    <div className="min-h-screen bg-gray-100 flex items-center justify-center p-4">
      <div className="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden">
        <div className="bg-pink-600 p-6 text-center">
          <h1 className="text-2xl font-bold text-white">Kết Quả Thanh Toán MoMo</h1>
        </div>
        
        <div className="p-6">
          {paymentStatus === 'pending' && (
            <div className="text-center py-10">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-pink-500 mx-auto mb-4"></div>
              <p className="text-gray-600">Đang xác minh kết quả thanh toán...</p>
            </div>
          )}
          
          {paymentStatus === 'success' && (
            <div className="text-center">
              <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <h2 className="text-xl font-bold text-green-600 mb-2">{message}</h2>
              
              {orderDetails && (
                <div className="mt-6 text-left bg-gray-50 rounded-lg p-4">
                  <div className="flex justify-between py-2 border-b">
                    <span className="text-gray-600">Mã đơn hàng:</span>
                    <span className="font-medium">{orderDetails.orderId}</span>
                  </div>
                  <div className="flex justify-between py-2 border-b">
                    <span className="text-gray-600">Số tiền:</span>
                    <span className="font-medium">{Number(orderDetails.amount).toLocaleString()} VND</span>
                  </div>
                  <div className="flex justify-between py-2 border-b">
                    <span className="text-gray-600">Mã giao dịch:</span>
                    <span className="font-medium">{orderDetails.transactionId}</span>
                  </div>
                  <div className="flex justify-between py-2">
                    <span className="text-gray-600">Thời gian:</span>
                    <span className="font-medium">{orderDetails.timestamp}</span>
                  </div>
                </div>
              )}
              
              <button
                onClick={() => navigate('/client/test')}
                className="mt-8 bg-pink-600 hover:bg-pink-700 text-white font-medium py-3 px-6 rounded-lg transition"
              >
                Quay về trang chủ
              </button>
            </div>
          )}
          
          {(paymentStatus === 'failed' || paymentStatus === 'error' || paymentStatus === 'invalid') && (
            <div className="text-center">
              <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg className="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </div>
              <h2 className="text-xl font-bold text-red-600 mb-2">{message}</h2>
              
              <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <button
                  onClick={() => navigate('/client/test/momo-checkout')}
                  className="bg-pink-600 hover:bg-pink-700 text-white font-medium py-3 px-6 rounded-lg transition"
                >
                  Thử lại thanh toán
                </button>
                <button
                  onClick={() => navigate('/client/test')}
                  className="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg transition"
                >
                  Về trang chủ
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MomoPaymentReturnPage;