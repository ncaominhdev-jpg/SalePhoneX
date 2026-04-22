export function toSlug(str) {
    if (!str || typeof str !== "string") return ""; // nếu null/undefined thì trả về chuỗi rỗng
    return str
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "") // loại bỏ dấu tiếng Việt
        .replace(/đ/g, "d")              // chuyển đ → d
        .replace(/[^a-z0-9\s-]/g, "")    // loại ký tự đặc biệt
        .trim()
        .replace(/\s+/g, "-");           // khoảng trắng → dấu -
}
