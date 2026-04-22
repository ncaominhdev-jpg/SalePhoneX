import React, { useState } from "react";

const SpecRow = ({ label, value }) => (
  <tr className="border-t">
    <td className="p-3 font-medium bg-gray-50 w-1/3">{label}</td>
    <td className="p-3">{value}</td>
  </tr>
);

const ProductExtra = ({ productAttributes }) => {
  const [showSpecs, setShowSpecs] = useState(false);
  const isMobile = window.innerWidth < 768;

  return (
    <div className="flex flex-wrap gap-6">
      <div className="w-full md:w-1/2 space-y-6">
        {productAttributes.length > 0 && (
          <div className="mt-6">
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-lg font-semibold">Thông số kỹ thuật</h2>
              {isMobile && (
                <button
                  onClick={() => setShowSpecs((prev) => !prev)}
                  className="text-gray-600 text-sm font-medium"
                >
                  {showSpecs ? "Thu gọn" : "Xem thêm"}
                </button>
              )}
            </div>

            <div className="overflow-x-auto rounded-lg shadow-sm border transition-all duration-500 ease-in-out">
              <table className="w-full text-sm text-left border-collapse">
                <tbody>
                  {(showSpecs || !isMobile
                    ? productAttributes
                    : productAttributes.slice(0, 3)
                  ) // ✅ chỉ hiện 3 dòng đầu khi thu gọn
                    .map((attr) => (
                      <SpecRow
                        key={attr.attribute_id}
                        label={attr.attribute_name}
                        value={attr.value}
                      />
                    ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Cam kết */}
        <div className="rounded-xl border border-gray-200 bg-gray-50 p-5 shadow-sm space-y-4">
          <h3 className="text-base font-semibold text-gray-800">
            SalePhoneX cam kết
          </h3>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
            {[
              {
                icon: "📦",
                text: "Sản phẩm mới (Cần thanh toán trước khi mở hộp).",
              },
              {
                icon: "📦",
                text: "Bộ sản phẩm gồm: Hộp, Sách hướng dẫn, Cáp, Cây lấy sim.",
              },
              {
                icon: "🔁",
                text: (
                  <>
                    Hư gì đổi nấy <strong>12 tháng</strong> tại 2956 siêu thị
                    toàn quốc (miễn phí tháng đầu).{" "}
                    <a href="#" className="text-blue-600 hover:underline">
                      Xem chi tiết
                    </a>
                  </>
                ),
              },
              {
                icon: "🛡️",
                text: (
                  <>
                    Bảo hành <strong>chính hãng điện thoại 1 năm</strong> tại
                    các trung tâm bảo hành hãng.{" "}
                    <a href="#" className="text-blue-600 hover:underline">
                      Xem địa chỉ bảo hành
                    </a>
                  </>
                ),
              },
            ].map((item, idx) => (
              <div key={idx} className="flex items-start gap-3">
                <div className="text-blue-500 text-lg pt-1">{item.icon}</div>
                <p className="leading-snug">{item.text}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductExtra;
