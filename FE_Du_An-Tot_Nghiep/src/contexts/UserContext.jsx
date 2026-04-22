import React, { createContext, useEffect, useState } from "react";

export const UserContext = createContext();

export const UserProvider = ({ children }) => {
    const [user, setUser] = useState(null);

    useEffect(() => {
        const storedUser = localStorage.getItem("user");
        if (storedUser) {
            setUser(JSON.parse(storedUser));
        }
    }, []);

    const updateUser = (newUser) => {
        if (newUser) {
            setUser(newUser);
            localStorage.setItem("user", JSON.stringify(newUser));
        } else {
            setUser(null);
            localStorage.removeItem("user");
        }
    };

    return (
        <UserContext.Provider value={{ user, updateUser }}>
            {children}
        </UserContext.Provider>
    );
};
