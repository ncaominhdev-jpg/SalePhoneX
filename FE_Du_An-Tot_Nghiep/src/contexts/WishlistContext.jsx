import { createContext, useContext } from "react";
import { useWishlist } from "../hooks/useWishlist";

const WishlistContext = createContext();

export const WishlistProvider = ({ children }) => {
    const wishlistData = useWishlist();
    return (
        <WishlistContext.Provider value={wishlistData}>
            {children}
        </WishlistContext.Provider>
    );
};

export const useWishlistContext = () => useContext(WishlistContext);
