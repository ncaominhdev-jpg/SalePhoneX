import React, { useState, useRef, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
  FaBars, FaMapMarkerAlt, FaSearch, FaPhoneAlt, FaShoppingCart, FaUser
} from 'react-icons/fa';
import logo from '../../assets/logo2.png';
import CategoryMenu from '../CategoryMenu/CategoryMenu';
import LocationModal from '../LocationModal/LocationModal';

const Header = () => {
  const [showCategories, setShowCategories] = useState(false);
  const categoryRef = useRef();
  const location = useLocation();
  const isCheckoutPage = location.pathname === '/checkout';
  const [showLocationModal, setShowLocationModal] = useState(false);
  const [selectedLocation, setSelectedLocation] = useState('Cần Thơ');

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (categoryRef.current && !categoryRef.current.contains(event.target)) {
        setShowCategories(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleToggleCategory = () => {
    setShowCategories(!showCategories);
  };

  const renderOverlay = () => (
    showCategories && <div className="fixed inset-0 bg-black bg-opacity-50 z-40" />
  );

  const renderCategoryMenu = () => (
    <div className="relative z-50" ref={categoryRef}>
      <button
        onClick={handleToggleCategory}
        className="flex items-center gap-2 bg-red-700 px-4 py-2 rounded-md hover:bg-red-500 transition"
      >
        <FaBars className="text-lg" />
        <span className="font-semibold hidden sm:inline">Danh mục</span>
      </button>

      {showCategories && (
        <div className="absolute top-full mt-2 lg:mt-6 left-0 lg:left-auto lg:right-0">
          <CategoryMenu onCloseMenu={() => setShowCategories(false)} />
        </div>

      )}

    </div>
  );

  const renderLocationModal = () => (
    <LocationModal
      visible={showLocationModal}
      selected={selectedLocation}
      onClose={() => setShowLocationModal(false)}
      onSelect={(loc) => {
        setSelectedLocation(loc);
        setShowLocationModal(false);
      }}
    />
  );

  const mobileHeader = () => (
    <header className="bg-red-600 text-white sticky top-0 z-50 shadow-md text-sm lg:hidden">
      <div className="max-w-[1300px] mx-auto flex items-center justify-between px-2 h-16 gap-2 overflow-visible">
        <Link
          to="/"
          className="flex items-center h-16 transition-transform duration-300 ease-in-out hover:scale-[1.1] flex-shrink-0"
        >
          <img
            src={logo}
            alt="Logo"
            className="h-14 md:h-20 w-auto object-contain drop-shadow-md"
          />
        </Link>

        <div className="flex-grow mx-1 min-w-0">
          <div className="flex items-center bg-white rounded-md overflow-hidden shadow">
            <input
              type="text"
              placeholder="Bạn cần tìm gì?"
              className="flex-grow px-2 py-2 text-xs text-gray-800 outline-none min-w-0"
            />
            <button className="px-2 text-gray-600 hover:text-red-600 transition">
              <FaSearch />
            </button>
          </div>
        </div>

        <button
          disabled={isCheckoutPage}
          onClick={() => setShowLocationModal(true)}
          className={`flex items-center px-2 py-2 rounded-md transition max-w-[110px] flex-shrink-0
            ${isCheckoutPage
              ? 'bg-red-500 opacity-70 cursor-not-allowed'
              : 'bg-red-700 hover:bg-red-500 cursor-pointer'
            }`}
        >
          <FaMapMarkerAlt className="text-lg flex-shrink-0" />
          <span className="ml-1 text-sm md:font-bold truncate">{selectedLocation} ▾</span>
        </button>

        <div className="hidden sm:flex items-center gap-2">
          <Link
            to="/cart"
            className="flex items-center gap-1 bg-red-700 px-2 py-2 rounded-md hover:bg-red-500 transition"
            title="Giỏ hàng"
          >
            <FaShoppingCart className="text-lg" />
          </Link>

          <Link
            to="/login"
            className="flex items-center gap-1 bg-red-700 px-2 py-2 rounded-md hover:bg-red-500 transition"
            title="Đăng nhập"
          >
            <FaUser className="text-lg" />
          </Link>
        </div>
      </div>
    </header>
  );

  const desktopHeader = () => (
    <header className="bg-red-600 text-white sticky top-0 z-50 shadow-md text-sm hidden lg:block">
      <div className="max-w-[1300px] mx-auto flex items-center px-4 h-20 gap-3 relative">
        <Link
          to="/"
          className="flex items-center h-20 transition-transform duration-300 ease-in-out hover:scale-[1.1]"
        >
          <img
            src={logo}
            alt="Logo"
            className="h-[90px] w-auto object-contain drop-shadow-md"
          />
        </Link>

        {!isCheckoutPage && renderCategoryMenu()}

        <div
          onClick={!isCheckoutPage ? () => setShowLocationModal(true) : undefined}
          className={`flex items-center gap-2 px-4 py-2 rounded-md transition
            ${isCheckoutPage ? 'bg-red-500 opacity-70 cursor-not-allowed' : 'bg-red-700 hover:bg-red-500 cursor-pointer'}`}
        >
          <FaMapMarkerAlt className="text-lg" />
          <span className="font-bold text-sm">{selectedLocation} ▾</span>
        </div>

        <div className="flex-grow min-w-0">
          <div className="flex items-center bg-white rounded-md overflow-hidden shadow w-full">
            <input
              type="text"
              placeholder="Bạn cần tìm gì?"
              className="w-full px-3 py-2 text-sm text-gray-800 outline-none"
            />
            <button className="px-3 text-gray-600 hover:text-red-600 transition">
              <FaSearch />
            </button>
          </div>
        </div>

        <button className="flex items-center gap-2 bg-red-700 px-4 py-2 rounded-md hover:bg-red-500 transition">
          <FaPhoneAlt className="text-lg" />
          <span className="font-bold text-sm">1800.2097</span>
        </button>

        <div className="flex items-center gap-2">
          <Link
            to="/cart"
            className="flex items-center gap-1 bg-red-700 px-3 py-2 rounded-md hover:bg-red-500 transition"
            title="Xem giỏ hàng"
          >
            <FaShoppingCart className="text-lg" />
            <span className="hidden sm:inline">Giỏ hàng</span>
          </Link>

          <Link
            to="/login"
            className="flex items-center gap-1 bg-red-700 px-3 py-2 rounded-md hover:bg-red-500 transition"
            title="Đăng nhập"
          >
            <FaUser className="text-lg" />
            <span className="hidden sm:inline">Đăng nhập</span>
          </Link>
        </div>
      </div>
    </header>
  );

  return (
    <>
      {renderOverlay()}
      {mobileHeader()}
      {desktopHeader()}
      {renderLocationModal()}
    </>
  );
};

export default Header;
