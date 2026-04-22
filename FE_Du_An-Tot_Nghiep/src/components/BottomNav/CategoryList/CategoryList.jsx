import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FaBox } from 'react-icons/fa';
import { useCategories } from '../../../hooks/useCategories';
import { toSlug } from '../../../utils/slug';

function CategoryList({ onSelect }) {
  const { data, isLoading } = useCategories();
  const categories = data?.categories || [];
  const location = useLocation();

  if (isLoading) {
    return (
      <ul className="space-y-1 text-gray-500 text-sm text-center py-4">
        <li>Đang tải danh mục...</li>
      </ul>
    );
  }

  function renderCategoryItem(cat, idx) {
    const path = '/' + toSlug(cat.name);
    const isActive = location.pathname === path;

    return (
      <li key={cat.id || idx}>
        <Link
          to={path}
          onClick={onSelect}
          className={
            'flex items-center gap-3 px-4 py-2 rounded-lg transition ' +
            (isActive
              ? 'bg-red-100 text-red-600 font-semibold'
              : 'text-gray-700 hover:bg-gray-100 hover:text-red-500')
          }
        >
          {/* Icon */}
          <span className="w-7 h-7 md:w-9 md:h-9 flex items-center justify-center">
            {cat.image ? (
              <img
                src={cat.image}
                alt={cat.name}
                loading="lazy"
                className="w-6 h-6 md:w-8 md:h-8 object-contain rounded"
              />
            ) : (
              <FaBox className="text-base md:text-xl text-gray-500" />
            )}
          </span>

          {/* Tên danh mục */}
          <span className="text-sm md:text-lg truncate">{cat.name}</span>
        </Link>
      </li>
    );
  }

  return (
    <ul className="grid grid-cols-2 gap-2 md:gap-3">
      {categories.map((cat, idx) => renderCategoryItem(cat, idx))}
    </ul>
  );

}

export default CategoryList;
