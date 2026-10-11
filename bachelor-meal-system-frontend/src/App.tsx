import {
	BarChart3,
	Calculator,
	Home,
	Lightbulb,
	Lock,
	LogIn,
	LogOut,
	Menu,
	Scale,
	ShoppingCart,
	User as UserIcon,
	Utensils,
	X,
} from "lucide-react";
import { useEffect, useState } from "react";
import { Link, Route, Routes, useLocation } from "react-router-dom";
import { ProtectedRoute } from "./components/ProtectedRoute";
import { useAuth } from "./context/AuthContext";
import { cn } from "./lib/utils";
import Bazar from "./pages/Bazar";
import Dashboard from "./pages/Dashboard";
import Fine from "./pages/Fine";
import Login from "./pages/Login";
import Meals from "./pages/Meals";
import Summary from "./pages/Summary";
import Utility from "./pages/Utility";

const links = [
	{ to: "/", label: "Dashboard", icon: Home, isPublic: true },
	{ to: "/meals", label: "Meals", icon: Utensils, isPublic: false },
	{ to: "/bazar", label: "Bazar", icon: ShoppingCart, isPublic: false },
	{ to: "/fine", label: "Fine", icon: Scale, isPublic: false },
	{ to: "/summary", label: "Summary", icon: BarChart3, isPublic: false },
	{ to: "/utility", label: "Utility", icon: Lightbulb, isPublic: false },
];

function AppSidebar({
	onNavigate,
	onClose,
}: {
	onNavigate?: () => void;
	onClose?: () => void;
}) {
	const location = useLocation();
	const { user, isAuthenticated, logout } = useAuth();

	return (
		<div className="w-[min(85vw,280px)] lg:w-60 bg-card border-r border-border h-full flex flex-col flex-shrink-0 shadow-xl lg:shadow-none">
			{/* Sidebar Header */}
			<div className="p-4 border-b border-border flex items-center justify-between">
				<Link
					to="/"
					onClick={onNavigate}
					className="flex items-center gap-2 hover:opacity-90 transition-opacity"
				>
					<Calculator className="w-6 h-6 text-primary shrink-0" />
					<span className="font-bold text-lg tracking-tight">Meal System</span>
				</Link>
				{onClose && (
					<button
						type="button"
						onClick={onClose}
						className="lg:hidden min-w-[44px] min-h-[44px] -mr-2 flex items-center justify-center text-muted-foreground hover:text-foreground transition-colors"
						aria-label="Close navigation menu"
					>
						<X className="w-5 h-5" />
					</button>
				)}
			</div>

			{/* Navigation Links */}
			<nav className="flex-1 py-4 flex flex-col gap-1 px-2 overflow-y-auto">
				{links.map((link) => {
					const isActive = location.pathname === link.to;
					const Icon = link.icon;
					const showLock = !isAuthenticated && !link.isPublic;

					return (
						<Link
							key={link.to}
							to={link.to}
							onClick={onNavigate}
							className={cn(
								"flex items-center justify-between px-3 py-2.5 min-h-[44px] text-sm font-medium transition-colors border-l-2",
								isActive
									? "bg-accent text-accent-foreground border-primary font-semibold"
									: "border-transparent text-muted-foreground hover:bg-accent/50 hover:text-foreground",
							)}
						>
							<div className="flex items-center gap-3">
								<Icon className="w-4 h-4 shrink-0" />
								<span>{link.label}</span>
							</div>
							{showLock && (
								<Lock className="w-3.5 h-3.5 text-muted-foreground/60" />
							)}
						</Link>
					);
				})}
			</nav>

			{/* User Session Bar */}
			<div className="p-3 border-t border-border bg-card/50 space-y-2">
				{isAuthenticated && user ? (
					<div className="space-y-2">
						<div className="flex items-center gap-2 px-1">
							<div className="w-7 h-7 bg-primary/10 border border-primary/20 flex items-center justify-center text-primary text-xs font-bold shrink-0">
								<UserIcon className="w-3.5 h-3.5" />
							</div>
							<div className="overflow-hidden flex-1">
								<p className="text-xs font-semibold text-foreground truncate">
									{user.name}
								</p>
								<p className="text-[10px] text-muted-foreground font-mono truncate">
									{user.email}
								</p>
							</div>
						</div>

						<button
							type="button"
							onClick={() => {
								logout();
								if (onNavigate) onNavigate();
							}}
							className="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold border border-border text-muted-foreground hover:text-destructive hover:border-destructive/40 hover:bg-destructive/10 transition-colors min-h-[36px] cursor-pointer"
						>
							<LogOut className="w-3.5 h-3.5" />
							<span>Sign Out</span>
						</button>
					</div>
				) : (
					<Link
						to="/login"
						onClick={onNavigate}
						className="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold bg-primary text-primary-foreground hover:bg-primary/90 transition-colors min-h-[38px] shadow-sm"
					>
						<LogIn className="w-3.5 h-3.5" />
						<span>Sign In</span>
					</Link>
				)}

				<div className="pt-1 text-[11px] text-muted-foreground/80 text-center">
					v2.0 · webpromahdi
				</div>
			</div>
		</div>
	);
}

function App() {
	const [mobileOpen, setMobileOpen] = useState(false);
	const { isAuthenticated, logout } = useAuth();

	// Lock background body scroll when mobile drawer is open
	useEffect(() => {
		if (mobileOpen) {
			document.body.style.overflow = "hidden";
		} else {
			document.body.style.overflow = "";
		}
		return () => {
			document.body.style.overflow = "";
		};
	}, [mobileOpen]);

	// Close drawer on Escape key press
	useEffect(() => {
		const handleKeyDown = (e: KeyboardEvent) => {
			if (e.key === "Escape" && mobileOpen) {
				setMobileOpen(false);
			}
		};
		window.addEventListener("keydown", handleKeyDown);
		return () => window.removeEventListener("keydown", handleKeyDown);
	}, [mobileOpen]);

	return (
		<div className="flex h-screen overflow-hidden bg-background text-foreground flex-col lg:flex-row">
			{/* Mobile & Tablet Top Header (< 1024px) */}
			<header className="lg:hidden flex items-center justify-between px-4 py-2.5 border-b border-border bg-card shrink-0 z-30 min-h-[56px]">
				<Link to="/" className="flex items-center gap-2">
					<Calculator className="w-5 h-5 text-primary shrink-0" />
					<span className="font-bold text-base tracking-tight">
						Meal System
					</span>
				</Link>
				<div className="flex items-center gap-2">
					{!isAuthenticated ? (
						<Link
							to="/login"
							className="px-2.5 py-1 text-xs font-semibold bg-primary text-primary-foreground flex items-center gap-1"
						>
							<LogIn className="w-3 h-3" />
							<span>Login</span>
						</Link>
					) : (
						<button
							type="button"
							onClick={() => logout()}
							className="p-1.5 border border-border text-muted-foreground hover:text-destructive"
							title="Sign Out"
						>
							<LogOut className="w-3.5 h-3.5" />
						</button>
					)}

					<button
						type="button"
						onClick={() => setMobileOpen(!mobileOpen)}
						className="min-w-[44px] min-h-[44px] flex items-center justify-center border border-border text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
						aria-label="Toggle navigation"
						aria-expanded={mobileOpen}
					>
						{mobileOpen ? (
							<X className="w-5 h-5" />
						) : (
							<Menu className="w-5 h-5" />
						)}
					</button>
				</div>
			</header>

			{/* Mobile Drawer Backdrop */}
			{mobileOpen && (
				<div
					className="fixed inset-0 bg-black/70 backdrop-blur-xs z-40 lg:hidden transition-opacity"
					onClick={() => setMobileOpen(false)}
					aria-hidden="true"
				/>
			)}

			{/* Mobile & Tablet Drawer Menu */}
			<div
				className={cn(
					"fixed top-0 bottom-0 left-0 z-50 transition-transform duration-200 ease-out lg:hidden",
					mobileOpen ? "translate-x-0" : "-translate-x-full",
				)}
			>
				<AppSidebar
					onNavigate={() => setMobileOpen(false)}
					onClose={() => setMobileOpen(false)}
				/>
			</div>

			{/* Desktop Fixed Persistent Sidebar (>= 1024px) */}
			<div className="hidden lg:flex h-screen shrink-0">
				<AppSidebar />
			</div>

			{/* Main Content Area */}
			<main className="flex-1 overflow-x-hidden overflow-y-auto">
				<Routes>
					{/* Public Dashboard Route */}
					<Route path="/" element={<Dashboard />} />

					{/* Login Route */}
					<Route path="/login" element={<Login />} />

					{/* Protected Section Routes */}
					<Route
						path="/meals"
						element={
							<ProtectedRoute>
								<Meals />
							</ProtectedRoute>
						}
					/>
					<Route
						path="/bazar"
						element={
							<ProtectedRoute>
								<Bazar />
							</ProtectedRoute>
						}
					/>
					<Route
						path="/fine"
						element={
							<ProtectedRoute>
								<Fine />
							</ProtectedRoute>
						}
					/>
					<Route
						path="/fines"
						element={
							<ProtectedRoute>
								<Fine />
							</ProtectedRoute>
						}
					/>
					<Route
						path="/summary"
						element={
							<ProtectedRoute>
								<Summary />
							</ProtectedRoute>
						}
					/>
					<Route
						path="/utility"
						element={
							<ProtectedRoute>
								<Utility />
							</ProtectedRoute>
						}
					/>
				</Routes>
			</main>
		</div>
	);
}

export default App;
