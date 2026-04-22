// src/pages/Client/Test/Momo/MomoPaymentService.jsx
import axios from 'axios';

const API_BASE = 'http://localhost:8000/api';

export const createMomoPayment = async (paymentData) => {
  try {
    const response = await axios.post(`${API_BASE}/momo-payment`, paymentData);
    return response.data;
  } catch (error) {
    throw new Error('Khởi tạo thanh toán MoMo thất bại: ' + (error.response?.data?.message || error.message));
  }
};

export const verifyMomoPayment = async (orderId) => {
  try {
    const response = await axios.get(`${API_BASE}/verify-momo-payment/${orderId}`);
    return response.data;
  } catch (error) {
    throw new Error('Xác minh thanh toán MoMo thất bại: ' + error.message);
  }
};