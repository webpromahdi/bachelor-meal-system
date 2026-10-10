import { useState, useEffect, useCallback } from "react"
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Scale,
  Users,
  Clock,
  History,
  Tag,
} from "lucide-react"
import { api } from "../lib/api"

export interface Person {
  id: number
  name: string
}

type FinesMap = Record<number, number>

const keyOf = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`

const PRESET_REASONS = [
  "Late Notice",
  "Missed Meal Off",
  "Duty Violation",
  "Late Bazar",
  "Mess Rule",
]

export default function Fine() {
  const [date, setDate] = useState(() => new Date())
  const [persons, setPersons] = useState<Person[]>([])
  const [fines, setFines] = useState<FinesMap>({})
  const [reason, setReason] = useState<string>("")
  const [isEditing, setIsEditing] = useState(false)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [feedback, setFeedback] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  // Monthly overview state
  const [monthlyLoading, setMonthlyLoading] = useState(false)
  const [monthlyData, setMonthlyData] = useState<{
    month: string
    overallCount: number
    memberSummary: Array<{
      personId: number
      personName: string
      totalCount: number
    }>
    dailyHistory: Array<{
      date: string
      totalCount: number
      entries: Array<{
        personId: number
        name: string
        fineCount: number
        reason: string
      }>
    }>
  } | null>(null)

  const key = keyOf(date)
  const currentMonth = key.slice(0, 7)

  // Helper to fill fines for all members
  const fill = (countList: Person[], v: number): FinesMap =>
    Object.fromEntries(countList.map((p) => [p.id, v]))

  // 1. Fetch persons on mount
  useEffect(() => {
    const fetchPersons = async () => {
      try {
        const res: any = await api.get("/persons")
        if (res.success && Array.isArray(res.data)) {
          setPersons(res.data)
        }
      } catch (err) {
        console.error("Failed to fetch persons:", err)
      }
    }
    fetchPersons()
  }, [])

  // 2. Fetch monthly fines
  const fetchMonthly = useCallback(async (targetMonth: string) => {
    setMonthlyLoading(true)
    try {
      const res: any = await api.get(`/fine/monthly?month=${targetMonth}`)
      if (res.success && res.data) {
        setMonthlyData(res.data)
      }
    } catch (err) {
      console.error("Failed to fetch monthly fines:", err)
    } finally {
      setMonthlyLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchMonthly(currentMonth)
  }, [currentMonth, fetchMonthly])

  // 3. Fetch fines when date or persons change
  useEffect(() => {
    if (persons.length === 0) return

    const fetchFinesForDate = async () => {
      setLoading(true)
      setFeedback(null)
      try {
        const res: any = await api.get(`/fine?date=${key}`)
        if (res.success && res.data) {
          const { hasSavedData, fineCounts, fines: fineList } = res.data

          if (hasSavedData) {
            setIsEditing(true)
            const fMap: FinesMap = {}
            for (const p of persons) {
              fMap[p.id] = fineCounts[p.id] || 0
            }
            setFines(fMap)

            // Extract reason from first entry if available
            if (fineList && fineList.length > 0 && fineList[0].reason) {
              setReason(fineList[0].reason)
            } else {
              setReason("")
            }
          } else {
            setIsEditing(false)
            setFines(fill(persons, 0))
            setReason("")
          }
        }
      } catch (err) {
        console.error("Failed to fetch fines for date:", err)
      } finally {
        setLoading(false)
      }
    }

    fetchFinesForDate()
  }, [key, persons])

  const shift = (n: number) => {
    const next = new Date(
      date.getFullYear(),
      date.getMonth(),
      date.getDate() + n,
    )
    setDate(next)
  }

  const handleDateChange = (dateString: string) => {
    if (!dateString) return
    const parts = dateString.split("-").map(Number)
    if (
      parts.length === 3 &&
      !isNaN(parts[0]) &&
      !isNaN(parts[1]) &&
      !isNaN(parts[2])
    ) {
      setDate(new Date(parts[0], parts[1] - 1, parts[2]))
    }
  }

  const clamp = (v: string) =>
    Math.max(0, Math.min(20, Math.floor(Number(v) || 0)))

  const sum = (c: FinesMap) => persons.reduce((a, p) => a + (c[p.id] || 0), 0)

  const save = async () => {
    setSaving(true)
    setFeedback(null)

    try {
      const payload = {
        date: key,
        fineCounts: fines,
        reason: reason.trim(),
      }

      const res: any = await api.post("/fine/save-day", payload)
      if (res.success) {
        setFeedback({
          type: "success",
          message: res.message || "Fines saved successfully!",
        })
        setIsEditing(sum(fines) > 0)
        // Refresh monthly view
        fetchMonthly(currentMonth)
      } else {
        setFeedback({
          type: "error",
          message: res.error || "Failed to save fines.",
        })
      }
    } catch (err: any) {
      console.error("Save fines error:", err)
      const errMsg =
        err.response?.data?.error ||
        err.message ||
        "An error occurred while saving."
      setFeedback({ type: "error", message: errMsg })
    } finally {
      setSaving(false)
    }
  }

  const long = date.toLocaleDateString("en-US", {
    weekday: "long",
    month: "long",
    day: "numeric",
    year: "numeric",
  })

  const short = date.toLocaleDateString("en-US", {
    month: "long",
    day: "numeric",
    year: "numeric",
  })

  const quick = [
    {
      label: "Everyone = 1 Fine",
      run: () => setFines(fill(persons, 1)),
      cls: "",
    },
    {
      label: "+1 to All",
      run: () => {
        const next: FinesMap = {}
        for (const p of persons) {
          next[p.id] = (fines[p.id] || 0) + 1
        }
        setFines(next)
      },
      cls: "",
    },
    {
      label: "❌ Clear All",
      run: () => setFines(fill(persons, 0)),
      cls: "!border-destructive/40 !text-destructive hover:!bg-destructive/10",
    },
  ]

  return (
    <div className="p-3.5 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-5 sm:space-y-6">
      {/* Top Date Navigation Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-border bg-card px-3.5 py-3 sm:px-4 sm:py-3.5 shadow-sm">
        <div className="flex flex-wrap items-center gap-2 sm:gap-3">
          <button
            type="button"
            onClick={() => shift(-1)}
            className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1.5"
            aria-label="Previous day"
          >
            <ChevronLeft className="w-4 h-4 text-zinc-300" />
            <span>Prev</span>
          </button>

          <div className="flex items-center gap-2 border border-border bg-background/60 px-3 py-2 min-h-[44px] transition-colors focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
            <Calendar className="w-4 h-4 text-primary shrink-0" />
            <input
              type="date"
              value={key}
              onChange={(e) => handleDateChange(e.target.value)}
              className="cursor-pointer bg-transparent text-base sm:text-sm font-semibold text-foreground outline-none [color-scheme:dark]"
              aria-label="Select date"
            />
            <span className="hidden md:inline-block border-l border-border pl-2.5 text-xs text-zinc-400 font-medium whitespace-nowrap">
              {long}
            </span>
          </div>

          <button
            type="button"
            onClick={() => shift(1)}
            className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1.5"
            aria-label="Next day"
          >
            <span>Next</span>
            <ChevronRight className="w-4 h-4 text-zinc-300" />
          </button>

          <button
            type="button"
            onClick={() => setDate(new Date())}
            className="cursor-pointer border border-border bg-card/50 px-3 py-2 min-h-[44px] text-xs font-medium text-zinc-400 hover:text-foreground hover:bg-accent transition-colors"
            title="Reset to today"
          >
            Today
          </button>
        </div>

        <div className="flex items-center gap-2">
          <span className="md:hidden text-xs text-zinc-400 font-medium">
            {long}
          </span>
          {isEditing && (
            <span className="inline-flex items-center gap-1.5 border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-400">
              ⚠️ Editing existing data
            </span>
          )}
        </div>
      </div>

      {/* Feedback Alert */}
      {feedback && (
        <div
          className={`p-4 border flex items-center gap-2.5 text-sm font-medium shadow-sm ${
            feedback.type === "success"
              ? "border-emerald-500/40 bg-emerald-500/10 text-emerald-400"
              : "border-destructive/40 bg-destructive/10 text-destructive"
          }`}
        >
          {feedback.type === "success" ? (
            <CheckCircle2 className="w-5 h-5 shrink-0" />
          ) : (
            <AlertCircle className="w-5 h-5 shrink-0" />
          )}
          <span>{feedback.message}</span>
        </div>
      )}

      {/* Fine Reason / Info Section */}
      <section className="border border-border bg-card p-4 sm:p-6 shadow-sm">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
          <div>
            <h2 className="text-base font-semibold text-foreground flex items-center gap-2">
              <Scale className="w-4 h-4 text-rose-400" />
              <span>Fine Details & Reason</span>
            </h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Specify reason or penalty note for this day's fines (optional)
            </p>
          </div>
        </div>

        <div className="space-y-3">
          <input
            type="text"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="e.g. Late meal notice, cooking duty miss, mess rules violation..."
            className="w-full border border-border bg-background/70 px-3.5 py-2.5 text-sm text-foreground placeholder:text-muted-foreground outline-none transition-colors focus:border-primary focus:ring-1 focus:ring-primary"
            aria-label="Fine reason"
          />

          <div className="flex flex-wrap items-center gap-1.5">
            <span className="text-xs text-zinc-400 flex items-center gap-1 mr-1">
              <Tag className="w-3 h-3" /> Quick Tags:
            </span>
            {PRESET_REASONS.map((pr) => (
              <button
                key={pr}
                type="button"
                onClick={() => setReason(pr)}
                className={`cursor-pointer px-2.5 py-1 text-xs font-medium border transition-colors ${
                  reason === pr
                    ? "border-rose-500 bg-rose-500/20 text-rose-300"
                    : "border-border/80 bg-background/50 text-zinc-300 hover:bg-accent hover:text-foreground"
                }`}
              >
                {pr}
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Member Fine Entry Section (Single Column, No Day/Night options) */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        {loading ? (
          <div className="p-8 text-center text-muted-foreground animate-pulse flex items-center justify-center gap-2">
            <Loader2 className="w-5 h-5 animate-spin text-primary" />
            <span>Loading fine entries for {short}...</span>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm min-w-[420px]">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                  <th className="w-14 px-4 py-3 text-left">#</th>
                  <th className="px-4 py-3 text-left text-zinc-300">Member</th>
                  <th className="w-48 sm:w-64 bg-rose-500/10 px-4 py-3 text-center text-rose-400 font-semibold border-l border-border">
                    ⚖️ Fine
                  </th>
                </tr>
              </thead>
              <tbody>
                {persons.map((p, i) => (
                  <tr
                    key={p.id}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td className="px-4 py-2.5 font-mono text-zinc-400 text-xs">
                      {i + 1}
                    </td>
                    <td className="px-4 py-2.5 font-medium text-foreground">
                      {p.name}
                    </td>
                    <td className="px-4 py-2.5 text-center border-l border-border/40">
                      <input
                        type="number"
                        min={0}
                        max={20}
                        value={fines[p.id] ?? 0}
                        aria-label={`${p.name} fine`}
                        onChange={(e) =>
                          setFines({
                            ...fines,
                            [p.id]: clamp(e.target.value),
                          })
                        }
                        onFocus={(e) => e.target.select()}
                        className="w-24 border border-rose-500/40 bg-rose-500/10 py-2 min-h-[44px] text-center font-mono text-base font-semibold text-rose-300 outline-none transition-colors focus:border-rose-400 focus:bg-rose-500/20 [color-scheme:dark]"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                  <td
                    colSpan={2}
                    className="px-4 py-3 text-right text-xs uppercase tracking-wider text-zinc-400"
                  >
                    TOTAL FINES:
                  </td>
                  <td className="px-4 py-3 text-center font-mono text-base font-bold text-rose-300 border-l border-border">
                    {sum(fines)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}

        <div className="flex flex-wrap items-center gap-2 border-t border-border p-3 bg-card/60">
          <span className="text-xs font-medium text-zinc-400 mr-1 hidden sm:inline">
            Presets:
          </span>
          {quick.map((q) => (
            <button
              key={q.label}
              type="button"
              onClick={q.run}
              className={`cursor-pointer border border-border bg-card px-3 py-2 min-h-[40px] text-xs font-medium text-zinc-300 transition-colors hover:border-zinc-500 hover:bg-accent hover:text-foreground active:scale-95 flex items-center justify-center ${q.cls}`}
            >
              {q.label}
            </button>
          ))}
        </div>
      </section>

      {/* Save Button */}
      <button
        type="button"
        onClick={save}
        disabled={saving || loading || persons.length === 0}
        className="w-full cursor-pointer bg-primary py-3.5 sm:py-4 min-h-[52px] text-base sm:text-lg font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-2"
      >
        {saving && <Loader2 className="w-5 h-5 animate-spin" />}
        <span>💾 Save All Fines for {short}</span>
      </button>

      {/* Monthly Overview & History Section */}
      <section className="space-y-4 pt-4">
        <div className="flex items-center justify-between border-b border-border pb-3">
          <h2 className="text-lg font-bold tracking-tight text-foreground flex items-center gap-2">
            <History className="w-5 h-5 text-primary" />
            <span>Monthly Fine Overview ({currentMonth})</span>
          </h2>
          {monthlyLoading && (
            <Loader2 className="w-4 h-4 animate-spin text-muted-foreground" />
          )}
        </div>

        {/* Overview Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div className="border border-border bg-card p-4">
            <div className="flex items-center justify-between text-xs font-medium text-muted-foreground">
              <span>Total Fines (Month)</span>
              <Scale className="w-4 h-4 text-rose-400" />
            </div>
            <div className="mt-2 text-2xl font-bold font-mono text-rose-400">
              {monthlyData?.overallCount ?? 0}
            </div>
          </div>

          <div className="border border-border bg-card p-4">
            <div className="flex items-center justify-between text-xs font-medium text-muted-foreground">
              <span>Active Fine Days</span>
              <Clock className="w-4 h-4 text-amber-400" />
            </div>
            <div className="mt-2 text-2xl font-bold font-mono text-amber-400">
              {monthlyData?.dailyHistory?.length ?? 0}
            </div>
          </div>

          <div className="border border-border bg-card p-4">
            <div className="flex items-center justify-between text-xs font-medium text-muted-foreground">
              <span>Members Fined</span>
              <Users className="w-4 h-4 text-primary" />
            </div>
            <div className="mt-2 text-2xl font-bold font-mono text-foreground">
              {monthlyData?.memberSummary?.filter((m) => m.totalCount > 0)
                .length ?? 0}
            </div>
          </div>
        </div>

        {/* Member Monthly Breakdown Grid */}
        <div className="border border-border bg-card p-4">
          <h3 className="text-sm font-semibold text-foreground mb-3">
            Member Monthly Fine Totals
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2.5">
            {monthlyData?.memberSummary.map((m) => (
              <div
                key={m.personId}
                className={`border p-2.5 text-center transition-colors ${
                  m.totalCount > 0
                    ? "border-rose-500/40 bg-rose-500/10 text-rose-300"
                    : "border-border/60 bg-background/40 text-muted-foreground"
                }`}
              >
                <div className="text-xs font-medium truncate">
                  {m.personName}
                </div>
                <div className="text-lg font-bold font-mono mt-1">
                  {m.totalCount}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* History Log Table */}
        {monthlyData?.dailyHistory && monthlyData.dailyHistory.length > 0 && (
          <div className="border border-border bg-card shadow-sm overflow-hidden">
            <div className="p-3.5 border-b border-border bg-muted/30 font-semibold text-xs uppercase tracking-wider text-zinc-300">
              Recent Fines Log
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[500px]">
                <thead>
                  <tr className="border-b border-border bg-muted/20 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                    <th className="px-4 py-2.5 text-left">Date</th>
                    <th className="px-4 py-2.5 text-left">Fined Members</th>
                    <th className="px-4 py-2.5 text-center">Total Fines</th>
                    <th className="px-4 py-2.5 text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {monthlyData.dailyHistory.map((d) => (
                    <tr
                      key={d.date}
                      className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                    >
                      <td className="px-4 py-2.5 font-mono font-medium text-foreground">
                        {d.date}
                      </td>
                      <td className="px-4 py-2.5 text-zinc-300">
                        <div className="flex flex-wrap gap-1.5">
                          {d.entries.map((e) => (
                            <span
                              key={e.personId}
                              className="inline-flex items-center gap-1 border border-rose-500/30 bg-rose-500/10 px-2 py-0.5 text-xs text-rose-300"
                            >
                              <span>{e.name}</span>
                              <span className="font-mono font-bold">
                                ({e.fineCount})
                              </span>
                              {e.reason && (
                                <span className="text-[10px] text-zinc-400">
                                  - {e.reason}
                                </span>
                              )}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-4 py-2.5 text-center font-mono font-bold text-rose-400">
                        {d.totalCount}
                      </td>
                      <td className="px-4 py-2.5 text-right">
                        <button
                          type="button"
                          onClick={() => handleDateChange(d.date)}
                          className="cursor-pointer border border-border px-2.5 py-1 text-xs font-medium text-zinc-300 hover:bg-primary hover:text-primary-foreground hover:border-primary transition-colors"
                        >
                          Load & Edit
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </section>
    </div>
  )
}
