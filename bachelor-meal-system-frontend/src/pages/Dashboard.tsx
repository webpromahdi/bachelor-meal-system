import {
  Activity,
  AlertTriangle,
  BarChart3,
  Calendar,
  CheckCircle2,
  ChevronRight,
  Info,
  Scale,
  ShoppingCart,
  TrendingDown,
  TrendingUp,
  Users,
  Utensils,
  Wallet,
  Zap,
} from "lucide-react"
import { useEffect, useState } from "react"
import { Link } from "react-router-dom"
import { api } from "../lib/api"

interface StatItem {
  membersCount: number
  totalMeals: number
  totalBazar: number
  overallRate: number
  riceMealRate: number
  effectiveOverallRate: number
  activeDays: number
  specialMeals: number
  totalRice: number
}

const CATEGORY_META: Record<string, {
  label: string
  icon: string
  color: string
  bg: string
  border: string
}> = {
  CHICKEN: {
    label: "Chicken / Meat",
    icon: "🍗",
    color: "text-amber-400",
    bg: "bg-amber-500/10",
    border: "border-amber-500/30",
  },
  FISH: {
    label: "Fish / Seafood",
    icon: "🐟",
    color: "text-blue-400",
    bg: "bg-blue-500/10",
    border: "border-blue-500/30",
  },
  DIM: {
    label: "Egg (Dim)",
    icon: "🥚",
    color: "text-yellow-400",
    bg: "bg-yellow-500/10",
    border: "border-yellow-500/30",
  },
  OTHER: {
    label: "Veg / Other",
    icon: "🥗",
    color: "text-emerald-400",
    bg: "bg-emerald-500/10",
    border: "border-emerald-500/30",
  },
  SPECIAL: {
    label: "Special Meal",
    icon: "⭐",
    color: "text-purple-400",
    bg: "bg-purple-500/10",
    border: "border-purple-500/30",
  },
  RICE: {
    label: "Rice (Chal)",
    icon: "🌾",
    color: "text-orange-400",
    bg: "bg-orange-500/10",
    border: "border-orange-500/30",
  },
}

export default function Dashboard() {
  const [month, setMonth] = useState(() => new Date().toISOString().slice(0, 7))
  const [data, setData] = useState<any>(null)
  const [fineData, setFineData] = useState<any>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchDashboardData = async () => {
      setLoading(true)
      try {
        const [summaryRes, fineRes]: any = await Promise.all([
          api.get(`/summary/monthly?month=${month}`),
          api.get(`/fine/monthly?month=${month}`).catch(() => null),
        ])
        if (summaryRes?.success && summaryRes.data) {
          setData(summaryRes.data)
        } else {
          setData(null)
        }
        if (fineRes?.success && fineRes.data) {
          setFineData(fineRes.data)
        } else {
          setFineData(null)
        }
      } catch (error) {
        console.error("Failed to fetch dashboard data", error)
        setData(null)
        setFineData(null)
      } finally {
        setLoading(false)
      }
    }
    fetchDashboardData()
  }, [month])

  const stats: StatItem | undefined = data?.stats
  const balanceSheet: any[] = data?.balanceSheet || []
  const categoryRates = data?.categoryRates
  const dailyLog: any[] = data?.dailyLog || []

  // Filter last 5 active days from daily log
  const activeDaysList = dailyLog
    .filter((d: any) => d.active)
    .slice(-5)
    .reverse()

  const monthLabel = new Date(`${month}-01T00:00:00.000Z`).toLocaleDateString(
    "en-US",
    {
      month: "long",
      year: "numeric",
      timeZone: "UTC",
    },
  )

  return (
    <div className="p-3.5 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 sm:space-y-8">
      {/* Header with Month Selector */}
      <header className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-border pb-5 sm:pb-6">
        <div>
          <div className="flex items-center gap-2.5">
            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
              Dashboard
            </h1>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-primary/15 text-primary border border-primary/30">
              <span className="size-1.5 rounded-full bg-primary animate-pulse" />
              Live Mess Overview
            </span>
          </div>
          <p className="text-muted-foreground mt-1 text-sm">
            Real-time financial status, meal rates & settlements for{" "}
            <span className="font-semibold text-foreground">{monthLabel}</span>
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <div className="flex items-center gap-2 bg-card border border-border px-3 py-2 min-h-[44px] shadow-sm">
            <Calendar className="w-4 h-4 text-primary shrink-0" />
            <input
              type="month"
              value={month}
              onChange={(e) => e.target.value && setMonth(e.target.value)}
              className="cursor-pointer bg-transparent text-base sm:text-sm font-semibold text-foreground outline-none [color-scheme:dark]"
              aria-label="Select month"
            />
          </div>
        </div>
      </header>

      {/* 1. Top KPI Stats Strip (5 Hero Metrics) */}
      {loading ? (
        <div className="p-12 border border-border bg-card text-center text-muted-foreground animate-pulse font-medium shadow-sm">
          Loading dashboard metrics for {monthLabel}...
        </div>
      ) : stats ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
          <StatCard
            icon={Users}
            label="Total Members"
            value={stats.membersCount}
            subtext="Active in Mess"
            accentColor="text-foreground"
          />
          <StatCard
            icon={Utensils}
            label="Meals This Month"
            value={stats.totalMeals}
            subtext={
              stats.specialMeals > 0
                ? `${stats.specialMeals} Special Meals`
                : "Lunch + Dinner"
            }
            accentColor="text-blue-400"
          />
          <StatCard
            icon={ShoppingCart}
            label="Total Bazar Cost"
            value={`৳${stats.totalBazar.toLocaleString()}`}
            subtext={
              stats.totalRice > 0
                ? `Includes Rice: ৳${stats.totalRice.toLocaleString()}`
                : "Groceries & Supplies"
            }
            accentColor="text-emerald-400 font-mono"
          />
          <StatCard
            icon={TrendingUp}
            label="Effective Meal Rate"
            value={`৳${(stats.effectiveOverallRate || stats.overallRate || 0).toFixed(2)}`}
            subtext={`Regular ৳${stats.overallRate.toFixed(2)} + Rice ৳${stats.riceMealRate.toFixed(2)}`}
            accentColor="text-primary font-mono"
            highlighted
          />
          <StatCard
            icon={Activity}
            label="Active Days"
            value={`${stats.activeDays} Days`}
            subtext={`Month of ${monthLabel}`}
            accentColor="text-amber-400"
          />
        </div>
      ) : (
        <div className="p-8 border border-destructive/50 bg-destructive/10 text-destructive text-center font-medium shadow-sm">
          Failed to load stats. Please ensure the backend server is running.
        </div>
      )}

      {/* Fine Overview Section (Before Leaderboard) */}
      <section className="bg-card border border-border p-5 sm:p-6 space-y-5 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-border">
          <div>
            <div className="flex items-center gap-2">
              <Scale className="w-5 h-5 text-rose-400" />
              <h2 className="font-semibold text-base sm:text-lg text-foreground">
                Fine Overview
              </h2>
              <span className="inline-flex items-center px-2 py-0.5 text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/25">
                {fineData?.overallCount ?? 0}{" "}
                {fineData?.overallCount === 1 ? "Fine" : "Fines"} Total
              </span>
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Monthly mess penalties and fine records for {monthLabel} (e.g.
              missed meal notices, mess duty violations).
            </p>
          </div>
          <Link
            to="/fine"
            className="text-xs font-semibold text-rose-400 hover:underline flex items-center gap-1 self-start sm:self-auto"
          >
            Manage Fines <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {/* Highlight Metrics */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="flex items-center justify-between p-3 bg-background/50 border border-border/80">
            <span className="text-xs text-muted-foreground">
              Total Fines ({monthLabel})
            </span>
            <span className="font-mono font-bold text-sm text-rose-400">
              {fineData?.overallCount ?? 0}
            </span>
          </div>
          <div className="flex items-center justify-between p-3 bg-background/50 border border-border/80">
            <span className="text-xs text-muted-foreground">
              Active Fine Days
            </span>
            <span className="font-mono font-bold text-sm text-amber-400">
              {fineData?.dailyHistory?.length ?? 0} Days
            </span>
          </div>
          <div className="flex items-center justify-between p-3 bg-background/50 border border-border/80">
            <span className="text-xs text-muted-foreground">
              Members With Fines
            </span>
            <span className="font-mono font-bold text-sm text-foreground">
              {fineData?.memberSummary?.filter((m: any) => m.totalCount > 0)
                .length ?? 0}{" "}
              / {fineData?.memberSummary?.length ?? stats?.membersCount ?? 0}
            </span>
          </div>
        </div>

        {/* Member Fine Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {!fineData?.memberSummary || fineData.memberSummary.length === 0 ? (
            <div className="col-span-full p-8 text-center text-sm text-muted-foreground border border-dashed border-border">
              No fine records for this month.
            </div>
          ) : (
            fineData.memberSummary.map((m: any) => {
              const hasFines = m.totalCount > 0
              return (
                <div
                  key={m.personId}
                  className="flex items-center justify-between p-3.5 bg-background/50 border border-border hover:border-rose-500/40 transition-all shadow-xs gap-3"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <div
                      className={`size-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0 ${
                        hasFines
                          ? "bg-rose-500/15 border border-rose-500/30 text-rose-400"
                          : "bg-emerald-500/10 border border-emerald-500/20 text-emerald-400"
                      }`}
                    >
                      {m.personName.slice(0, 2).toUpperCase()}
                    </div>
                    <div className="min-w-0">
                      <div className="font-semibold text-sm text-foreground truncate">
                        {m.personName}
                      </div>
                      <div className="text-[11px] text-muted-foreground truncate">
                        {hasFines ? (
                          <span>
                            Accumulated penalties:{" "}
                            <span className="text-rose-400 font-mono font-semibold">
                              {m.totalCount}{" "}
                              {m.totalCount === 1 ? "fine" : "fines"}
                            </span>
                          </span>
                        ) : (
                          <span>No fines recorded this month</span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="shrink-0">
                    {hasFines ? (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-rose-400 bg-rose-500/10 border border-rose-500/20 font-mono">
                        <AlertTriangle className="w-3 h-3 text-rose-400" />
                        {m.totalCount} {m.totalCount === 1 ? "Fine" : "Fines"}
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20">
                        <CheckCircle2 className="w-3 h-3 text-emerald-400" />
                        Clean Record
                      </span>
                    )}
                  </div>
                </div>
              )
            })
          )}
        </div>

        <div className="pt-3 border-t border-border/60 flex flex-wrap items-center justify-between text-xs text-muted-foreground gap-2">
          <span>
            Total Fine Entries:{" "}
            <strong className="text-foreground">
              {fineData?.overallCount ?? 0}
            </strong>
          </span>
          <span>
            Dedicated independent tracking · Isolated from meal & bazar
            calculations
          </span>
        </div>
      </section>

      {/* 2. Member Balance Leaderboard (Full Width Section) */}
      <section className="bg-card border border-border p-5 sm:p-6 space-y-5 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-border">
          <div>
            <div className="flex items-center gap-2">
              <Wallet className="w-5 h-5 text-primary" />
              <h2 className="font-semibold text-base sm:text-lg text-foreground">
                Member Balance Leaderboard
              </h2>
            </div>
            <p className="text-xs text-muted-foreground mt-1">
              Live financial standing showing who owes money for meals and who
              has balance to receive from grocery shopping.
            </p>
          </div>
          <Link
            to="/summary"
            className="text-xs font-semibold text-primary hover:underline flex items-center gap-1 self-start sm:self-auto"
          >
            Full Audit Statement <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {balanceSheet.length === 0 ? (
            <div className="col-span-full p-8 text-center text-sm text-muted-foreground border border-dashed border-border">
              No member entries recorded yet.
            </div>
          ) : (
            balanceSheet.map((m: any) => {
              const bal = m.balance || 0
              const isReceive = bal > 0.01
              const isPay = bal < -0.01
              const isSettled = !isReceive && !isPay

              return (
                <div
                  key={m.id}
                  className="flex items-center justify-between p-3.5 bg-background/50 border border-border hover:border-primary/40 transition-all shadow-xs gap-3"
                >
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="size-9 rounded-full bg-primary/10 border border-primary/25 flex items-center justify-center font-bold text-xs text-primary shrink-0">
                      {m.name.slice(0, 2).toUpperCase()}
                    </div>
                    <div className="min-w-0">
                      <div className="font-semibold text-sm text-foreground truncate">
                        {m.name}
                      </div>
                      <div className="text-[11px] text-muted-foreground truncate">
                        Meals:{" "}
                        <span className="text-foreground font-medium">
                          {m.totalMeals}
                        </span>
                        {" · "}
                        Bazar Paid:{" "}
                        <span className="text-foreground font-mono font-medium">
                          ৳{m.totalPaid.toFixed(2)}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div className="shrink-0">
                    {isReceive && (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 font-mono">
                        <TrendingUp className="w-3 h-3" />
                        +৳{bal.toFixed(2)} (Gets back)
                      </span>
                    )}
                    {isPay && (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-amber-400 bg-amber-500/10 border border-amber-500/20 font-mono">
                        <TrendingDown className="w-3 h-3" />
                        -৳{Math.abs(bal).toFixed(2)} (Owes)
                      </span>
                    )}
                    {isSettled && (
                      <span className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-muted-foreground bg-muted/40 border border-border">
                        <CheckCircle2 className="w-3 h-3 text-emerald-500" />
                        Settled
                      </span>
                    )}
                  </div>
                </div>
              )
            })
          )}
        </div>

        <div className="pt-3 border-t border-border/60 flex flex-wrap items-center justify-between text-xs text-muted-foreground gap-2">
          <span>
            Total Active Members:{" "}
            <strong className="text-foreground">{balanceSheet.length}</strong>
          </span>
          <span>
            Settlements calculated in real-time based on individual meal rates
          </span>
        </div>
      </section>

      {/* 3. Food Category Spending & Rate Breakdown (High-Value Section) */}
      <section className="bg-card border border-border p-5 sm:p-6 space-y-4 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-border">
          <div>
            <h2 className="font-semibold text-base sm:text-lg text-foreground flex items-center gap-2">
              <Utensils className="w-5 h-5 text-primary" />
              Category Food Budget & Meal Rates
            </h2>
            <p className="text-xs text-muted-foreground mt-1">
              Transparent cost breakdown for each meal type and average
              consumption rate.
            </p>
          </div>
          <Link
            to="/bazar"
            className="text-xs font-semibold text-primary hover:underline flex items-center gap-1 self-start sm:self-auto"
          >
            Record Grocery <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          {categoryRates?.rates &&
            Object.entries(categoryRates.rates).map(([key, rateVal]) => {
              const meta = CATEGORY_META[key] || {
                label: key,
                icon: "🍲",
                color: "text-foreground",
                bg: "bg-card",
                border: "border-border",
              }
              const totalSpent = categoryRates.totals?.[key] || 0
              const count = categoryRates.counts?.[key] || 0
              const rateNum = Number(rateVal) || 0

              return (
                <div
                  key={key}
                  className={`p-3.5 bg-background/60 border ${meta.border} flex flex-col justify-between gap-2 transition-all hover:scale-[1.02]`}
                >
                  <div className="flex items-center justify-between">
                    <span className="text-lg">{meta.icon}</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
                      {count} {key === "RICE" ? "Eaters" : "Meals"}
                    </span>
                  </div>

                  <div>
                    <div className="text-xs font-semibold text-foreground truncate">
                      {meta.label}
                    </div>
                    <div
                      className={`text-lg sm:text-xl font-bold font-mono ${meta.color} mt-0.5`}
                    >
                      ৳{rateNum.toFixed(2)}
                      <span className="text-[10px] font-normal text-muted-foreground ml-0.5">
                        /meal
                      </span>
                    </div>
                  </div>

                  <div className="pt-2 border-t border-border/50 text-[11px] text-muted-foreground flex items-center justify-between font-mono">
                    <span>Spent:</span>
                    <span className="font-semibold text-foreground">
                      ৳{totalSpent.toLocaleString()}
                    </span>
                  </div>
                </div>
              )
            })}
        </div>
      </section>

      {/* 4. Recent Daily Activity Log Snapshot (High-Value Section) */}
      <section className="bg-card border border-border p-5 sm:p-6 space-y-4 shadow-sm">
        <div className="flex items-center justify-between pb-3 border-b border-border">
          <div className="flex items-center gap-2">
            <Calendar className="w-5 h-5 text-primary" />
            <h2 className="font-semibold text-base sm:text-lg text-foreground">
              Recent Active Days in {monthLabel}
            </h2>
          </div>
          <Link
            to="/summary"
            className="text-xs font-semibold text-primary hover:underline flex items-center gap-1"
          >
            Full 31-Day Log <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        {activeDaysList.length === 0 ? (
          <div className="p-8 text-center border border-dashed border-border space-y-3 bg-background/30">
            <p className="text-sm text-muted-foreground">
              No daily meals or bazar groceries have been logged yet for{" "}
              {monthLabel}.
            </p>
            <div className="flex justify-center gap-3">
              <Link
                to="/meals"
                className="px-3 py-1.5 bg-primary text-primary-foreground text-xs font-semibold hover:bg-primary/90 transition-colors"
              >
                + Log Today's Meals
              </Link>
              <Link
                to="/bazar"
                className="px-3 py-1.5 bg-card border border-border text-foreground text-xs font-semibold hover:bg-accent transition-colors"
              >
                + Add Bazar Item
              </Link>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {activeDaysList.map((d: any) => (
              <div
                key={d.date}
                className="p-3.5 bg-background/60 border border-border hover:border-primary/50 transition-colors space-y-2"
              >
                <div className="flex items-center justify-between text-xs">
                  <span className="font-bold text-foreground">
                    {d.dayName}, {d.dayNum} {monthLabel.slice(0, 3)}
                  </span>
                  <span className="font-mono text-[11px] font-semibold text-primary">
                    {d.total} meals
                  </span>
                </div>

                <div className="text-[11px] text-muted-foreground space-y-1">
                  <div className="flex justify-between">
                    <span>Lunch / Dinner:</span>
                    <span className="font-medium text-foreground">
                      {d.lunch} / {d.dinner}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span>Bazar Expense:</span>
                    <span className="font-mono font-medium text-emerald-400">
                      ৳{d.dailyBazar.toLocaleString()}
                    </span>
                  </div>
                </div>

                {d.types && d.types.length > 0 && (
                  <div className="flex flex-wrap gap-1 pt-1.5 border-t border-border/50">
                    {d.types.map((t: string) => (
                      <span
                        key={t}
                        className="text-[10px] px-1.5 py-0.5 bg-card border border-border text-muted-foreground font-medium"
                      >
                        {CATEGORY_META[t]?.icon || ""} {t}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </section>

      {/* 5. Quick Actions Hub */}
      <section className="space-y-3">
        <h2 className="text-base sm:text-lg font-semibold tracking-tight text-foreground flex items-center gap-2">
          ⚡ Quick Actions
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <ActionCard
            to="/meals"
            icon={Utensils}
            iconColor="text-amber-400 bg-amber-500/10 border-amber-500/20"
            title="Log Today's Meals"
            description="Record lunch and dinner member counts with session meal types."
          />
          <ActionCard
            to="/bazar"
            icon={ShoppingCart}
            iconColor="text-emerald-400 bg-emerald-500/10 border-emerald-500/20"
            title="Add Bazar Items"
            description="Record grocery expenses by category (Chicken, Fish, Rice, etc.)."
          />
          <ActionCard
            to="/utility"
            icon={Zap}
            iconColor="text-blue-400 bg-blue-500/10 border-blue-500/20"
            title="Utilities & Fund"
            description="Manage shared current/gas meters, wifi bills, and collected funds."
          />
          <ActionCard
            to="/summary"
            icon={BarChart3}
            iconColor="text-purple-400 bg-purple-500/10 border-purple-500/20"
            title="Monthly Statement"
            description="Inspect daily log, cost matrix, balance sheet, and debt settlements."
          />
        </div>
      </section>

      {/* 6. How It Works Section */}
      <section className="bg-card border border-border p-6 space-y-4 shadow-sm">
        <h2 className="font-semibold text-lg flex items-center gap-2 text-foreground">
          <Info className="w-5 h-5 text-primary" />
          How It Works
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-muted-foreground">
          <div className="border-l-2 border-primary/50 pl-4 py-1">
            <strong className="text-foreground block mb-1">
              1. Daily Meal Entry
            </strong>
            Record each meal with global type and member meal counts.
          </div>
          <div className="border-l-2 border-primary/50 pl-4 py-1">
            <strong className="text-foreground block mb-1">
              2. Bazar Tracking
            </strong>
            Add grocery expenses by category and track who paid.
          </div>
          <div className="border-l-2 border-primary/50 pl-4 py-1">
            <strong className="text-foreground block mb-1">
              3. Category Calculation
            </strong>
            System calculates fair rates per category and shares Rice among
            regular meals.
          </div>
          <div className="border-l-2 border-primary/50 pl-4 py-1">
            <strong className="text-foreground block mb-1">
              4. Balance Settlement
            </strong>
            Instant balance sheet clearly shows who owes and who receives.
          </div>
        </div>
      </section>
    </div>
  )
}

function StatCard({
  icon: Icon,
  label,
  value,
  subtext,
  accentColor = "text-foreground",
  highlighted = false,
}: {
  icon: any
  label: string
  value: string | number
  subtext?: string
  accentColor?: string
  highlighted?: boolean
}) {
  return (
    <div
      className={`bg-card border p-4 sm:p-5 flex flex-col justify-between gap-3 transition-all hover:border-primary/50 shadow-sm ${
        highlighted
          ? "border-primary/60 bg-primary/5 ring-1 ring-primary/30"
          : "border-border"
      }`}
    >
      <div className="flex items-center gap-2 text-muted-foreground">
        <Icon className="w-4 h-4 text-primary shrink-0" />
        <span className="text-[11px] font-semibold uppercase tracking-wider">
          {label}
        </span>
      </div>
      <div>
        <div
          className={`text-2xl sm:text-3xl font-bold tracking-tight ${accentColor}`}
        >
          {value}
        </div>
        {subtext && (
          <div className="text-[11px] text-muted-foreground mt-1 truncate">
            {subtext}
          </div>
        )}
      </div>
    </div>
  )
}

function ActionCard({
  to,
  icon: Icon,
  iconColor,
  title,
  description,
}: {
  to: string
  icon: any
  iconColor: string
  title: string
  description: string
}) {
  return (
    <Link
      to={to}
      className="group block bg-card border border-border p-5 transition-all hover:border-primary/50 hover:bg-accent/30 shadow-sm"
    >
      <div className="flex items-start gap-3.5">
        <div
          className={`p-2.5 border shrink-0 group-hover:scale-105 transition-transform ${iconColor}`}
        >
          <Icon className="w-5 h-5" />
        </div>
        <div className="space-y-1">
          <h3 className="font-semibold text-foreground text-sm group-hover:text-primary transition-colors flex items-center gap-1">
            {title} <ChevronRight className="w-3 h-3 opacity-60" />
          </h3>
          <p className="text-xs text-muted-foreground leading-relaxed">
            {description}
          </p>
        </div>
      </div>
    </Link>
  )
}
