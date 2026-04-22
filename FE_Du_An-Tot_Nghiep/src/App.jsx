import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient();

// Layout & Components
import ClientLayout from "./layouts/ClientLayout/ClientLayout";
import ScrollToTopButton from "./components/ScrollToTopButton/ScrollToTopButton";
import PrivateRoute from "./routes/PrivateRoute";

// Context Providers
import { CategoryProvider } from "./contexts/CategoryContext";
import { UserProvider } from "./contexts/UserContext";
import { ProductShopProvider } from "./contexts/ProductShopContext";
import { ShippingAddressProvider } from "./contexts/ShippingAddressContext";
import { BrandShopProvider } from "./contexts/BrandShopContext";
import { ProductVariantProviderCart } from "./contexts/ProductVariantContextCart";
import { CartProvider } from "./contexts/CartContext";
import { CityProvider } from "./contexts/CityContext";
import ProductProvider from "./contexts/ProductHomeContext";
import BrandProvider from "./contexts/BrandHomeContext";
import { CommentProvider } from "./contexts/CommentContext.jsx";
import { WishlistProvider } from "./contexts/WishlistContext";
import { VoucherProvider } from "./contexts/VoucherContext";
import { OrderProvider } from "./contexts/oderContext.jsx";
import { ReviewProvider } from "./contexts/ReviewContext.jsx";
import { FlashDealsProvider } from "./contexts/FlashDealsContext.jsx";

// Slick Carousel CSS
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";

// Client Pages
import Home from "./pages/Client/Home/Home";
import Shop from "./pages/Client/Shop/Shop";
import Login from "./pages/Client/Login/Login";
import Register from "./pages/Client/Register/Register";
import Cart from "./pages/Client/Cart/Cart";
import Checkout from "./pages/Client/Checkout/Checkout";
import UserProfile from "./pages/Client/UserProfile/UserProfile";
import ForgotPassword from "./pages/Client/ForgotPassword/ForgotPassword";
import ResetPassword from "./pages/Client/ResetPassword/ResetPassword";
import Product_Detail from "./pages/Client/Product_Detail/Product_Detail";
import SearchPage from "./pages/Client/SearchPage/SearchPage";
import ThankYouPage from "./components/ThankYouPage/ThankYouPage.jsx";
import NotFoundPage from "./components/NotFoundPage/NotFoundPage.jsx";
import Forbidden from "./pages/Client/Error/Forbidden.jsx";
import OrderDetail from "./components/OrderDetail/OrderDetail";

function App() {
  return (
    <>
      <QueryClientProvider client={queryClient}>
        <CityProvider>
          <FlashDealsProvider>
            <CategoryProvider>
              <ProductProvider>
                <BrandProvider>
                  <CommentProvider>
                    <ProductVariantProviderCart>
                      <ProductShopProvider>
                        <BrandShopProvider>
                          <CartProvider>
                            <UserProvider>
                              <OrderProvider>
                                <ReviewProvider>
                                  <ShippingAddressProvider>
                                    <WishlistProvider>
                                      <VoucherProvider>
                                        <Router>
                                          <Routes>
                                            {/* Auth Routes */}
                                            <Route
                                              path="/login"
                                              element={<Login />}
                                            />
                                            <Route
                                              path="/register"
                                              element={<Register />}
                                            />
                                            <Route
                                              path="/forgot-password"
                                              element={<ForgotPassword />}
                                            />
                                            <Route
                                              path="/reset-password"
                                              element={<ResetPassword />}
                                            />

                                            <Route element={<ClientLayout />}>
                                              {/* Public routes */}
                                              <Route
                                                path="/"
                                                element={
                                                  <PrivateRoute publicRoute>
                                                    <Home />
                                                  </PrivateRoute>
                                                }
                                              />
                                              <Route
                                                path="/:slug"
                                                element={
                                                  <PrivateRoute publicRoute>
                                                    <Shop />
                                                  </PrivateRoute>
                                                }
                                              />
                                              <Route
                                                path="/:categorySlug/:productSlug"
                                                element={
                                                  <PrivateRoute publicRoute>
                                                    <Product_Detail />
                                                  </PrivateRoute>
                                                }
                                              />
                                              <Route
                                                path="/search"
                                                element={
                                                  <PrivateRoute publicRoute>
                                                    <SearchPage />
                                                  </PrivateRoute>
                                                }
                                              />

                                              {/* Private routes - chỉ user */}
                                              <Route
                                                path="/cart"
                                                element={
                                                  <PrivateRoute
                                                    allowedRoles={["user"]}
                                                  >
                                                    <Cart />
                                                  </PrivateRoute>
                                                }
                                              />
                                              <Route
                                                path="/checkout"
                                                element={
                                                  <PrivateRoute
                                                    allowedRoles={["user"]}
                                                  >
                                                    <Checkout />
                                                  </PrivateRoute>
                                                }
                                              />
                                              <Route
                                                path="/profile"
                                                element={
                                                  <PrivateRoute
                                                    allowedRoles={["user"]}
                                                  >
                                                    <UserProfile />
                                                  </PrivateRoute>
                                                }
                                              />

                                              <Route
                                                path="/order-detail/:orderId"
                                                element={
                                                  <PrivateRoute
                                                    allowedRoles={["user"]}
                                                  >
                                                    <OrderDetail />
                                                  </PrivateRoute>
                                                }
                                              />

                                              <Route
                                                path="/cam-on-quy-khach"
                                                element={
                                                  <PrivateRoute
                                                    allowedRoles={["user"]}
                                                  >
                                                    <ThankYouPage />
                                                  </PrivateRoute>
                                                }
                                              />
                                            </Route>

                                            {/* Forbidden */}
                                            <Route
                                              path="/403"
                                              element={<Forbidden />}
                                            />

                                            {/* 404 Not Found */}
                                            <Route
                                              path="/404"
                                              element={<NotFoundPage />}
                                            />

                                            <Route path="*" element={<NotFoundPage />} />

                                          </Routes>
                                          <ScrollToTopButton />
                                        </Router>
                                      </VoucherProvider>
                                    </WishlistProvider>
                                  </ShippingAddressProvider>
                                </ReviewProvider>
                              </OrderProvider>
                            </UserProvider>
                          </CartProvider>
                        </BrandShopProvider>
                      </ProductShopProvider>
                    </ProductVariantProviderCart>
                  </CommentProvider>
                </BrandProvider>
              </ProductProvider>
            </CategoryProvider>
          </FlashDealsProvider>
        </CityProvider>
      </QueryClientProvider>

      <ToastContainer position="top-right" autoClose={3000} />
    </>
  );
}

export default App;
