import { ReactNode } from "react"
import { Navigate, useLocation, Link } from "react-router-dom"
import { Lock, LogIn, ArrowLeft } from "lucide-react"
import { useAuth } from "../context/AuthContext"

interface ProtectedRouteProps {
  children: ReactNode
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { isAuthenticated, isLoading } = useAuth()
  const location = useLocation()

  if (isLoading) {
    return (
      <div className="flex h-full min-h-[60vh] items-center justify-center">
        <div className="flex flex-col items-center gap-3">
          <div className="w-8 h-8 border-2 border-primary border-t-transparent animate-spin rounded-full" />
          <span className="text-sm font-medium text-muted-foreground font-mono">
            Verifying session...
          </span>
        </div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return (
      <div className="p-4 sm:p-8 max-w-lg mx-auto min-h-[75vh] flex items-center justify-center">
        <div className="w-full border border-border bg-card p-6 sm:p-8 shadow-xl text-center space-y-5">
          <div className="w-14 h-14 bg-primary/10 border border-primary/20 flex items-center justify-center mx-auto text-primary">
            <Lock className="w-7 h-7" />
          </div>

          <div className="space-y-2">
            <h2 className="text-xl sm:text-2xl font-bold tracking-tight text-foreground">
              Authentication Required
            </h2>
            <p className="text-sm text-muted-foreground leading-relaxed">
              Everyone can view the public Dashboard, but you must be signed in with valid credentials to view and manage this section.
            </p>
          </div>

          <div className="pt-2 flex flex-col sm:flex-row gap-3 justify-center">
            <Link
              to="/login"
              state={{ from: location }}
              className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-primary-foreground font-semibold text-sm hover:bg-primary/90 transition-colors shadow-sm min-h-[44px]"
            >
              <LogIn className="w-4 h-4" />
              <span>Sign In to Access</span>
            </Link>

            <Link
              to="/"
              className="inline-flex items-center justify-center gap-2 px-5 py-2.5 border border-border bg-card/60 text-muted-foreground hover:text-foreground hover:bg-accent font-semibold text-sm transition-colors min-h-[44px]"
            >
              <ArrowLeft className="w-4 h-4" />
              <span>Return to Dashboard</span>
            </Link>
          </div>
        </div>
      </div>
    )
  }

  return <>{children}</>
}
