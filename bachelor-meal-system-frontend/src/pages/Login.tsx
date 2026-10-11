import {
	AlertCircle,
	ArrowLeft,
	Calculator,
	Eye,
	EyeOff,
	Lock,
	LogIn,
	Mail,
	ShieldCheck,
} from "lucide-react";
import { type FormEvent, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function Login() {
	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [showPassword, setShowPassword] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const [submitting, setSubmitting] = useState(false);

	const { login, isAuthenticated } = useAuth();
	const navigate = useNavigate();
	const location = useLocation();

	// Determine redirect target
	const from = (location.state as any)?.from?.pathname || "/";

	// If already logged in, redirect immediately
	if (isAuthenticated) {
		navigate(from, { replace: true });
		return null;
	}

	const handleSubmit = async (e: FormEvent) => {
		e.preventDefault();
		setError(null);

		if (!email.trim()) {
			setError("Please enter your email address.");
			return;
		}

		if (!password) {
			setError("Please enter your password.");
			return;
		}

		setSubmitting(true);

		try {
			const result = await login(email, password);
			if (result.success) {
				navigate(from, { replace: true });
			} else {
				setError(result.error || "Invalid email or password.");
			}
		} catch (err: any) {
			setError(err?.message || "Failed to sign in. Please try again.");
		} finally {
			setSubmitting(false);
		}
	};

	return (
		<div className="min-h-[85vh] flex items-center justify-center p-4 sm:p-6 lg:p-8">
			<div className="w-full max-w-md space-y-6">
				{/* Header / Brand */}
				<div className="text-center space-y-2">
					<div className="inline-flex items-center justify-center w-12 h-12 bg-primary/10 border border-primary/20 text-primary mb-2">
						<Calculator className="w-6 h-6" />
					</div>
					<h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
						Sign In
					</h1>
					<p className="text-xs sm:text-sm text-muted-foreground">
						Sign in to access and update all sections of the Meal System
					</p>
				</div>

				{/* Login Card */}
				<div className="border border-border bg-card p-6 sm:p-8 shadow-xl space-y-6">
					{error && (
						<div className="p-3.5 bg-destructive/10 border border-destructive/30 text-destructive text-sm flex items-start gap-2.5">
							<AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
							<span>{error}</span>
						</div>
					)}

					<form onSubmit={handleSubmit} className="space-y-4">
						{/* Email Field */}
						<div className="space-y-1.5">
							<label
								htmlFor="email"
								className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground"
							>
								Email Address
							</label>
							<div className="relative">
								<div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
									<Mail className="w-4 h-4" />
								</div>
								<input
									id="email"
									type="email"
									autoComplete="email"
									required
									value={email}
									onChange={(e) => setEmail(e.target.value)}
									placeholder="admin@mealsystem.local"
									className="w-full pl-9 pr-3 py-2.5 border border-border bg-background/50 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary transition-colors min-h-[44px]"
								/>
							</div>
						</div>

						{/* Password Field */}
						<div className="space-y-1.5">
							<label
								htmlFor="password"
								className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground"
							>
								Password
							</label>
							<div className="relative">
								<div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
									<Lock className="w-4 h-4" />
								</div>
								<input
									id="password"
									type={showPassword ? "text" : "password"}
									autoComplete="current-password"
									required
									value={password}
									onChange={(e) => setPassword(e.target.value)}
									placeholder="••••••••••••"
									className="w-full pl-9 pr-10 py-2.5 border border-border bg-background/50 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary transition-colors min-h-[44px]"
								/>
								<button
									type="button"
									onClick={() => setShowPassword(!showPassword)}
									className="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground transition-colors"
									aria-label={showPassword ? "Hide password" : "Show password"}
								>
									{showPassword ? (
										<EyeOff className="w-4 h-4" />
									) : (
										<Eye className="w-4 h-4" />
									)}
								</button>
							</div>
						</div>

						{/* Submit Button */}
						<button
							type="submit"
							disabled={submitting}
							className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-primary-foreground font-semibold text-sm hover:bg-primary/90 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed min-h-[44px] cursor-pointer"
						>
							{submitting ? (
								<>
									<div className="w-4 h-4 border-2 border-primary-foreground border-t-transparent animate-spin rounded-full" />
									<span>Verifying credentials...</span>
								</>
							) : (
								<>
									<LogIn className="w-4 h-4" />
									<span>Sign In</span>
								</>
							)}
						</button>
					</form>

					{/* Seed Notice */}
					<div className="border-t border-border/60 pt-4 flex items-center gap-2 text-xs text-muted-foreground">
						<ShieldCheck className="w-4 h-4 text-primary shrink-0" />
						<span>
							Pre-configured database credentials only. No registration
							required.
						</span>
					</div>
				</div>

				{/* Back Link */}
				<div className="text-center">
					<Link
						to="/"
						className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors font-medium"
					>
						<ArrowLeft className="w-3.5 h-3.5" />
						<span>Back to Public Dashboard</span>
					</Link>
				</div>
			</div>
		</div>
	);
}
