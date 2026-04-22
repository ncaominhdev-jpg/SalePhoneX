import React, { useState } from 'react';
import SidebarMenu from '../../../components/UserProfile/SidebarMenu';
import AddressList from '../../../components/UserProfile/AddressList';
import OrderHistory from '../../../components/UserProfile/OrderHistory';
import CombinedProfileForm from '../../../components/UserProfile/CombinedProfileForm';
import WishlistPage from '../../../components/UserProfile/WishlistPage';
import VoucherPage from '../../../components/UserProfile/VoucherPage';

const UserProfile = () => {
  const [tab, setTab] = useState('info');

  return (
    <div className="max-w-7xl mx-auto px-2 sm:px-4 py-6 sm:py-10 min-h-[70vh]">
      <div className="flex flex-col lg:flex-row gap-4 sm:gap-6">

        {/* Sidebar */}
        <SidebarMenu tab={tab} setTab={setTab} />

        {/* Main Content */}
        <div className="flex-1 bg-white rounded-xl p-4 sm:p-6 text-xs sm:text-sm shadow-[0_0_15px_rgba(0,0,0,0.1)]">
          {tab === 'address' && <AddressList />}
          {tab === 'history' && <OrderHistory />}
          {tab === 'info' && <CombinedProfileForm />}
          {tab === 'lovely' && <WishlistPage />}
          {tab === 'voucher' && <VoucherPage />}
        </div>

      </div>
    </div>
  );
};

export default UserProfile;
