import React from 'react';
import { toSlug } from '../../utils/slug';
import { useCategories } from '../../hooks/useCategories';
import { Link } from 'react-router-dom';

function CategoryMenu({ sidebarHeight, onCloseMenu }) {
  const { data, isLoading } = useCategories();
  const categories = data?.categories || [];

  if (isLoading) {
    return (
      <aside
        className="bg-white rounded-xl shadow-lg p-4 border border-gray-200 animate-pulse"
        style={{ minHeight: sidebarHeight || "40vh", maxHeight: sidebarHeight || "46vh" }}
      >
        <div className="space-y-3">
          {Array.from({ length: 6 }).map((_, idx) => (
            <div
              key={idx}
              className="h-4 bg-gray-200 rounded-md w-3/4 mx-auto"
            ></div>
          ))}
        </div>
      </aside>
    );
  }

  return (
    <aside
      className="bg-white rounded-xl shadow-lg overflow-y-auto custom-scrollbar border border-gray-200"
      style={{
        minHeight: sidebarHeight ? sidebarHeight : "40vh",
        maxHeight: sidebarHeight ? sidebarHeight : "46vh",
      }}
    >
      {categories.length > 0 ? (
        categories.map((item) => (
          <Link
            key={item.id}
            onClick={onCloseMenu}
            to={`/${toSlug(item.name)}`}
            className="flex items-center gap-3 px-8 py-3 relative group cursor-pointer
                       transition-all duration-200 hover:bg-red-100"
          >
            {/* Thanh viền trái xuất hiện khi hover */}
            <span className="absolute left-0 top-0 h-full w-1 bg-transparent 
                             group-hover:bg-red-500 transition-all duration-200"></span>

            {/* Tên danh mục */}
            <span
              className="truncate font-medium text-sm sm:text-base text-gray-700 
             group-hover:text-red-600 group-hover:scale-105  duration-300 ease-out inline-block"
            >
              {item.name}
            </span>

          </Link>
        ))
      ) : (
        <p className="text-center text-gray-400 italic py-6">
          Hiện chưa có danh mục nào
        </p>
      )}
    </aside>
  );
}

export default CategoryMenu;
