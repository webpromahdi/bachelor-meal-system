import { useState, useEffect, useCallback } from "react"
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  CheckCircle2,
  AlertCircle,
  Loader2,
} from "lucide-react"
import { api } from "../lib/api"

export interface Person {
  id: number
  name: string
}

const CATS = [
  {
    id: "chicken",
    icon: "🍗",
    label: "Chicken / Meat",
    col: "🍗 Chicken",
    border: "border-l-orange-500",
  },
  {
    id: "fish",
    icon: "🐟",
    label: "Fish / Seafood",
    col: "🐟 Fish",
    border: "border-l-sky-500",
  },
  {
    id: "dim",
    icon: "🥚",
    label: "Dim (Egg)",
    col: "🥚 Dim",
    border: "border-l-yellow-400",
  },
  {
    id: "other",
    icon: "🥗",
    label: "Other / Vegetables",
    col: "🥗 Other",
    border: "border-l-green-500",
    badge: "MEAL-BASED",
  },
  {
    id: "special",
    icon: "⭐",
    label: "Special Meal",
    col: "⭐ Special",
    border: "border-l-fuchsia-500",
    badge: "MEAL-BASED",
  },
  {
    id: "rice",
    icon: "🍚",
    label: "Rice (Chal)",
    col: "🍚 Rice",
    border: "border-l-amber-600",
    badge: "PERSON-WISE",
    warn: true,
  },
]

type Amounts = Record<string, number>
const zeroAmounts = (): Amounts =>
  Object.fromEntries(CATS.map((c) => [c.id, 0]))
const calcTotal = (a: Amounts) => CATS.reduce((s, c) => s + (a[c.id] || 0), 0)
const fmt = (n: number) =>
  n.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

const getTodayStr = () => {
  const now = new Date()
  const y = now.getFullYear()
  const m = String(now.getMonth() + 1).padStart(2, "0")
  const d = String(now.getDate()).padStart(2, "0")
  return `${y}-${m}-${d}`
}

export default function Bazar() {
  const [date, setDate] = useState<string>(getTodayStr())
  const [persons, setPersons] = useState<Person[]>([])
  const [payerId, setPayerId] = useState<string>("")
  const [amt, setAmt] = useState<Amounts>(zeroAmounts())
  const [existingDataFound, setExistingDataFound] = useState<boolean>(false)

  // Table data state from GET /bazar?date=...
  const [tableData, setTableData] = useState<{
    categoryTotals: Record<string, number>
    grandTotal: number
    byPerson: Record<number, {
      name: string
      amounts: Record<string, number>
      total: number
    }>
  }>({
    categoryTotals: zeroAmounts(),
    grandTotal: 0,
    byPerson: {},
  })

  const [loading, setLoading] = useState<boolean>(true)
  const [saving, setSaving] = useState<boolean>(false)
  const [alert, setAlert] = useState<{
    type: "success" | "error"
    msg: string
  } | null>(null)

  // 1. Fetch Persons list once on mount
  useEffect(() => {
    const fetchPersons = async () => {
      try {
        const res: any = await api.get("/persons")
        if (res.success && Array.isArray(res.data)) {
          setPersons(res.data)
          if (res.data.length > 0 && !payerId) {
            setPayerId(String(res.data[0].id))
          }
        }
      } catch (err) {
        console.error("Failed to load persons", err)
      }
    }
    fetchPersons()
  }, [])

  // 2. Fetch table entries for the date
  const fetchTableEntries = useCallback(async (targetDate: string) => {
    try {
      const res: any = await api.get(`/bazar?date=${targetDate}`)
      if (res.success && res.data) {
        setTableData({
          categoryTotals: res.data.categoryTotals || zeroAmounts(),
          grandTotal: res.data.grandTotal || 0,
          byPerson: res.data.byPerson || {},
        })
      }
    } catch (err) {
      console.error("Failed to fetch bazar entries for date", err)
    }
  }, [])

  // 3. Fetch specific person's entries on this date to populate the form
  const fetchPersonAmounts = useCallback(
    async (targetDate: string, pid: string) => {
      if (!pid) {
        setAmt(zeroAmounts())
        setExistingDataFound(false)
        return
      }

      try {
        const res: any = await api.get(
          `/bazar/person?date=${targetDate}&personId=${pid}`,
        )
        if (res.success && res.data) {
          if (res.data.hasExistingData && res.data.categoryAmounts) {
            setAmt(res.data.categoryAmounts)
            setExistingDataFound(true)
          } else {
            setAmt(zeroAmounts())
            setExistingDataFound(false)
          }
        }
      } catch (err) {
        console.error("Failed to fetch person bazar data", err)
        setAmt(zeroAmounts())
        setExistingDataFound(false)
      }
    },
    [],
  )

  // 4. Trigger data loads when date or payerId changes
  useEffect(() => {
    const load = async () => {
      setLoading(true)
      await Promise.all([
        fetchTableEntries(date),
        payerId ? fetchPersonAmounts(date, payerId) : Promise.resolve(),
      ])
      setLoading(false)
    }
    load()
  }, [date, payerId, fetchTableEntries, fetchPersonAmounts])

  const shiftDate = (days: number) => {
    const parts = date.split("-").map(Number)
    if (
      parts.length === 3 &&
      !isNaN(parts[0]) &&
      !isNaN(parts[1]) &&
      !isNaN(parts[2])
    ) {
      const cur = new Date(parts[0], parts[1] - 1, parts[2] + days)
      const nextStr = `${cur.getFullYear()}-${String(cur.getMonth() + 1).padStart(2, "0")}-${String(cur.getDate()).padStart(2, "0")}`
      setDate(nextStr)
      setAlert(null)
    }
  }

  const handlePayerChange = (newPid: string) => {
    setPayerId(newPid)
    setAlert(null)
  }

  const handleSave = async () => {
    if (!payerId) {
      setAlert({ type: "error", msg: "Please select who paid for the bazar." })
      return
    }

    setSaving(true)
    setAlert(null)

    try {
      const res: any = await api.post("/bazar/upsert", {
        date,
        paidBy: parseInt(payerId),
        amounts: amt,
      })

      if (res.success) {
        setAlert({
          type: "success",
          msg: res.message || "Bazar expenses saved successfully!",
        })
        setExistingDataFound(calcTotal(amt) > 0)
        // Refresh table data
        await fetchTableEntries(date)
      } else {
        setAlert({
          type: "error",
          msg: res.error || "Failed to save bazar expenses.",
        })
      }
    } catch (err: any) {
      console.error("Bazar save error", err)
      const errMsg =
        err.response?.data?.error ||
        err.message ||
        "An error occurred while saving."
      setAlert({ type: "error", msg: errMsg })
    } finally {
      setSaving(false)
    }
  }

  const dObj = new Date(`${date}T00:00:00`)
  const dShort = isNaN(dObj.getTime())
    ? date
    : dObj.toLocaleDateString("en-US", { month: "short", day: "numeric" })
  const dLong = isNaN(dObj.getTime())
    ? date
    : dObj.toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
      })

  const selectedPersonName =
    persons.find((p) => String(p.id) === payerId)?.name || ""

  return (
    <div className="p-3.5 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-5 sm:space-y-6">
      {/* Alert Banner */}
      {alert && (
        <div
          role="alert"
          className={`flex items-center gap-3 border p-4 text-sm font-medium shadow-sm transition-all animate-in fade-in duration-200 ${
            alert.type === "success"
              ? "border-emerald-500/40 bg-emerald-500/10 text-emerald-400"
              : "border-rose-500/40 bg-rose-500/10 text-rose-400"
          }`}
        >
          {alert.type === "success" ? (
            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-400" />
          ) : (
            <AlertCircle className="h-5 w-5 shrink-0 text-rose-400" />
          )}
          <span className="flex-1">{alert.msg}</span>
          <button
            type="button"
            onClick={() => setAlert(null)}
            className="text-xs font-semibold uppercase tracking-wider hover:underline"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* Top Date Selection & Paid By Form */}
      <div className="border border-border bg-card p-4 sm:p-6 shadow-sm">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          {/* Date Picker Controls */}
          <div>
            <label
              htmlFor="bdate"
              className="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400"
            >
              📅 Bazar Date
            </label>
            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={() => shiftDate(-1)}
                className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1"
                aria-label="Previous day"
              >
                <ChevronLeft className="w-4 h-4 text-zinc-300" />
                <span className="hidden sm:inline">Prev</span>
              </button>

              <div className="flex-1 min-w-[160px] flex items-center gap-2 border border-border bg-background/60 px-3 py-2 min-h-[44px] transition-colors focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                <Calendar className="w-4 h-4 text-primary shrink-0" />
                <input
                  id="bdate"
                  type="date"
                  value={date}
                  onChange={(e) => {
                    if (e.target.value) {
                      setDate(e.target.value)
                      setAlert(null)
                    }
                  }}
                  className="w-full cursor-pointer bg-transparent text-base sm:text-sm font-semibold text-foreground outline-none [color-scheme:dark]"
                  aria-label="Select bazar date"
                />
              </div>

              <button
                type="button"
                onClick={() => shiftDate(1)}
                className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1"
                aria-label="Next day"
              >
                <span className="hidden sm:inline">Next</span>
                <ChevronRight className="w-4 h-4 text-zinc-300" />
              </button>

              <button
                type="button"
                onClick={() => {
                  setDate(getTodayStr())
                  setAlert(null)
                }}
                className="cursor-pointer border border-border bg-card/50 px-3 py-2 min-h-[44px] text-xs font-medium text-zinc-400 hover:text-foreground hover:bg-accent transition-colors"
                title="Reset to today"
              >
                Today
              </button>
            </div>
            <p className="mt-2 text-xs text-zinc-400 font-medium">{dLong}</p>
          </div>

          {/* Paid By Selection */}
          <div>
            <label
              htmlFor="payer"
              className="mb-2 block text-xs font-semibold uppercase tracking-wider text-zinc-400"
            >
              💰 Paid By
            </label>
            <div>
              <select
                id="payer"
                value={payerId}
                onChange={(e) => handlePayerChange(e.target.value)}
                className="w-full cursor-pointer border border-border bg-card/60 px-3 py-2 min-h-[44px] text-base sm:text-sm font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:border-primary focus:ring-1 focus:ring-primary [color-scheme:dark]"
              >
                <option value="" className="bg-card text-zinc-400">
                  -- Select Person --
                </option>
                {persons.map((p) => (
                  <option
                    key={p.id}
                    value={String(p.id)}
                    className="bg-card text-foreground"
                  >
                    {p.name}
                  </option>
                ))}
              </select>
            </div>
            <p className="mt-2 text-xs text-zinc-400">
              {selectedPersonName
                ? `Recording bazar purchases paid by ${selectedPersonName}`
                : "Select who purchased today's bazar"}
            </p>
          </div>
        </div>
      </div>

      {/* Existing Entries Notice */}
      {existingDataFound && selectedPersonName && (
        <div className="flex items-center gap-2 border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 text-sm font-medium text-amber-400 shadow-sm">
          <span>⚠️</span>
          <span>
            Existing entries found for{" "}
            <strong className="text-amber-300">{selectedPersonName}</strong> on{" "}
            <strong className="text-amber-300">{dShort}</strong> — saving will
            update them.
          </span>
        </div>
      )}

      {/* Category Expense Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
        {CATS.map((c) => (
          <div
            key={c.id}
            className={`border border-l-4 border-border bg-card p-4 sm:p-5 shadow-sm transition-colors hover:border-border/80 ${c.border}`}
          >
            <div className="flex items-start justify-between gap-2">
              <div className="flex items-center gap-2.5">
                <span className="text-3xl leading-none select-none">
                  {c.icon}
                </span>
                <span className="font-bold text-foreground text-sm sm:text-base">
                  {c.label}
                </span>
              </div>
              {c.badge && (
                <span
                  className={`px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wider font-semibold ${
                    c.warn
                      ? "border border-amber-500/40 bg-amber-500/10 text-amber-400"
                      : "border border-border bg-muted/60 text-zinc-300"
                  }`}
                >
                  {c.badge}
                </span>
              )}
            </div>

            <div className="mt-4 flex items-center gap-3">
              <span className="font-mono text-xs font-semibold text-zinc-400">
                BDT
              </span>
              <input
                type="number"
                min={0}
                value={amt[c.id] || ""}
                placeholder="0"
                aria-label={`${c.label} amount`}
                onChange={(e) =>
                  setAmt({
                    ...amt,
                    [c.id]: Math.max(0, parseFloat(e.target.value) || 0),
                  })
                }
                onFocus={(e) => e.target.select()}
                className="w-full border border-border bg-card/60 px-3 py-2 min-h-[44px] text-right font-mono text-xl font-bold text-foreground outline-none transition-colors placeholder:text-zinc-600 focus:border-primary focus:bg-background focus:ring-1 focus:ring-primary [color-scheme:dark]"
              />
            </div>
            {c.warn && (
              <p className="mt-2 text-xs font-medium text-amber-400">
                ⚠️ Cost goes ONLY to the payer — not shared
              </p>
            )}
          </div>
        ))}
      </div>

      {/* Summary Total & Save Button Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-border bg-card p-4 sm:p-5 shadow-sm">
        <div className="flex items-baseline gap-3">
          <span className="text-sm font-medium text-zinc-300">
            {selectedPersonName
              ? `${selectedPersonName}'s Total:`
              : "Today's Total:"}
          </span>
          <span className="font-mono text-2xl sm:text-3xl font-bold text-primary">
            ৳ {fmt(calcTotal(amt))}
          </span>
        </div>
        <button
          type="button"
          onClick={handleSave}
          disabled={!payerId || saving}
          className="w-full sm:w-auto cursor-pointer bg-primary px-6 py-3 min-h-[48px] font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-primary flex items-center justify-center gap-2"
        >
          {saving ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              <span>Saving...</span>
            </>
          ) : (
            `💾 Save Bazar for ${dShort}`
          )}
        </button>
      </div>

      {/* All Members' Entries Table */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 border-b border-border px-5 py-4 bg-card">
          <h2 className="text-base font-semibold text-foreground">
            Today's Entries — All Members
          </h2>
          <span className="text-xs font-medium text-zinc-400">{dLong}</span>
        </div>

        <div className="overflow-x-auto">
          {loading ? (
            <div className="p-8 text-center text-sm text-zinc-400 animate-pulse">
              Loading today's entries...
            </div>
          ) : (
            <table className="w-full text-sm min-w-[720px]">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                  <th className="px-5 py-3 text-left">Person</th>
                  {CATS.map((c) => (
                    <th key={c.id} className="px-4 py-3 text-right">
                      {c.col}
                    </th>
                  ))}
                  <th className="px-5 py-3 text-right text-zinc-300">Total</th>
                </tr>
              </thead>
              <tbody>
                {persons.map((p) => {
                  const r = tableData.byPerson[p.id]
                  const isSelectedPayer = String(p.id) === payerId
                  const personTotal = r ? r.total : 0

                  return (
                    <tr
                      key={p.id}
                      onClick={() => handlePayerChange(String(p.id))}
                      className={`cursor-pointer border-b border-border/60 transition-colors ${
                        isSelectedPayer
                          ? "bg-primary/10 hover:bg-primary/15"
                          : "hover:bg-accent/30"
                      }`}
                    >
                      <td className="px-5 py-2.5 font-medium text-foreground">
                        <span className="flex items-center gap-1.5">
                          {p.name}
                          {isSelectedPayer && (
                            <span className="text-[10px] font-semibold text-primary uppercase tracking-wider">
                              (Selected)
                            </span>
                          )}
                        </span>
                      </td>
                      {CATS.map((c) => {
                        const val = r?.amounts?.[c.id] || 0
                        return (
                          <td
                            key={c.id}
                            className={`px-4 py-2.5 text-right font-mono ${
                              val
                                ? "text-foreground font-medium"
                                : "text-zinc-500"
                            }`}
                          >
                            {val ? fmt(val) : "-"}
                          </td>
                        )
                      })}
                      <td
                        className={`px-5 py-2.5 text-right font-mono font-bold ${
                          personTotal ? "text-primary" : "text-zinc-500"
                        }`}
                      >
                        {personTotal ? fmt(personTotal) : "-"}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
              <tfoot>
                <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                  <td className="px-5 py-3 text-xs uppercase tracking-wider text-zinc-400">
                    TOTAL
                  </td>
                  {CATS.map((c) => (
                    <td
                      key={c.id}
                      className="px-4 py-3 text-right font-mono font-bold text-zinc-200"
                    >
                      {fmt(tableData.categoryTotals[c.id] || 0)}
                    </td>
                  ))}
                  <td className="px-5 py-3 text-right font-mono font-bold text-primary text-base">
                    ৳ {fmt(tableData.grandTotal)}
                  </td>
                </tr>
              </tfoot>
            </table>
          )}
        </div>
      </section>
    </div>
  )
}
