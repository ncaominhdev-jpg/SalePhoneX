import PC from "../../../../assets/ElectroniccComponents/group_786.png";
import CPU from "../../../../assets/ElectroniccComponents/cpu-intel-core-i7-14700k_2_.png";
import Main from "../../../../assets/ElectroniccComponents/mainboard-msi-pro-b760m-e-ddr4_2_.png";
import Ram from "../../../../assets/ElectroniccComponents/ram_297_20_.png";
import O_cung from "../../../../assets/ElectroniccComponents/o-cung-hdd-wd-plus-10tb-3-5-inch-sata-iii-256mb-cache-7200rpm-wd101efbx_1_.png";
import Cart_man_hinh from "../../../../assets/ElectroniccComponents/vga-gigabyte-geforce-rtx-5070-ti-eagle-oc-16gb_1_.png";
import Nguon_may_tinh from "../../../../assets/ElectroniccComponents/Nguon_mat_tinh_40_1_53.png";
import Tan_nhiet from "../../../../assets/ElectroniccComponents/tan-nhiet-nuoc-gigabyte-aorus-waterforce-280_1_.png";
import Cas_may_tinh from "../../../../assets/ElectroniccComponents/case-may-tinh-nzxt-h5-elite-atx.png";

const ElectronicComponents = () => {
  const categories = [
    { name: "PC ráp sẵn", image: PC, bgColor: "bg-red-300" },
    { name: "CPU", image: CPU, bgColor: "bg-pink-300" },
    { name: "Mainboard", image: Main, bgColor: "bg-pink-400" },
    { name: "RAM", image: Ram, bgColor: "bg-purple-300" },
    { name: "Ổ cứng", image: O_cung, bgColor: "bg-blue-300" },
    { name: "Card màn hình", image: Cart_man_hinh, bgColor: "bg-blue-400" },
    { name: "Nguồn máy tính", image: Nguon_may_tinh, bgColor: "bg-green-300" },
    { name: "Tản nhiệt", image: Tan_nhiet, bgColor: "bg-yellow-300" },
    { name: "Case máy tính", image: Cas_may_tinh, bgColor: "bg-orange-300" },
  ];

  return (
    <div className="px-4 py-6">
      {/* Tiêu đề */}
      <div className="flex justify-between items-center mb-4 flex-wrap gap-4">
        <h1 className="text-2xl sm:text-2xl font-bold text-gray-800 whitespace-nowrap">
          LINH KIỆN MÁY TÍNH
        </h1>
        <button className="px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-700 hover:bg-red-100 transition whitespace-nowrap">
          Xem tất cả
        </button>
      </div>

      {/* Danh sách danh mục */}
      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-9 gap-4">
        {categories.map((cat, index) => (
          <div
            key={index}
            className={`${cat.bgColor} rounded-lg p-3 flex flex-col items-center text-white shadow-md transition-transform hover:scale-105 cursor-pointer`}
          >
            <img
              src={cat.image}
              alt={cat.name}
              className="w-16 h-16 object-contain mb-2"
            />
            <h3 className="text-center text-sm font-semibold">{cat.name}</h3>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ElectronicComponents;
