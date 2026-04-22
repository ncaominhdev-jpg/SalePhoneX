// src/services/paymentService.js
import axios from 'axios';

const API_URL = 'http://localhost:8000/api/payments';

// Tạo thanh toán VNPay
export const createVNPayPayment = async (paymentData) => {
  try {
    const token = localStorage.getItem('access_token'); // sửa lấy token đúng key trong localStorage
    const response = await axios.post(`${API_URL}/vnpay/create`, paymentData, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    return response.data;
  } catch (error) {
    throw new Error(error.response?.data?.message || 'Lỗi tạo thanh toán');
  }
};

// Kiểm tra kết quả thanh toán
export const checkPaymentResult = async (queryParams) => {
  try {
    const response = await axios.get(`${API_URL}/vnpay/return`, {
      params: queryParams
    });
    return response.data;
  } catch (error) {
    throw new Error(error.response?.data?.message || 'Lỗi xác minh thanh toán');
  }
};