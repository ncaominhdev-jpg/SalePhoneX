import React from 'react';
import Header from '../../components/Header/Header';
import Footer from '../../components/Footer/Footer';
import BottomNav from '../../components/BottomNav/BottomNav';
import { Outlet } from 'react-router-dom';

const ClientLayout = () => {
    return (
        <>
            <Header />
            <main className="pb-16"> {/* Thêm padding-bottom */}
                <Outlet />
            </main>
            <div className="hidden lg:block">
                <Footer />
            </div>
            <BottomNav />
        </>
    );
};

export default ClientLayout;
