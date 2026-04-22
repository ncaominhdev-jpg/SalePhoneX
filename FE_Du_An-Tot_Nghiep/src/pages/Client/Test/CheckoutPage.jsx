// src/pages/CheckoutPage.jsx
import React, { useState } from 'react';
import VNPayButton from './VNPayButton';

const CheckoutPage = () => {
  const [order] = useState({
    id: 123,
    total: 150000, // 150,000 VND
    items: [
      { id: 1, name: 'Sản phẩm A', price: 50000, quantity: 2 },
      { id: 2, name: 'Sản phẩm B', price: 50000, quantity: 1 },
    ]
  });

  return (
    <div className="max-w-4xl mx-auto p-4">
      <h1 className="text-2xl font-bold mb-6">Thanh Toán Đơn Hàng</h1>
      
      <div className="bg-white shadow rounded-lg p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">Chi tiết đơn hàng #{order.id}</h2>
        
        <div className="mb-4">
          {order.items.map(item => (
            <div key={item.id} className="flex justify-between py-2 border-b">
              <div>
                {item.name} × {item.quantity}
              </div>
              <div>
                {(item.price * item.quantity).toLocaleString('vi-VN')}₫
              </div>
            </div>
          ))}
        </div>
        
        <div className="flex justify-between font-bold text-lg pt-2">
          <div>Tổng cộng:</div>
          <div>{order.total.toLocaleString('vi-VN')}₫</div>
        </div>
      </div>
      
      <div className="bg-white shadow rounded-lg p-6">
        <h2 className="text-xl font-semibold mb-4">Chọn phương thức thanh toán</h2>
        
        <div className="border rounded-lg p-4">
          <h3 className="font-medium mb-3">Thanh toán trực tuyến</h3>
          
          <VNPayButton 
            amount={order.total}
            orderId={order.id}
            userId={1} // ID người dùng thực tế
          />
        </div>
      </div>
    </div>
  );
};

export default CheckoutPage;