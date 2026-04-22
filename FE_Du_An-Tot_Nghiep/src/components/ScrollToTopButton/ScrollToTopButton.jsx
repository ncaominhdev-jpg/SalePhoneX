import React, { useEffect, useState } from "react";
import { ChevronsUp } from "lucide-react";
import { useLocation } from "react-router-dom";

const ScrollToTopButton = () => {
  const [showButton, setShowButton] = useState(false);
  const location = useLocation();

  // Ẩn nút ở các trang không cần
  const hiddenPaths = ["/cart", "/checkout"];
  const isHidden = hiddenPaths.includes(location.pathname);

  useEffect(() => {
    const handleScroll = () => {
      setShowButton(window.scrollY > 300);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  if (isHidden || !showButton) return null;

  return (
    <button
      onClick={scrollToTop}
      className="fixed z-50 flex items-center justify-center gap-1 
      text-xs font-semibold text-white bg-neutral-900 rounded-lg shadow-md transition-all 
      hover:bg-neutral-800
      bottom-20 right-4 px-3 py-3
      sm:bottom-16 sm:right-6 sm:px-4 sm:py-3 sm:rounded-xl sm:text-sm
      md:bottom-16 md:right-6 md:px-5 md:py-3 md:gap-2 md:rounded-xl md:text-sm"
      >
      <span className="hidden sm:inline">Lên đầu</span>
      <ChevronsUp size={16} className="sm:size-[18px] md:size-[20px]" />
    </button>

  );
};

export default ScrollToTopButton;
