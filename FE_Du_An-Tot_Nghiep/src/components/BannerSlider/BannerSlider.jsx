import React from "react";
import Slider from "react-slick";

import bannerMeizuLucky from "../../assets/banner/meizu-lucky-08-sliding-cate1.webp";
import bannerNothingB2S from "../../assets/banner/nothing-cate-b2s.webp";
import bannerNothing3A from "../../assets/banner/nothing-phone-3a-cate-0625.webp";
import bannerOppoB2S from "../../assets/banner/oppo-cate-b2s.webp";
import bannerOppoReno14 from "../../assets/banner/oppo-reno14-cate.webp";
import bannerRealmeB2S from "../../assets/banner/realme-cate-b2s.webp";
import bannerTecno from "../../assets/banner/tecno-dien-thoai-cate.webp";
import bannerXiaomi from "../../assets/banner/xiaomi-dien-thoai-cate.webp";
import bannerSamsungZ7F7 from "../../assets/banner/z7-f7-pre-cate.webp";
import bannerVoucherCombo from "../../assets/banner/cate-voucher.webp";
import bannerVivoB2S from "../../assets/banner/dienj-thoai-vivo-b2s.webp";
import bannerSamsungB2S from "../../assets/banner/dien-thoai-samsung-b2s.webp";
import bannerInfinixNote50 from "../../assets/banner/infinix-note-50-cate.webp";
import bannerIphone16ProMax from "../../assets/banner/iphone-16-pro-max-cate-0625.webp";

const bannerList1 = [
  bannerMeizuLucky,
  bannerNothingB2S,
  bannerNothing3A,
  bannerOppoB2S,
  bannerOppoReno14,
  bannerRealmeB2S,
  bannerTecno,
];

const bannerList2 = [
  bannerXiaomi,
  bannerSamsungZ7F7,
  bannerVoucherCombo,
  bannerVivoB2S,
  bannerSamsungB2S,
  bannerInfinixNote50,
  bannerIphone16ProMax,
];

const allBanners = [...bannerList1, ...bannerList2];

class NextArrow extends React.Component {
  render() {
    const { onClick } = this.props;
    return (
      <div
        onClick={onClick}
        className="absolute right-2 top-1/2 -translate-y-1/2 z-10 cursor-pointer opacity-0 group-hover:opacity-100 bg-gray-200 hover:bg-gray-300 rounded-full p-1"
      >
        <svg className="w-5 h-5 text-black" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7.05 4.05a.75.75 0 011.06 0L13 8.94a.75.75 0 010 1.06l-4.89 4.89a.75.75 0 11-1.06-1.06L10.94 10 7.05 6.11a.75.75 0 010-1.06z" />
        </svg>
      </div>
    );
  }
}

class PrevArrow extends React.Component {
  render() {
    const { onClick } = this.props;
    return (
      <div
        onClick={onClick}
        className="absolute left-2 top-1/2 -translate-y-1/2 z-10 cursor-pointer opacity-0 group-hover:opacity-100 bg-gray-200 hover:bg-gray-300 rounded-full p-1"
      >
        <svg className="w-5 h-5 text-black" fill="currentColor" viewBox="0 0 20 20">
          <path d="M12.95 4.05a.75.75 0 010 1.06L9.06 10l3.89 3.89a.75.75 0 11-1.06 1.06L7 10.94a.75.75 0 010-1.06l4.89-4.89a.75.75 0 011.06 0z" />
        </svg>
      </div>
    );
  }
}

const settings = {
  dots: true,
  infinite: true,
  speed: 800,
  slidesToShow: 1,
  slidesToScroll: 1,
  autoplay: true,
  autoplaySpeed: 5000,
  nextArrow: <NextArrow />,
  prevArrow: <PrevArrow />,
};

class BannerSlider extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isMobile: window.innerWidth < 768,
    };
  }

  componentDidMount() {
    window.addEventListener("resize", this.handleResize);
  }

  componentWillUnmount() {
    window.removeEventListener("resize", this.handleResize);
  }

  handleResize = () => {
    this.setState({ isMobile: window.innerWidth < 768 });
  };

  renderBannerList(bannerList, keyPrefix) {
    return bannerList.map((banner, index) => (
      <div key={`${keyPrefix}-${index}`}>
        <img
          src={banner}
          alt={`${keyPrefix}-${index}`}
          className="w-full object-contain rounded-xl shadow-md transform transition duration-300 ease-in-out focus:outline-none"
        />
      </div>
    ));
  }

  render() {
    const { isMobile } = this.state;

    return (
      <div className="mb-4">
        {isMobile ? (
          // 👉 Mobile: show 1 slider with all banners
          <div className="overflow-hidden rounded-xl group relative">
            <Slider {...settings}>
              {this.renderBannerList(allBanners, "bannerMobile")}
            </Slider>
          </div>
        ) : (
          // 👉 Desktop: show 2 sliders side by side
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 overflow-hidden rounded-xl group relative">
              <Slider {...settings}>
                {this.renderBannerList(bannerList1, "banner1")}
              </Slider>
            </div>
            <div className="flex-1 overflow-hidden rounded-xl group relative">
              <Slider {...settings}>
                {this.renderBannerList(bannerList2, "banner2")}
              </Slider>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default BannerSlider;
