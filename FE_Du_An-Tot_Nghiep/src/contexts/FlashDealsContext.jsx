import { createContext, useContext, useEffect, useMemo, useState } from "react";
import axios from "axios";
import constants from "../constants/constants";

/** Public API của context */
const FlashDealsContext = createContext({
    sessions: [],
    loading: false,
    error: "",
    refresh: () => { },
});

/** Utils: ngày VN (yyyy-mm-dd) */
const todayVN = () =>
    new Date().toLocaleString("en-CA", { timeZone: "Asia/Ho_Chi_Minh" }).slice(0, 10);

/** Provider: fetch 1 lần, fallback logic: /active -> /flash-deals[ngày] */
export function FlashDealsProvider({ children }) {
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setErr] = useState("");

    const loadFromActive = async () => {
        const r = await axios.get(`${constants.BASE_URL}/flash-deals/active`);
        // Ghép current + upcoming (lọc null/undefined)
        return [r.data?.current, ...(r.data?.upcoming || [])].filter(Boolean);
    };

    const loadFromAll = async () => {
        const r = await axios.get(`${constants.BASE_URL}/flash-deals`);
        const days = Object.keys(r.data?.data || {});
        if (!days.length) return [];
        const key = days.includes(todayVN()) ? todayVN() : days[0];
        return r.data.data[key] || [];
    };

    const fetchDeals = async () => {
        try {
            setLoading(true);
            setErr("");
            let list = [];
            try {
                list = await loadFromActive();
            } catch {
                // ignore, sẽ fallback
            }
            if (!list.length) list = await loadFromAll();
            setSessions(Array.isArray(list) ? list : []);
        } catch (e) {
            setErr(e?.response?.data?.message || "Không thể tải Flash Deals");
            setSessions([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        let mounted = true;
        (async () => {
            await fetchDeals();
            // Clean-up
            return () => {
                mounted = false;
            };
        })();
    }, []);

    const value = useMemo(
        () => ({
            sessions,
            loading,
            error,
            refresh: fetchDeals,
        }),
        [sessions, loading, error]
    );

    return (
        <FlashDealsContext.Provider value={value}>
            {children}
        </FlashDealsContext.Provider>
    );
}

export function useFlashDeals() {
    return useContext(FlashDealsContext);
}
