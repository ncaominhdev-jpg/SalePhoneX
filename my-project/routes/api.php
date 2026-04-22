<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
// Đã xóa dòng import CategoryController thừa để tránh lỗi trùng lặp
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ShippingAddressController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ConsultRequestController;
use App\Http\Controllers\ReviewController;

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeCategoryController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Middleware\TokenAuthMiddleware;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\InventoryTransactionController;

use App\Http\Controllers\FlashDealController;

Route::get('/flash-deals', [FlashDealController::class, 'index']);
Route::get('/flash-deals/active', [FlashDealController::class, 'active']);
//user
Route::apiResource('users', UserController::class);
//biến thể sản phẩm
Route::get('/product-variants', [ProductVariantController::class, 'index']);
Route::post('/product-variants', [ProductVariantController::class, 'store']);
Route::get('/product-variants/{id}', [ProductVariantController::class, 'show']);
Route::put('/product-variants/{id}', [ProductVariantController::class, 'update']);
Route::delete('/product-variants/{id}', [ProductVariantController::class, 'destroy']);
//sản phảm
Route::get('/products', [ProductController::class, 'index']);

// API lọc sản phẩm theo giá trị thuộc tính trên đường dẫn
Route::get('/products/filter-by-attribute-value/{value}', [ProductController::class, 'filterByAttributeValue']);

// API lọc sản phẩm theo tên thuộc tính trên đường dẫn
Route::get('/products/filter-by-attribute-name/{name}', [ProductController::class, 'filterByAttributeName']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::get('/products/category/{category_id}', [ProductController::class, 'getByCategory']);
Route::get('/products/brand/{brand_id}', [ProductController::class, 'getByBrand']);
Route::get('/products/filter/{brand_id}/{category_id}', [ProductController::class, 'filterByBrandAndCategory']);
Route::post('/products/filter-attributes', [ProductController::class, 'filterByAttributes']);

//category
Route::get('/categories/filter-five', [CategoryController::class, 'filterFiveCategories']);
Route::apiResource('categories', CategoryController::class);
Route::get('categories/{id}/logo', [CategoryController::class, 'getLogo']);
//cart
Route::apiResource('carts', CartController::class);
Route::get('carts/user/{userId}', [CartController::class, 'getByUserId']);
//order
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders/{id}/send-invoice', [OrderController::class, 'sendInvoice']);
Route::apiResource('orders', OrderController::class)->middleware('order.status.validation');
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders/{id}/send-invoice', [OrderController::class, 'sendInvoice']);
Route::get('/orders/{id}/pdf', [OrderController::class, 'generatePdf'])->name('api.orders.pdf');
Route::get('/orders/{id}/pdf/view', [OrderController::class, 'viewPdf'])->name('api.orders.pdf.view');

//order detail
Route::apiResource('order-details', OrderDetailController::class);
//inventory
Route::apiResource('inventories', InventoryController::class);
//brand
Route::apiResource('brands', BrandController::class);
Route::get('/brands/category/{category_id}', [BrandController::class, 'getBrandsByCategory']);
Route::get('brands/{id}/logo', [BrandController::class, 'getLogo']);
//branch
Route::apiResource('branches', BranchController::class);
//payment
Route::apiResource('payments', PaymentController::class);
Route::middleware(TokenAuthMiddleware::class)->group(function () {
    Route::post('/payments/vnpay/create', [PaymentController::class, 'createVnpayPayment']);
});
Route::get('/payments/vnpay/return', [PaymentController::class, 'vnpayReturn']);
Route::post('/momo-payment', [PaymentController::class, 'momopayment']);
// Route::post('/momo-ipn', function (Request $request) {
//     \Illuminate\Support\Facades\Log::info('MoMo IPN Callback:', $request->all());
//     return response()->json(['message' => 'IPN received']);
// });
Route::get('/verify-momo-payment/{orderId}', [PaymentController::class, 'verifyMomoPayment']);
Route::post('/momo-ipn', [PaymentController::class, 'momoIpn']);
Route::post('/payments/cod', [PaymentController::class, 'codPayment']); //COD
Route::get('/momo-return', [PaymentController::class, 'momoReturn']);



// Auth API
// Đăng ký, đăng nhập và đăng xuất
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Quên mật khẩu - gửi token lấy lại mật khẩu
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);

// Lấy lại mật khẩu - reset mật khẩu bằng token
Route::post('/password/reset', [AuthController::class, 'resetPassword']);

// Đổi mật khẩu khi đã đăng nhập
Route::post('/password/change', [AuthController::class, 'changePassword']);
Route::get('/comments', [CommentController::class, 'index']);
// API quản lý sổ địa chỉ
Route::middleware(TokenAuthMiddleware::class)->group(function () {
    Route::get('/shipping-addresses', [ShippingAddressController::class, 'index']);
    Route::post('/shipping-addresses', [ShippingAddressController::class, 'store']);
    Route::put('/shipping-addresses/{id}', [ShippingAddressController::class, 'update']);
    Route::delete('/shipping-addresses/{id}', [ShippingAddressController::class, 'destroy']);
    Route::post('/shipping-addresses/{id}/set-default', [ShippingAddressController::class, 'setDefault']);
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);
    // API comments

    Route::post('/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
});

Route::get('/inventories/available/{warehouse_id}', [InventoryController::class, 'getAvailableProductsByWarehouse']);

// API cho attributes, attribute_category, attribute_values
Route::get('/attributes', [AttributeController::class, 'index']);
Route::get('/attribute-categories', [AttributeCategoryController::class, 'index']);
Route::get('/attribute-values', [AttributeValueController::class, 'index']);

// API lấy tất cả thuộc tính của sản phẩm theo product_id
Route::get('/products/{product_id}/attributes', [ProductController::class, 'getAttributesByProductId']);

Route::get('/available-products/city/{city}', [ProductController::class, 'getAvailableProductsByCity']);
// API upload media cho sản phẩm
Route::post('/media/upload/{productId}', [MediaController::class, 'upload']);
Route::get('/media/{productId}', [MediaController::class, 'index']);
Route::delete('/media/{id}', [MediaController::class, 'destroy']);
Route::put('/media/thumbnail/{id}', [MediaController::class, 'setThumbnail']);

Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::get('/media/all', [MediaController::class, 'getAllMedia']);
Route::put('/media/thumbnail/{id}', [MediaController::class, 'updateThumbnail']);
Route::put('/media/thumbnail/{id}', [MediaController::class, 'updateThumbnail']);


Route::get('/wishlists', [WishlistController::class, 'index']);
Route::post('/wishlists', [WishlistController::class, 'store']);
Route::delete('/wishlists/{productId}', [WishlistController::class, 'destroy']);
// API lấy danh sách voucher còn hiệu lực
Route::get('/vouchers', [VoucherController::class, 'index']);
Route::post('/vouchers', [VoucherController::class, 'store']); // dành cho admin
Route::post('/vouchers/apply', [VoucherController::class, 'apply']);

// bỏ middleware auth:api, sẽ check token trong Controller
Route::get('/vouchers/available', [VoucherController::class, 'getAvailableVouchers']);

Route::post('/vouchers/claim', [VoucherController::class, 'claimVoucher']);
Route::get('/vouchers/my', [VoucherController::class, 'myVouchers']);
Route::post('/user/{user}/vouchers/{voucher}', [VoucherController::class, 'assignToUser']);
Route::get('/user/{id}/vouchers', [VoucherController::class, 'userVouchers']);

// API cho ConsultRequest
Route::apiResource('consult-requests', ConsultRequestController::class);
// đánh giá sản phẩm
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);


// ... existing code ...

Route::get('/inventory-transactions/{id}/pdf', [InventoryTransactionController::class, 'generatePdf'])->name('api.inventory-transactions.pdf');
Route::get('/inventory-transactions/{id}/pdf/view', [InventoryTransactionController::class, 'viewPdf'])->name('api.inventory-transactions.pdf.view');
// ... existing code ...
