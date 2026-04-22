// src/pages/PaymentReturnPage.jsx
import React, { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { checkPaymentResult } from './paymentService';

const PaymentResult = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const verifyPayment = async () => {
      try {
        const queryParams = Object.fromEntries(
          new URLSearchParams(location.search)
        );
        
        const paymentResult = await checkPaymentResult(queryParams);
        setResult(paymentResult);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    verifyPayment();
  }, [location]);

  if (loading) {
    return (
      <div className="max-w-2xl mx-auto p-6 text-center">
        <div className="flex justify-center mb-4">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        </div>
        <h2 className="text-xl font-medium">Đang xác minh kết quả thanh toán...</h2>
        <p className="text-gray-600 mt-2">Vui lòng đợi trong giây lát</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto p-6">
        <div className="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-red-800">Lỗi xác minh thanh toán</h3>
              <div className="mt-2 text-sm text-red-700">
                <p>{error}</p>
              </div>
            </div>
          </div>
        </div>
        
        <button
          onClick={() => navigate('/')}
          className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
        >
          Quay về trang chủ
        </button>
      </div>
    );
  }

  const isSuccess = result.success;
  const amount = result.amount ? result.amount.toLocaleString('vi-VN') : '';

  return (
    <div className="max-w-2xl mx-auto p-6">
      <div className={`rounded-lg p-6 ${isSuccess ? 'bg-green-50' : 'bg-yellow-50'}`}>
        <div className="flex items-center mb-4">
          <div className={`flex-shrink-0 ${isSuccess ? 'text-green-400' : 'text-yellow-400'}`}>
            {isSuccess ? (
              <svg className="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            ) : (
              <svg className="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            )}
          </div>
          <div className="ml-4">
            <h2 className="text-2xl font-bold">
              {isSuccess ? 'Thanh toán thành công!' : 'Thanh toán không thành công'}
            </h2>
            <p className="mt-1 text-gray-600">
              {isSuccess 
                ? 'Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.' 
                : 'Vui lòng thử lại hoặc chọn phương thức thanh toán khác.'}
            </p>
          </div>
        </div>

        <div className="border-t border-gray-200 mt-4 pt-4">
          <dl className="grid grid-cols-2 gap-4">
            <div>
              <dt className="text-sm font-medium text-gray-500">Mã giao dịch</dt>
              <dd className="mt-1 text-sm font-medium">
                {result.transaction_ref || 'N/A'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Số tiền</dt>
              <dd className="mt-1 text-sm font-medium">
                {amount ? `${amount}₫` : 'N/A'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Mã phản hồi</dt>
              <dd className="mt-1 text-sm font-medium">
                {result.response_code || 'N/A'}
              </dd>
            </div>
            <div>
              <dt className="text-sm font-medium text-gray-500">Thời gian</dt>
              <dd className="mt-1 text-sm font-medium">
                {new Date().toLocaleString('vi-VN')}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div className="mt-6 flex justify-between">
        <button
          onClick={() => navigate('/orders')}
          className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
        >
          Xem đơn hàng
        </button>
        
        {!isSuccess && (
          <button
            onClick={() => navigate('/checkout')}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Thử thanh toán lại
          </button>
        )}
        
        <button
          onClick={() => navigate('/')}
          className="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-900"
        >
          Tiếp tục mua sắm
        </button>
      </div>
    </div>
  );
};

export default PaymentResult;