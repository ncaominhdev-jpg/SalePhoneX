import HomeSection from "./HomeSection/HomeSection";
import FeaturedPhone from "./FeaturedPhone/FeaturedPhone";

import ElectronicComponents from "./ElectronicComponents/ElectronicComponents";

import PaymentOffers from "./PaymentOffers/PaymentOffers";

import FlashDealLaptops from "./FlashDealLaptops/FlashDealLaptops";
const Home = () => {
  return (
    <div className="max-w-7xl container mx-auto">
      <HomeSection />
      <FlashDealLaptops />
      <FeaturedPhone/>   
      <ElectronicComponents />
      <PaymentOffers />
    </div>
  );
};

export default Home;
