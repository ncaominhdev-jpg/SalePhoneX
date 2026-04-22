// src/pages/Client/Test/test.jsx
import React from 'react';
import { Link } from 'react-router-dom';

const TestPage = () => {
  return (
    <div className="min-h-screen bg-gray-100 p-6">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-2xl font-bold text-gray-800 mb-8">Trang Quản Lý Thanh Toán</h1>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Link 
            to="/client/test/momo-checkout" 
            className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition"
          >
            <div className="text-center">
              <div className="bg-pink-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <img 
                  src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-MoMo.png" 
                  alt="MoMo" 
                  className="w-8 h-8"
                />
              </div>
              <h2 className="text-xl font-semibold text-gray-800">Thanh toán MoMo</h2>
              <p className="text-gray-600 mt-2">Thực hiện thanh toán qua MoMo</p>
            </div>
          </Link>
          
          {/* Các phương thức thanh toán khác ở đây */}
        </div>
      </div>
    </div>
  );
};

export default TestPage;