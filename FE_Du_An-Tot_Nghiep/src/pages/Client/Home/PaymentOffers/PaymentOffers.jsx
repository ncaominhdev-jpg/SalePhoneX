import React from 'react';
import PaymentOffers1 from "../../../../assets/uu-dai-hsbc-01-2025-slide-28-05.webp";
import PaymentOffers2 from "../../../../assets/techcom.webp";
import PaymentOffers3 from "../../../../assets/HOMECREDIT.webp";
import PaymentOffers4 from "../../../../assets/vitebank-09-04.webp";

const offers = [
  {
    title: 'HSBC',
    desc: 'Ưu Đãi Thanh Toán',
    detail: 'Hoàn đến 2 Triệu',
    image: PaymentOffers1,
  },
  {
    title: 'Techcombank',
    desc: 'Ưu Đãi Trả Góp Qua Thẻ Tín Dụng',
    detail: 'Giảm 800K',
    image: PaymentOffers2,
  },
  {
    title: 'Home Credit',
    desc: 'Ưu Đãi Thẻ Tín Dụng',
    detail: 'Giảm ngay 400K',
    image: PaymentOffers3,
  },
  {
    title: 'VietBank',
    desc: 'Ưu Đãi Thanh Toán Thẻ Tín Dụng',
    detail: 'Giảm đến 1 Triệu',
    image: PaymentOffers4,
  },
];

const PaymentOffers = () => {
  return (
    <div className="px-4 py-6">
      <h2 className="text-xl sm:text-2xl font-bold text-gray-800 mb-4">
        ƯU ĐÃI THANH TOÁN
      </h2>

      <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        {offers.map((offer, index) => (
          <div
            key={index}
            className="rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:scale-[1.02] transition-transform border border-gray-200 bg-white cursor-pointer"
          >
            <img
              src={offer.image}
              alt={offer.title}
              className="w-full h-28 sm:h-32 object-cover"
            />
          </div>
        ))}
      </div>
    </div>
  );
};

export default PaymentOffers;
