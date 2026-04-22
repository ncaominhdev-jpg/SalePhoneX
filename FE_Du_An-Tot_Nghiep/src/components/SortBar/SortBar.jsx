import { FaSortAmountDown, FaSortAmountUp } from "react-icons/fa";

const SortBar = ({ sortType, setSortType, displayName }) => {
    const sorts = [
        { label: "Giá ↑", value: "price-asc", icon: <FaSortAmountUp /> },
        { label: "Giá ↓", value: "price-desc", icon: <FaSortAmountDown /> },
    ];

    const renderSortButtons = () => {
        return sorts.map((item) => {
            const isActive = sortType === item.value;
            const baseClass =
                "flex-shrink-0 px-3 py-2 text-sm whitespace-nowrap transition-colors duration-200";

            const mobileClass = isActive
                ? "border-b-2 border-red-500 text-red-600 font-semibold"
                : "border-b-2 border-transparent text-gray-600 hover:text-red-500";

            const desktopClass = isActive
                ? "sm:bg-blue-50 sm:text-blue-600 sm:border sm:border-blue-500 sm:font-semibold"
                : "sm:bg-gray-100 sm:text-gray-800 sm:border sm:border-transparent sm:hover:bg-gray-200";

            return (
                <button
                    key={item.value}
                    onClick={() =>
                        setSortType(sortType === item.value ? "default" : item.value)
                    }
                    className={`flex items-center ${baseClass} ${mobileClass} ${desktopClass} sm:rounded-full`}
                >
                    <span className="hidden sm:inline mr-1">{item.icon}</span>
                    {item.label}
                </button>
            );
        });
    };

    return (
        <>
            {/* Tiêu đề danh mục */}
            <h1
                className="relative text-center 
                text-base sm:text-2xl md:text-3xl 
                font-extrabold text-red-600 uppercase 
                tracking-widest mt-2 mb-2 sm:mt-4 sm:mb-6 
                leading-snug
                after:content-[''] after:block after:w-12 after:h-1 
                after:bg-red-500 after:mx-auto after:mt-2 sm:after:mt-3"
            >
                {displayName}
            </h1>

            <div className="grid grid-cols-3 items-center">
                <div className="col-span-3 flex justify-end gap-2 border-b sm:border-0">
                    {renderSortButtons()}
                </div>
            </div>

        </>

    );
};

export default SortBar;
