import {
	createContext,
	type ReactNode,
	useContext,
	useEffect,
	useState,
} from "react";
import { api } from "../lib/api";

export interface AuthUser {
	id: number;
	name: string;
	email: string;
	role: string;
}

interface AuthContextType {
	user: AuthUser | null;
	token: string | null;
	isAuthenticated: boolean;
	isLoading: boolean;
	login: (
		email: string,
		password: string,
	) => Promise<{ success: boolean; error?: string }>;
	logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
	const [user, setUser] = useState<AuthUser | null>(() => {
		const savedUser = localStorage.getItem("auth_user");
		if (savedUser) {
			try {
				return JSON.parse(savedUser);
			} catch {
				return null;
			}
		}
		return null;
	});
	const [token, setToken] = useState<string | null>(() =>
		localStorage.getItem("auth_token"),
	);
	const [isLoading, setIsLoading] = useState<boolean>(true);

	// Verify session on mount if token exists
	useEffect(() => {
		let mounted = true;

		const verifySession = async () => {
			const storedToken = localStorage.getItem("auth_token");
			if (!storedToken) {
				if (mounted) {
					setUser(null);
					setToken(null);
					setIsLoading(false);
				}
				return;
			}

			try {
				const res: any = await api.get("/auth/me");
				if (mounted && res?.success && res.data) {
					setUser(res.data);
					localStorage.setItem("auth_user", JSON.stringify(res.data));
				} else {
					throw new Error("Invalid session");
				}
			} catch {
				if (mounted) {
					setUser(null);
					setToken(null);
					localStorage.removeItem("auth_token");
					localStorage.removeItem("auth_user");
				}
			} finally {
				if (mounted) {
					setIsLoading(false);
				}
			}
		};

		verifySession();

		// Listen for unauthorized events from api interceptor
		const handleUnauthorized = () => {
			if (mounted) {
				setUser(null);
				setToken(null);
			}
		};
		window.addEventListener("auth:unauthorized", handleUnauthorized);

		return () => {
			mounted = false;
			window.removeEventListener("auth:unauthorized", handleUnauthorized);
		};
	}, []);

	const login = async (email: string, password: string) => {
		try {
			const res: any = await api.post("/auth/login", {
				email: email.trim(),
				password,
			});

			if (res?.success && res.data) {
				const receivedToken = res.data.accessToken;
				const receivedUser = res.data.user;

				setToken(receivedToken);
				setUser(receivedUser);

				localStorage.setItem("auth_token", receivedToken);
				localStorage.setItem("auth_user", JSON.stringify(receivedUser));

				return { success: true };
			}

			return {
				success: false,
				error: res?.message || "Failed to log in",
			};
		} catch (err: any) {
			const errorMessage =
				err?.response?.data?.message ||
				err?.response?.data?.error ||
				err?.message ||
				"An unexpected error occurred during login";
			return {
				success: false,
				error: errorMessage,
			};
		}
	};

	const logout = async () => {
		try {
			await api.post("/auth/logout").catch(() => null);
		} finally {
			setUser(null);
			setToken(null);
			localStorage.removeItem("auth_token");
			localStorage.removeItem("auth_user");
		}
	};

	return (
		<AuthContext.Provider
			value={{
				user,
				token,
				isAuthenticated: !!user && !!token,
				isLoading,
				login,
				logout,
			}}
		>
			{children}
		</AuthContext.Provider>
	);
}

export function useAuth() {
	const context = useContext(AuthContext);
	if (!context) {
		throw new Error("useAuth must be used within an AuthProvider");
	}
	return context;
}
