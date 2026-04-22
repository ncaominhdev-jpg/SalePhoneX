import React, { useState, useRef, useEffect, useContext } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import {
  FaBars, FaMapMarkerAlt, FaPhoneAlt, FaShoppingCart, FaUser, FaSignOutAlt, FaChevronDown
} from 'react-icons/fa';
import { toast } from 'react-toastify';
import logo from '../../assets/logo2.png';
import constants from '../../constants/constants';
import CategoryMenu from '../CategoryMenu/CategoryMenu';
import LocationModal from '../LocationModal/LocationModal';
import { UserContext } from '../../contexts/UserContext';
import { ProductShopContext } from '../../contexts/ProductShopContext';
import SearchBox from './SearchBox/SearchBox';
import { useCity } from "../../contexts/CityContext";
import { useCart } from "../../contexts/CartContext";
import { useWishlist } from "../../hooks/useWishlist";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const Header = () => {
  const [showCategories, setShowCategories] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);
  const { selectedCity, setSelectedCity } = useCity();
  const { user, updateUser } = useContext(UserContext);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const userMenuRef = useRef();
  const categoryRef = useRef();
  const navigate = useNavigate();
  const location = useLocation();
  const isCheckoutPage = location.pathname === '/checkout';
  const { searchProducts } = useContext(ProductShopContext);
  const [searchTerm, setSearchTerm] = useState("");
  const { cartItems } = useCart();
  const { wishlist } = useWishlist();

  // class chung cho tất cả button
  const buttonClass = `
    flex items-center gap-2 
     p-2.5
    rounded-lg shadow-sm 
    text-sm font-medium 
    bg-red-700 hover:bg-red-500 
    transition cursor-pointer
  `;

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (categoryRef.current && !categoryRef.current.contains(event.target)) {
        setShowCategories(false);
      }
      if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
        setShowUserMenu(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = async () => {
    const result = await Swal.fire({
      title: "Bạn có chắc muốn đăng xuất?",
      showCancelButton: true,
      confirmButtonText: "Đăng xuất",
      cancelButtonText: "Hủy",
      reverseButtons: true,
      buttonsStyling: false,
      customClass: {
        popup: "w-[90%] sm:w-[400px] rounded-xl p-5",
        title: "text-base sm:text-lg font-semibold text-gray-800",
        htmlContainer: "text-sm sm:text-base text-gray-600",
        actions:
          "flex flex-col sm:flex-row justify-center gap-3 mt-5 w-full",
        confirmButton:
          "w-full sm:w-auto bg-red-600 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-red-700 transition",
        cancelButton:
          "w-full sm:w-auto bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-medium hover:bg-gray-300 transition",
      },
    });

    if (!result.isConfirmed) return;

    const token = localStorage.getItem("access_token") || sessionStorage.getItem("access_token");

    if (token) {
      try {
        await fetch(`${constants.BASE_URL}/logout`, {
          method: "POST",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        });
      } catch (error) {
        console.error("Lỗi khi gọi API đăng xuất:", error);
      }
    }

    updateUser(null);
    localStorage.removeItem("access_token");
    sessionStorage.removeItem("access_token");

    toast.success("Đăng xuất thành công!", {
      position: "top-right",
      autoClose: 2500,
      theme: "colored",
    });

    navigate("/");
  };

  const renderUserSection = () => {
    if (user) {
      return (
        <div ref={userMenuRef} className="relative">
          <button
            onClick={() => setShowUserMenu((prev) => !prev)}
            className={`${buttonClass} max-w-[180px]`}
            title={user.name || "Tài khoản"}
          >
            <FaUser className="text-lg flex-shrink-0" />
            <span className="hidden sm:inline truncate font-medium">
              {user?.name && user.name.length > 12
                ? user.name.substring(0, 12) + "..."
                : user?.name || "Tài khoản"}
            </span>
            <FaChevronDown className="text-xs" />
          </button>

          {showUserMenu && (
            <div
              className="absolute right-0 mt-4 bg-white text-gray-800 shadow-lg rounded-lg 
               z-[9999] min-w-[200px] overflow-hidden text-sm animate-fadeIn"
            >
              <Link
                to="/profile"
                className="flex items-center gap-2 px-4 py-2 group transition-all duration-200 hover:bg-red-50 hover:pl-5"
                onClick={() => setShowUserMenu(false)}
              >
                <FaUser className="text-red-400 group-hover:text-red-600 transition-colors" />
                <span className="font-medium group-hover:text-red-600">Thông tin cá nhân</span>
              </Link>

              <button
                onClick={handleLogout}
                className="w-full flex items-center gap-2 px-4 py-2 group text-red-600 font-medium transition-all duration-200 hover:bg-red-50 hover:pl-5"
              >
                <FaSignOutAlt className="text-red-400 group-hover:text-red-600 transition-colors" />
                <span className="group-hover:text-red-600">Đăng xuất</span>
              </button>
            </div>
          )}

        </div>
      );
    } else {
      return (
        <Link
          to="/login"
          className={buttonClass}
          title="Đăng nhập"
        >
          <FaUser className="text-lg" />
          <span className="hidden sm:inline font-medium">Đăng nhập</span>
        </Link>
      );
    }
  };

  return (
    <>
      {showCategories && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-40" />
      )}

      {/* Mobile */}
      <header className="bg-red-600 text-white sticky top-0 z-50 shadow-md lg:hidden">
        <div className="max-w-7xl mx-auto flex items-center justify-between px-3 h-16 gap-2">
          <Link to="/" className="flex items-center h-full">
            <img
              src={logo}
              alt="Logo"
              className="h-[100px] sm:h-30 w-auto object-contain drop-shadow-md transition-transform duration-300 group-hover:scale-110"
            />
          </Link>

          <div className="flex-grow mx-2 min-w-0">
            <SearchBox />
          </div>

          <button
            disabled={isCheckoutPage}
            onClick={() => setShowLocationModal(true)}
            className={`${buttonClass} ${isCheckoutPage ? "bg-red-500 opacity-70 cursor-not-allowed" : ""}`}
          >
            <FaMapMarkerAlt className="text-lg flex-shrink-0" />
            <span className="truncate max-w-[60px] sm:max-w-[200px]">
              {selectedCity || "Chọn tỉnh"}
            </span>
            <FaChevronDown className="ml-1 text-xs" />
          </button>
        </div>
      </header>

      {/* Desktop */}
      <header className="bg-red-600 text-white sticky top-0 z-50 shadow-md hidden lg:block">
        <div className="max-w-[1300px] mx-auto flex items-center justify-between px-4 h-20 gap-3">

          {/* Logo */}
          <Link to="/" className="flex items-center h-full flex-shrink-0 group">
            <img
              src={logo}
              alt="Logo"
              className="h-[95px] w-auto object-contain drop-shadow-md transition-transform duration-300 ease-out group-hover:scale-110 group-hover:drop-shadow-xl"
            />
          </Link>

          {/* Danh mục */}
          {!isCheckoutPage && (
            <div ref={categoryRef} className="relative flex-shrink-0">
              <button
                onClick={() => setShowCategories(!showCategories)}
                className={`${buttonClass} whitespace-nowrap`}
              >
                <FaBars />
                <span>Danh mục</span>
              </button>
              {showCategories && (
                <div className="absolute top-full mt-4 left-1/4 transform -translate-x-1/2 bg-white rounded-xl shadow-lg overflow-hidden z-50 w-[220px]">
                  <CategoryMenu onCloseMenu={() => setShowCategories(false)} />
                </div>

              )}
            </div>
          )}

          {/* Location */}
          <button
            disabled={isCheckoutPage}
            onClick={() => setShowLocationModal(true)}
            className={`${buttonClass} flex-shrink-0 max-w-[150px] ${isCheckoutPage ? "bg-red-500 opacity-70 cursor-not-allowed" : ""}`}
          >
            <FaMapMarkerAlt className="text-lg flex-shrink-0" />
            <span className="truncate">{selectedCity || "Chọn tỉnh"}</span>
            <FaChevronDown className="ml-1 text-xs flex-shrink-0" />
          </button>

          {/* Search Box - chiếm phần còn lại */}
          <div className="flex-grow min-w-[200px]">
            <SearchBox />
          </div>

          {/* Hotline */}
          <button className={`${buttonClass} flex-shrink-0 whitespace-nowrap`}>
            <FaPhoneAlt />
            <span className="font-bold">1800.2097</span>
          </button>

          {/* Giỏ hàng */}
          <Link to="/cart" className={`relative ${buttonClass} flex-shrink-0`}>
            <FaShoppingCart />
            <span className="hidden sm:inline">Giỏ hàng</span>
            {/* Ẩn số lượng khi không đăng nhập */}
            {user && cartItems.length > 0 && (
              <span className="absolute -top-1 -right-1 bg-white text-red-700 text-xs font-bold rounded-full px-1.5 py-0.5 shadow">
                {cartItems.length}
              </span>
            )}
          </Link>

          {/* User */}
          <div className="flex-shrink-0">
            {renderUserSection()}
          </div>
        </div>
      </header>

      <LocationModal
        visible={showLocationModal}
        selected={selectedCity}
        onClose={() => setShowLocationModal(false)}
        onSelect={(loc) => {
          setSelectedCity(loc);
          setShowLocationModal(false);
        }}
      />
    </>
  );
};

export default Header;
