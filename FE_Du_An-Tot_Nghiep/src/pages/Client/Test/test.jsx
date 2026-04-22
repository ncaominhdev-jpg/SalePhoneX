// App.js
import React, { useState, useEffect } from 'react';
import PaymentTable from './PaymentReturnPage';
import VNPayButton from './VNPayButton';
import axios from 'axios';

function Thanh() {
  const [currentOrder, setCurrentOrder] = useState({
    orderId: 123,
    userId: 1,
    amount: 100000
  });
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [user, setUser] = useState(null);
  const [loginForm, setLoginForm] = useState({
    email: 'test@example.com',
    password: 'password123'
  });
  const [loginLoading, setLoginLoading] = useState(false);
  const [loginError, setLoginError] = useState('');

  useEffect(() => {
    // Kiểm tra trạng thái đăng nhập
    const token = localStorage.getItem('access_token');
    const userData = localStorage.getItem('user');
    
    if (token && userData) {
      setIsLoggedIn(true);
      setUser(JSON.parse(userData));
      // Cập nhật userId từ user đã đăng nhập
      setCurrentOrder(prev => ({
        ...prev,
        userId: JSON.parse(userData).id
      }));
    } else {
      setIsLoggedIn(false);
      setUser(null);
    }
  }, []);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoginLoading(true);
    setLoginError('');

    try {
      const response = await axios.post('http://localhost:8000/api/login', loginForm);
      
      if (response.data.access_token) {
        localStorage.setItem('access_token', response.data.access_token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        setIsLoggedIn(true);
        setUser(response.data.user);
        setCurrentOrder(prev => ({
          ...prev,
          userId: response.data.user.id
        }));
      }
    } catch (error) {
      setLoginError(error.response?.data?.message || 'Đăng nhập thất bại');
    } finally {
      setLoginLoading(false);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user');
    setIsLoggedIn(false);
    setUser(null);
  };

  return (
    <div className="container py-4">
      <h1 className="mb-4">Quản lý Thanh toán</h1>
      
      {!isLoggedIn ? (
        <div className="card mb-4">
          <div className="card-header">
            <h3>Đăng nhập để test thanh toán</h3>
          </div>
          <div className="card-body">
            <form onSubmit={handleLogin}>
              <div className="mb-3">
                <label className="form-label">Email</label>
                <input 
                  type="email" 
                  className="form-control"
                  value={loginForm.email}
                  onChange={e => setLoginForm({...loginForm, email: e.target.value})}
                  required
                />
              </div>
              
              <div className="mb-3">
                <label className="form-label">Mật khẩu</label>
                <input 
                  type="password" 
                  className="form-control"
                  value={loginForm.password}
                  onChange={e => setLoginForm({...loginForm, password: e.target.value})}
                  required
                />
              </div>
              
              {loginError && (
                <div className="alert alert-danger" role="alert">
                  {loginError}
                </div>
              )}
              
              <button 
                type="submit" 
                className="btn btn-primary"
                disabled={loginLoading}
              >
                {loginLoading ? 'Đang đăng nhập...' : 'Đăng nhập'}
              </button>
            </form>
            
            <div className="mt-3">
              <small className="text-muted">
                Hoặc bạn có thể <a href="/register">đăng ký tài khoản mới</a> hoặc <a href="/login">đăng nhập tại đây</a>
              </small>
            </div>
          </div>
        </div>
      ) : (
        <div className="alert alert-success" role="alert">
          <strong>Đã đăng nhập:</strong> Xin chào {user?.name || user?.email}!
          <button 
            onClick={handleLogout}
            className="btn btn-sm btn-outline-secondary ms-3"
          >
            Đăng xuất
          </button>
        </div>
      )}
      
      <div className="card mb-4">
        <div className="card-header">
          <h2>Tạo thanh toán mới</h2>
        </div>
        <div className="card-body">
          <div className="mb-3">
            <label className="form-label">Mã đơn hàng</label>
            <input 
              type="number" 
              className="form-control"
              value={currentOrder.orderId}
              onChange={e => setCurrentOrder({...currentOrder, orderId: e.target.value})}
            />
          </div>
          
          <div className="mb-3">
            <label className="form-label">Mã người dùng</label>
            <input 
              type="number" 
              className="form-control"
              value={currentOrder.userId}
              onChange={e => setCurrentOrder({...currentOrder, userId: e.target.value})}
              disabled={isLoggedIn} // Disable nếu đã đăng nhập
            />
            {isLoggedIn && (
              <small className="form-text text-muted">
                User ID được tự động lấy từ tài khoản đã đăng nhập
              </small>
            )}
          </div>
          
          <div className="mb-3">
            <label className="form-label">Số tiền (VND)</label>
            <input 
              type="number" 
              className="form-control"
              value={currentOrder.amount}
              onChange={e => setCurrentOrder({...currentOrder, amount: e.target.value})}
            />
          </div>
          
          <VNPayButton 
            orderId={currentOrder.orderId}
            userId={currentOrder.userId}
            amount={currentOrder.amount}
          />
        </div>
      </div>
      
      <div className="card">
        <div className="card-header">
          <h2>Lịch sử Thanh toán</h2>
        </div>
        <div className="card-body">
          <PaymentTable />
        </div>
      </div>
    </div>
  );
}

export default Thanh;