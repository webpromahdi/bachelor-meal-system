import { useState, useEffect, useCallback } from "react"
import { RotateCcw, CheckCircle2, AlertCircle, Loader2 } from "lucide-react"
import { api } from "../lib/api"

type UtilityRecord = {
  wifi: number
  current: number
  gas: number
  other: number
}

const KEYS: { k: keyof UtilityRecord; label: string }[] = [
  { k: "wifi", label: "📶 WiFi" },
  { k: "current", label: "⚡ Current" },
  { k: "gas", label: "🔥 Gas" },
  { k: "other", label: "🔧 Other" },
]

const fmtN = (n: number) => Math.round(n).toLocaleString("en-US")
const num = (v: string) => Math.max(0, parseFloat(v) || 0)

interface MemberUtilityRow {
  id: number
  name: string
  wifi: number
  current: number
  gas: number
  other: number
  subtotal: number
}

interface MemberFundRow {
  id: number
  name: string
  collected: number
  spent: number
  balance: number
}

interface SharedUtilityItem {
  type: string
  amount: number
  lastReset: string
  daysRunning: number
}

function TimelineCard({
  type,
  title,
  accent,
  btn,
  focus,
  data,
  onRefresh,
}: {
  type: "current" | "gas"
  title: string
  accent: string
  btn: string
  focus: string
  data?: SharedUtilityItem
  onRefresh: () => void
}) {
  const initialDate = data?.lastReset
    ? new Date(data.lastReset).toISOString().split("T")[0]
    : new Date().toISOString().split("T")[0]

  const [startDate, setStartDate] = useState(initialDate)
  const [amount, setAmount] = useState(data?.amount || 0)
  const [saving, setSaving] = useState(false)
  const [resetting, setResetting] = useState(false)
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    if (data?.lastReset) {
      setStartDate(new Date(data.lastReset).toISOString().split("T")[0])
    }
    if (data?.amount !== undefined) {
      setAmount(data.amount)
    }
  }, [data])

  const handleUpdate = async () => {
    setSaving(true)
    try {
      const res: any = await api.post("/utilities/shared/update", {
        type,
        amount,
        start_date: startDate,
      })
      if (res.success) {
        setSaved(true)
        setTimeout(() => setSaved(false), 2500)
        onRefresh()
      }
    } catch (err) {
      console.error(`Failed to update ${type}`, err)
    } finally {
      setSaving(false)
    }
  }

  const handleReset = async () => {
    if (!window.confirm(`Reset ${title} meter timeline and amount to 0?`))
      return
    setResetting(true)
    try {
      const res: any = await api.post("/utilities/shared/reset", { type })
      if (res.success) {
        onRefresh()
      }
    } catch (err) {
      console.error(`Failed to reset ${type}`, err)
    } finally {
      setResetting(false)
    }
  }

  return (
    <div
      className={`border border-l-4 border-border bg-card p-4 sm:p-6 shadow-sm ${accent}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <div className="font-bold text-base text-foreground">{title}</div>
          <div className="mt-1 text-xs sm:text-sm text-zinc-400 font-medium">
            Running for{" "}
            <span className="font-mono text-foreground font-bold">
              {data?.daysRunning ?? 0}
            </span>{" "}
            Days
          </div>
        </div>
        <button
          type="button"
          onClick={handleReset}
          disabled={resetting}
          className="cursor-pointer border border-destructive/40 bg-destructive/10 px-3 py-1.5 min-h-[36px] text-xs font-medium text-destructive transition-colors hover:bg-destructive/20 active:scale-95 disabled:opacity-50"
        >
          {resetting ? "Resetting..." : "Reset"}
        </button>
      </div>
      <div className="mt-5 space-y-4">
        <div>
          <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-400">
            Start Date
          </label>
          <input
            type="date"
            value={startDate}
            onChange={(e) => {
              if (e.target.value) {
                setStartDate(e.target.value)
                setSaved(false)
              }
            }}
            className={`w-full border border-border bg-card/60 px-3 py-2 min-h-[44px] text-base sm:text-sm font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:ring-1 [color-scheme:dark] ${focus}`}
          />
        </div>
        <div>
          <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-zinc-400">
            Total Loaded Amount (BDT)
          </label>
          <input
            type="number"
            min={0}
            value={amount}
            onChange={(e) => {
              setAmount(num(e.target.value))
              setSaved(false)
            }}
            onFocus={(e) => e.target.select()}
            className={`w-full border border-border bg-card/60 px-3 py-2 min-h-[44px] font-mono text-base font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:ring-1 [color-scheme:dark] ${focus}`}
          />
        </div>
        <button
          type="button"
          onClick={handleUpdate}
          disabled={saving}
          className={`w-full cursor-pointer px-5 py-2.5 min-h-[44px] font-semibold text-white shadow-sm transition-all hover:opacity-90 active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-1.5 ${btn}`}
        >
          {saving ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              <span>Saving...</span>
            </>
          ) : saved ? (
            "✓ Updated"
          ) : (
            "Update"
          )}
        </button>
      </div>
    </div>
  )
}

export default function Utility() {
  const [loading, setLoading] = useState<boolean>(true)
  const [savingUtil, setSavingUtil] = useState<boolean>(false)
  const [savingFund, setSavingFund] = useState<boolean>(false)
  const [alert, setAlert] = useState<{
    type: "success" | "error"
    msg: string
  } | null>(null)

  // Editable local state maps keyed by personId
  const [utilState, setUtilState] = useState<Record<number, UtilityRecord>>({})
  const [fundState, setFundState] = useState<Record<number, number>>({})
  const [personsList, setPersonsList] = useState<{ id: number; name: string }[]>(
    [],
  )

  // Remote server data state for shared utilities and calculations
  const [sharedUtils, setSharedUtils] =
    useState<Record<string, SharedUtilityItem>>({})

  const fetchUtilitiesData = useCallback(async () => {
    try {
      const res: any = await api.get("/utilities")
      if (res.success && res.data) {
        const uList: MemberUtilityRow[] = res.data.utilities || []
        const fList: MemberFundRow[] = res.data.fund || []

        const newUtilMap: Record<number, UtilityRecord> = {}
        const newFundMap: Record<number, number> = {}
        const pList: { id: number; name: string }[] = []

        for (const u of uList) {
          pList.push({ id: u.id, name: u.name })
          newUtilMap[u.id] = {
            wifi: u.wifi,
            current: u.current,
            gas: u.gas,
            other: u.other,
          }
        }

        for (const f of fList) {
          newFundMap[f.id] = f.collected
        }

        setPersonsList(pList)
        setUtilState(newUtilMap)
        setFundState(newFundMap)
        setSharedUtils(res.data.sharedUtilities || {})
      }
    } catch (err) {
      console.error("Failed to load utilities data", err)
      setAlert({
        type: "error",
        msg: "Failed to connect to utilities backend.",
      })
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchUtilitiesData()
  }, [fetchUtilitiesData])

  // Calculations
  const getSubtotal = (pid: number) => {
    const u = utilState[pid]
    if (!u) return 0
    return (u.wifi || 0) + (u.current || 0) + (u.gas || 0) + (u.other || 0)
  }

  const colTotal = (k: keyof UtilityRecord) =>
    personsList.reduce((acc, p) => acc + (utilState[p.id]?.[k] || 0), 0)

  const spentTotal = personsList.reduce((acc, p) => acc + getSubtotal(p.id), 0)
  const collectedTotal = personsList.reduce(
    (acc, p) => acc + (fundState[p.id] || 0),
    0,
  )
  const remainingBalance = collectedTotal - spentTotal

  // Actions
  const handleSaveUtilities = async () => {
    setSavingUtil(true)
    setAlert(null)
    try {
      const payload = personsList.map((p) => ({
        personId: p.id,
        wifi: utilState[p.id]?.wifi || 0,
        current: utilState[p.id]?.current || 0,
        gas: utilState[p.id]?.gas || 0,
        other: utilState[p.id]?.other || 0,
      }))

      const res: any = await api.post("/utilities/expenses", {
        utilities: payload,
      })
      if (res.success) {
        setAlert({
          type: "success",
          msg: "Individual utility expenses saved successfully!",
        })
        await fetchUtilitiesData()
      } else {
        setAlert({
          type: "error",
          msg: res.error || "Failed to save utilities.",
        })
      }
    } catch (err: any) {
      console.error("Utility save error", err)
      setAlert({
        type: "error",
        msg: err.response?.data?.error || "Error saving utility data.",
      })
    } finally {
      setSavingUtil(false)
    }
  }

  const handleSaveFund = async () => {
    setSavingFund(true)
    setAlert(null)
    try {
      const payload = personsList.map((p) => ({
        personId: p.id,
        collected: fundState[p.id] || 0,
      }))

      const res: any = await api.post("/utilities/fund", { fund: payload })
      if (res.success) {
        setAlert({
          type: "success",
          msg: "Utility fund collections saved successfully!",
        })
        await fetchUtilitiesData()
      } else {
        setAlert({ type: "error", msg: res.error || "Failed to save fund." })
      }
    } catch (err: any) {
      console.error("Fund save error", err)
      setAlert({
        type: "error",
        msg: err.response?.data?.error || "Error saving fund data.",
      })
    } finally {
      setSavingFund(false)
    }
  }

  const handleResetUtilExpenses = async () => {
    if (!window.confirm("Reset all member utility expenses to zero?")) return
    setLoading(true)
    setAlert(null)
    try {
      const res: any = await api.post("/utilities/reset-expenses")
      if (res.success) {
        setAlert({
          type: "success",
          msg: "All utility expenses reset to zero.",
        })
        await fetchUtilitiesData()
      }
    } catch (err: any) {
      setAlert({ type: "error", msg: "Failed to reset utility expenses." })
    } finally {
      setLoading(false)
    }
  }

  const handleNewCycleReset = async () => {
    if (
      !window.confirm(
        "Start a new cycle? This clears both utility expenses and collected fund balances.",
      )
    )
      return
    setLoading(true)
    setAlert(null)
    try {
      const res: any = await api.post("/utilities/fund/reset")
      if (res.success) {
        setAlert({
          type: "success",
          msg: "New cycle initiated. Expenses and fund cleared.",
        })
        await fetchUtilitiesData()
      }
    } catch (err: any) {
      setAlert({ type: "error", msg: "Failed to reset for new cycle." })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="p-3.5 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6 sm:space-y-8">
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

      {/* Page Header */}
      <header className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border pb-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-foreground">
            Utility Expenses
          </h1>
          <p className="mt-1 text-sm text-zinc-400">
            Manage ongoing utility costs and monthly shared expenses
          </p>
        </div>
        <button
          type="button"
          onClick={handleResetUtilExpenses}
          className="w-full sm:w-auto cursor-pointer border border-destructive/50 bg-destructive/10 px-4 py-2 min-h-[44px] text-sm font-medium text-destructive transition-colors hover:bg-destructive/20 active:scale-95 flex items-center justify-center gap-2"
        >
          <RotateCcw className="w-4 h-4" />
          <span>Reset Utility Data</span>
        </button>
      </header>

      {loading ? (
        <div className="border border-border bg-card p-12 text-center text-zinc-400 animate-pulse font-medium shadow-sm">
          Loading utility data from server...
        </div>
      ) : (
        <>
          {/* Individual Utility Expenses Section */}
          <section className="border border-border bg-card shadow-sm overflow-hidden p-4 sm:p-6 space-y-4">
            <div>
              <h2 className="text-lg font-semibold text-foreground">
                Individual Utility Expenses
              </h2>
              <p className="text-xs text-zinc-400 mt-0.5">
                Breakdown per member for WiFi, Electricity, Gas, and Other
                expenses
              </p>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[640px]">
                <thead>
                  <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                    <th className="px-4 py-3 text-left">Member Name</th>
                    {KEYS.map((c) => (
                      <th key={c.k} className="px-3 py-3 text-center">
                        {c.label}
                      </th>
                    ))}
                    <th className="px-4 py-3 text-right text-zinc-300">
                      Subtotal
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {personsList.map((p) => {
                    const rowData = utilState[p.id] || {
                      wifi: 0,
                      current: 0,
                      gas: 0,
                      other: 0,
                    }
                    return (
                      <tr
                        key={p.id}
                        className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                      >
                        <td className="px-4 py-2.5 font-medium text-foreground">
                          {p.name}
                        </td>
                        {KEYS.map((c) => (
                          <td key={c.k} className="px-3 py-2.5 text-center">
                            <input
                              type="number"
                              min={0}
                              value={rowData[c.k] || ""}
                              placeholder="0"
                              aria-label={`${p.name} ${c.k}`}
                              onChange={(e) => {
                                const val = num(e.target.value)
                                setUtilState({
                                  ...utilState,
                                  [p.id]: {
                                    ...rowData,
                                    [c.k]: val,
                                  },
                                })
                              }}
                              onFocus={(e) => e.target.select()}
                              className="w-24 border border-border bg-card/60 py-2 min-h-[44px] text-center font-mono text-base sm:text-sm font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:border-primary focus:bg-background focus:ring-1 focus:ring-primary [color-scheme:dark]"
                            />
                          </td>
                        ))}
                        <td className="px-4 py-2.5 text-right font-mono font-bold text-primary">
                          ৳ {fmtN(getSubtotal(p.id))}
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
                <tfoot>
                  <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                    <td className="px-4 py-3 text-right text-xs uppercase tracking-wider text-zinc-400">
                      Column Total:
                    </td>
                    {KEYS.map((c) => (
                      <td
                        key={c.k}
                        className="px-3 py-3 text-center font-mono font-bold text-zinc-200"
                      >
                        ৳ {fmtN(colTotal(c.k))}
                      </td>
                    ))}
                    <td className="px-4 py-3 text-right font-mono font-bold text-primary text-base">
                      ৳ {fmtN(spentTotal)}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div className="pt-2 flex justify-end">
              <button
                type="button"
                onClick={handleSaveUtilities}
                disabled={savingUtil}
                className="w-full sm:w-auto cursor-pointer bg-primary px-8 py-3 min-h-[48px] font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {savingUtil ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Saving...</span>
                  </>
                ) : (
                  "💾 Save Utility Updates"
                )}
              </button>
            </div>
          </section>

          {/* Utility Fund Overview Section */}
          <section className="space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h2 className="text-xl font-semibold text-foreground flex items-center gap-2">
                  <span>💰</span> Utility Fund Overview
                </h2>
                <p className="mt-0.5 text-xs sm:text-sm text-zinc-400">
                  Track pooled contributions and individual member balances
                </p>
              </div>
              <button
                type="button"
                onClick={handleNewCycleReset}
                className="w-full sm:w-auto cursor-pointer border border-primary/50 bg-primary/10 px-4 py-2 min-h-[44px] text-sm font-medium text-primary transition-colors hover:bg-primary/20 active:scale-95 flex items-center justify-center gap-1.5"
              >
                <RotateCcw className="w-4 h-4" />
                <span>New Cycle Reset</span>
              </button>
            </div>

            {/* 3 Overview Stat Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
              <div className="border border-border bg-card p-4 sm:p-5 text-center shadow-sm">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                  Total Collected
                </div>
                <div className="mt-2 font-mono text-2xl sm:text-3xl font-bold text-emerald-400">
                  ৳ {fmtN(collectedTotal)}
                </div>
              </div>
              <div className="border border-border bg-card p-4 sm:p-5 text-center shadow-sm">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                  Total Spent
                </div>
                <div className="mt-2 font-mono text-2xl sm:text-3xl font-bold text-rose-400">
                  ৳ {fmtN(spentTotal)}
                </div>
              </div>
              <div className="border border-border bg-card p-4 sm:p-5 text-center shadow-sm">
                <div className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
                  Remaining Balance
                </div>
                <div
                  className={`mt-2 font-mono text-2xl sm:text-3xl font-bold ${
                    remainingBalance < 0 ? "text-rose-400" : "text-primary"
                  }`}
                >
                  ৳ {fmtN(remainingBalance)}
                </div>
              </div>
            </div>

            {/* Member Balances Table */}
            <div className="border border-border bg-card shadow-sm overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-sm min-w-[560px]">
                  <thead>
                    <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                      <th className="px-5 py-3 text-left">Member Name</th>
                      <th className="px-4 py-3 text-center">
                        💵 Collected (BDT)
                      </th>
                      <th className="px-4 py-3 text-right">💸 Spent (BDT)</th>
                      <th className="px-5 py-3 text-right">🏦 Balance (BDT)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {personsList.map((p) => {
                      const spent = getSubtotal(p.id)
                      const col = fundState[p.id] || 0
                      const bal = col - spent
                      return (
                        <tr
                          key={p.id}
                          className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                        >
                          <td className="px-5 py-2.5 font-medium text-foreground">
                            {p.name}
                          </td>
                          <td className="px-4 py-2.5 text-center">
                            <input
                              type="number"
                              min={0}
                              value={col || ""}
                              placeholder="0"
                              aria-label={`${p.name} collected`}
                              onChange={(e) => {
                                const val = num(e.target.value)
                                setFundState({
                                  ...fundState,
                                  [p.id]: val,
                                })
                              }}
                              onFocus={(e) => e.target.select()}
                              className="w-32 border border-border bg-card/60 py-2 min-h-[44px] text-center font-mono text-base sm:text-sm font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:border-emerald-500 focus:bg-background focus:ring-1 focus:ring-emerald-500 [color-scheme:dark]"
                            />
                          </td>
                          <td className="px-4 py-2.5 text-right font-mono font-medium text-rose-400">
                            ৳ {fmtN(spent)}
                          </td>
                          <td
                            className={`px-5 py-2.5 text-right font-mono font-bold ${
                              bal >= 0 ? "text-emerald-400" : "text-amber-400"
                            }`}
                          >
                            {bal >= 0 ? "✅" : "⚠️"} {bal < 0 ? "-" : ""}৳{" "}
                            {fmtN(Math.abs(bal))}
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                  <tfoot>
                    <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                      <td className="px-5 py-3 text-xs uppercase tracking-wider text-zinc-400">
                        Total
                      </td>
                      <td className="px-4 py-3 text-center font-mono font-bold text-emerald-400">
                        ৳ {fmtN(collectedTotal)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono font-bold text-rose-400">
                        ৳ {fmtN(spentTotal)}
                      </td>
                      <td
                        className={`px-5 py-3 text-right font-mono font-bold text-base ${
                          remainingBalance >= 0
                            ? "text-emerald-400"
                            : "text-amber-400"
                        }`}
                      >
                        {remainingBalance < 0 ? "-" : ""}৳{" "}
                        {fmtN(Math.abs(remainingBalance))}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <div className="flex justify-end">
              <button
                type="button"
                onClick={handleSaveFund}
                disabled={savingFund}
                className="w-full sm:w-auto cursor-pointer bg-emerald-600 px-8 py-3 min-h-[48px] font-semibold text-white shadow-sm transition-all hover:bg-emerald-500 active:scale-[0.99] disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {savingFund ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    <span>Saving...</span>
                  </>
                ) : (
                  "💾 Save Fund Updates"
                )}
              </button>
            </div>
          </section>

          {/* Shared Utilities Timeline Section */}
          <section className="space-y-4">
            <div className="border-b border-border pb-2">
              <h2 className="text-xl font-semibold text-foreground flex items-center gap-2">
                <span>⏳</span> Shared Utilities Timeline
              </h2>
              <p className="mt-0.5 text-xs sm:text-sm text-zinc-400">
                Track reload cycles and running days for shared meters
              </p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
              <TimelineCard
                type="current"
                title="⚡ Current (Electricity)"
                accent="border-l-amber-500"
                btn="bg-amber-600 hover:bg-amber-500"
                focus="focus:border-amber-500 focus:ring-amber-500"
                data={sharedUtils.current}
                onRefresh={fetchUtilitiesData}
              />
              <TimelineCard
                type="gas"
                title="🔥 Gas"
                accent="border-l-orange-500"
                btn="bg-orange-600 hover:bg-orange-500"
                focus="focus:border-orange-500 focus:ring-orange-500"
                data={sharedUtils.gas}
                onRefresh={fetchUtilitiesData}
              />
            </div>
          </section>
        </>
      )}
    </div>
  )
}
