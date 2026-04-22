import React from "react";

const SummaryItem = ({ item }) => {
    return (
        <div
            key={item.id}
            className="flex items-center justify-between gap-3 p-2 rounded-lg border bg-gray-50"
        >
            <div className="flex items-center gap-3 min-w-0 flex-1">
                <img
                    src={item.image}
                    alt={item.name}
                    className="w-10 h-10 rounded-md"
                />
                <div className="min-w-0">
                    <p className="text-xs sm:text-sm font-medium text-gray-800 truncate">
                        {item.productName}
                    </p>
                    <p className="text-xs text-gray-600">{item.name}</p>
                    <p className="text-xs text-gray-500">Số lượng: {item.quantity}</p>
                </div>
            </div>
            <div className="text-sm sm:text-base font-semibold text-red-600 whitespace-nowrap">
                {(item.price * item.quantity).toLocaleString()}
            </div>
        </div>
    );
};

export default SummaryItem;
