import React, { useState, useContext } from 'react';
import { FaHome, FaList, FaShoppingCart, FaUser, FaTimes } from 'react-icons/fa';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import CategoryList from './CategoryList/CategoryList';
import { UserContext } from '../../contexts/UserContext';
import { useCart } from '../../contexts/CartContext'; // 👉 Thêm import

const BottomNav = () => {
    const [showMenu, setShowMenu] = useState(false);
    const { user } = useContext(UserContext);
    const { cartItems } = useCart(); // 👉 Lấy cartItems từ context
    const navigate = useNavigate();
    const location = useLocation();

    const toggleMenu = () => setShowMenu((prev) => !prev);

    const navItems = [
        { name: 'Trang chủ', icon: <FaHome />, path: '/' },
        { name: 'Danh mục', icon: <FaList />, path: '#', onClick: toggleMenu },
        { name: 'Giỏ hàng', icon: <FaShoppingCart />, path: '/cart' },
    ];

    const isActive = (path) => location.pathname === path;

    const renderNavItem = (item, idx) => {
        if (item.onClick) {
            return (
                <button
                    key={idx}
                    onClick={item.onClick}
                    className={
                        'flex flex-col items-center text-xs px-2 ' +
                        (showMenu ? 'text-red-600' : 'text-gray-600')
                    }
                >
                    <div className="relative text-lg">
                        {item.icon}
                    </div>
                    <span>{item.name}</span>
                </button>
            );
        } else {
            return (
                <li key={idx}>
                    <Link
                        to={item.path}
                        className={
                            'flex flex-col items-center text-xs px-2 relative ' +
                            (isActive(item.path) ? 'text-red-600' : 'text-gray-600')
                        }
                    >
                        <div className="relative text-lg">
                            {item.icon}
                            {/* Badge giỏ hàng đẹp hơn */}
                            {item.path === '/cart' && cartItems.length > 0 && (
                                <span className="absolute -top-2 -right-2 bg-red-600 text-white text-[11px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center shadow-md">
                                    {cartItems.reduce((sum, i) => sum + (i.quantity || 1), 0)}
                                </span>
                            )}
                        </div>
                        <span>{item.name}</span>
                    </Link>
                </li>
            );
        }
    };

    return (
        <>
            <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 lg:hidden">
                <ul className="flex justify-around py-2">
                    {navItems.map((item, idx) => renderNavItem(item, idx))}

                    {user ? (
                        <li>
                            <Link
                                to="/profile"
                                className={
                                    'flex flex-col items-center text-xs px-2 ' +
                                    (isActive('/profile') ? 'text-red-600' : 'text-gray-600')
                                }
                            >
                                <div className="text-lg">
                                    <FaUser />
                                </div>
                                <span>{user.name}</span>
                            </Link>
                        </li>
                    ) : (
                        <li>
                            <Link
                                to="/login"
                                className={
                                    'flex flex-col items-center text-xs px-2 ' +
                                    (isActive('/login') ? 'text-red-600' : 'text-gray-600')
                                }
                            >
                                <div className="text-lg">
                                    <FaUser />
                                </div>
                                <span>Đăng nhập</span>
                            </Link>
                        </li>
                    )}
                </ul>
            </nav>

            {showMenu && (
                <div className="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-70">
                    <div className="absolute bottom-12 left-0 right-0 bg-white rounded-t-2xl p-4 overflow-y-auto max-h-[80vh] sm:max-w-md sm:mx-auto sm:rounded-xl">
                        <div className="flex justify-between items-center border-b pb-2">
                            <h3 className="text-base sm:text-lg font-semibold">Danh mục</h3>
                            <button
                                onClick={() => setShowMenu(false)}
                                className="text-gray-600 hover:text-red-600 transition"
                            >
                                <FaTimes className="text-xl" />
                            </button>
                        </div>
                        <CategoryList onSelect={() => setShowMenu(false)} />
                    </div>
                </div>
            )}
        </>
    );
};

export default BottomNav;
