import { useState, useEffect } from "react"
import { Routes, Route, Link, useLocation } from "react-router-dom"
import {
  Home,
  Utensils,
  ShoppingCart,
  BarChart3,
  Lightbulb,
  Calculator,
  Menu,
  X,
  Scale,
} from "lucide-react"
import { cn } from "./lib/utils"
import Dashboard from "./pages/Dashboard"
import Meals from "./pages/Meals"
import Bazar from "./pages/Bazar"
import Fine from "./pages/Fine"
import Summary from "./pages/Summary"
import Utility from "./pages/Utility"

const links = [
  { to: "/", label: "Dashboard", icon: Home },
  { to: "/meals", label: "Meals", icon: Utensils },
  { to: "/bazar", label: "Bazar", icon: ShoppingCart },
  { to: "/fine", label: "Fine", icon: Scale },
  { to: "/summary", label: "Summary", icon: BarChart3 },
  { to: "/utility", label: "Utility", icon: Lightbulb },
]

function AppSidebar({
  onNavigate,
  onClose,
}: {
  onNavigate?: () => void
  onClose?: () => void
}) {
  const location = useLocation()

  return (
    <div className="w-[min(85vw,280px)] lg:w-60 bg-card border-r border-border h-full flex flex-col flex-shrink-0 shadow-xl lg:shadow-none">
      <div className="p-4 border-b border-border flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Calculator className="w-6 h-6 text-primary shrink-0" />
          <span className="font-bold text-lg tracking-tight">Meal System</span>
        </div>
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
      <nav className="flex-1 py-4 flex flex-col gap-1 px-2 overflow-y-auto">
        {links.map((link) => {
          const isActive = location.pathname === link.to
          const Icon = link.icon
          return (
            <Link
              key={link.to}
              to={link.to}
              onClick={onNavigate}
              className={cn(
                "flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm font-medium transition-colors border-l-2",
                isActive
                  ? "bg-accent text-accent-foreground border-primary font-semibold"
                  : "border-transparent text-muted-foreground hover:bg-accent/50 hover:text-foreground",
              )}
            >
              <Icon className="w-4 h-4 shrink-0" />
              <span>{link.label}</span>
            </Link>
          )
        })}
      </nav>
      <div className="p-4 border-t border-border text-xs text-muted-foreground text-center">
        v2.0 · webpromahdi
      </div>
    </div>
  )
}

function App() {
  const [mobileOpen, setMobileOpen] = useState(false)

  // Lock background body scroll when mobile drawer is open
  useEffect(() => {
    if (mobileOpen) {
      document.body.style.overflow = "hidden"
    } else {
      document.body.style.overflow = ""
    }
    return () => {
      document.body.style.overflow = ""
    }
  }, [mobileOpen])

  // Close drawer on Escape key press
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && mobileOpen) {
        setMobileOpen(false)
      }
    }
    window.addEventListener("keydown", handleKeyDown)
    return () => window.removeEventListener("keydown", handleKeyDown)
  }, [mobileOpen])

  return (
    <div className="flex h-screen overflow-hidden bg-background text-foreground flex-col lg:flex-row">
      {/* Mobile & Tablet Top Header (< 1024px) */}
      <header className="lg:hidden flex items-center justify-between px-4 py-2.5 border-b border-border bg-card shrink-0 z-30 min-h-[56px]">
        <div className="flex items-center gap-2">
          <Calculator className="w-5 h-5 text-primary shrink-0" />
          <span className="font-bold text-base tracking-tight">
            Meal System
          </span>
        </div>
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
          <Route path="/" element={<Dashboard />} />
          <Route path="/meals" element={<Meals />} />
          <Route path="/bazar" element={<Bazar />} />
          <Route path="/fine" element={<Fine />} />
          <Route path="/fines" element={<Fine />} />
          <Route path="/summary" element={<Summary />} />
          <Route path="/utility" element={<Utility />} />
        </Routes>
      </main>
    </div>
  )
}

export default App
