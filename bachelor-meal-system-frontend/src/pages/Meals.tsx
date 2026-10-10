import { useState, useEffect } from "react"
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

const TYPES = [
  { id: "fish", icon: "🐟", label: "Fish" },
  { id: "chicken", icon: "🍗", label: "Chicken" },
  { id: "dim", icon: "🥚", label: "Dim (Egg)" },
  { id: "other", icon: "🥗", label: "Other" },
  { id: "special", icon: "⭐", label: "Special" },
]

type Counts = Record<number, number>

const keyOf = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`

function TypePicker({
  label,
  sub,
  subClass,
  value,
  onChange,
}: {
  label: string
  sub: string
  subClass: string
  value: string
  onChange: (v: string) => void
}) {
  return (
    <div>
      <div className="font-semibold text-sm sm:text-base text-foreground">
        {label}
      </div>
      <div className={`mb-3 mt-0.5 text-xs font-medium ${subClass}`}>{sub}</div>
      <div
        role="radiogroup"
        aria-label={label}
        className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 sm:gap-2.5"
      >
        {TYPES.map((t, idx) => {
          const on = value.toLowerCase() === t.id.toLowerCase()
          const isLastOdd = idx === TYPES.length - 1 // 5th item
          return (
            <button
              key={t.id}
              type="button"
              role="radio"
              aria-checked={on}
              onClick={() => onChange(t.id)}
              className={`flex cursor-pointer flex-col items-center justify-center gap-1.5 border p-3 sm:p-4 min-h-[56px] text-xs font-medium transition-all ${
                isLastOdd ? "col-span-2 sm:col-span-1 md:col-span-1" : ""
              } ${
                on
                  ? "border-2 border-primary bg-primary/15 text-foreground shadow-sm shadow-primary/20 ring-1 ring-primary/40 font-semibold"
                  : "border-border bg-card/60 text-zinc-300 hover:border-zinc-500 hover:bg-accent/60 hover:text-foreground"
              }`}
            >
              <span className="text-2xl sm:text-3xl leading-none select-none">
                {t.icon}
              </span>
              <span className="tracking-tight text-center">{t.label}</span>
            </button>
          )
        })}
      </div>
    </div>
  )
}

export default function Meals() {
  const [date, setDate] = useState(() => new Date())
  const [persons, setPersons] = useState<Person[]>([])
  const [lunchType, setLunchType] = useState("fish")
  const [dinnerType, setDinnerType] = useState("chicken")
  const [lunch, setLunch] = useState<Counts>({})
  const [dinner, setDinner] = useState<Counts>({})
  const [isEditing, setIsEditing] = useState(false)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [feedback, setFeedback] = useState<{
    type: "success" | "error"
    message: string
  } | null>(null)

  const key = keyOf(date)

  // Helper to fill counts for all members
  const fill = (countList: Person[], v: number): Counts =>
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

  // 2. Fetch meals when date or persons change
  useEffect(() => {
    if (persons.length === 0) return

    const fetchMealsForDate = async () => {
      setLoading(true)
      setFeedback(null)
      try {
        const res: any = await api.get(`/meals?date=${key}`)
        if (res.success && res.data) {
          const {
            hasSavedData,
            lunchType: lt,
            dinnerType: dt,
            lunchCounts,
            dinnerCounts,
          } = res.data

          if (hasSavedData) {
            setIsEditing(true)
            setLunchType(lt ? lt.toLowerCase() : "fish")
            setDinnerType(dt ? dt.toLowerCase() : "chicken")

            const lMap: Counts = {}
            const dMap: Counts = {}
            for (const p of persons) {
              lMap[p.id] = lunchCounts[p.id] || 0
              dMap[p.id] = dinnerCounts[p.id] || 0
            }
            setLunch(lMap)
            setDinner(dMap)
          } else {
            // Business rule from meals.php: default to 1 Lunch + 1 Dinner if unrecorded
            setIsEditing(false)
            setLunchType("fish")
            setDinnerType("chicken")
            setLunch(fill(persons, 1))
            setDinner(fill(persons, 1))
          }
        }
      } catch (err) {
        console.error("Failed to fetch meals for date:", err)
      } finally {
        setLoading(false)
      }
    }

    fetchMealsForDate()
  }, [key, persons.length])

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
  const sum = (c: Counts) => persons.reduce((a, p) => a + (c[p.id] || 0), 0)

  const save = async () => {
    setSaving(true)
    setFeedback(null)

    try {
      const payload = {
        date: key,
        globalLunchType: lunchType.toUpperCase(),
        globalDinnerType: dinnerType.toUpperCase(),
        lunchCounts: lunch,
        dinnerCounts: dinner,
      }

      const res: any = await api.post("/meals/save-day", payload)
      if (res.success) {
        setFeedback({
          type: "success",
          message: res.message || "Meals saved successfully!",
        })
        setIsEditing(true)
      } else {
        setFeedback({
          type: "error",
          message: res.error || "Failed to save meals.",
        })
      }
    } catch (err: any) {
      console.error("Save meals error:", err)
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
      label: "Everyone = 1 Lunch + 1 Dinner",
      run: () => (setLunch(fill(persons, 1)), setDinner(fill(persons, 1))),
      cls: "",
    },
    {
      label: "Lunch Only",
      run: () => (setLunch(fill(persons, 1)), setDinner(fill(persons, 0))),
      cls: "",
    },
    {
      label: "Dinner Only",
      run: () => (setLunch(fill(persons, 0)), setDinner(fill(persons, 1))),
      cls: "",
    },
    {
      label: "❌ Clear All",
      run: () => (setLunch(fill(persons, 0)), setDinner(fill(persons, 0))),
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

      {/* Today's Meal Types */}
      <section className="border border-border bg-card p-4 sm:p-6 shadow-sm">
        <h2 className="mb-4 text-base font-semibold text-foreground">
          Today's Meal Types (same for everyone)
        </h2>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-8">
          <TypePicker
            label="☀️ Lunch Type"
            sub="Select lunch menu"
            subClass="text-amber-400"
            value={lunchType}
            onChange={setLunchType}
          />
          <TypePicker
            label="🌙 Dinner Type"
            sub="Select dinner menu"
            subClass="text-indigo-400"
            value={dinnerType}
            onChange={setDinnerType}
          />
        </div>
      </section>

      {/* Member Meal Counts Table */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        {loading ? (
          <div className="p-8 text-center text-muted-foreground animate-pulse flex items-center justify-center gap-2">
            <Loader2 className="w-5 h-5 animate-spin text-primary" />
            <span>Loading meal entries for {short}...</span>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm min-w-[520px]">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                  <th className="w-14 px-4 py-3 text-left">#</th>
                  <th className="px-4 py-3 text-left text-zinc-300">Member</th>
                  <th className="w-40 sm:w-48 bg-amber-500/10 px-4 py-3 text-center text-amber-400 font-semibold border-l border-border">
                    ☀️ Lunch
                  </th>
                  <th className="w-40 sm:w-48 bg-indigo-500/10 px-4 py-3 text-center text-indigo-400 font-semibold border-l border-border">
                    🌙 Dinner
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
                        value={lunch[p.id] ?? 0}
                        aria-label={`${p.name} lunch`}
                        onChange={(e) =>
                          setLunch({ ...lunch, [p.id]: clamp(e.target.value) })
                        }
                        onFocus={(e) => e.target.select()}
                        className="w-20 border border-amber-500/40 bg-amber-500/10 py-2 min-h-[44px] text-center font-mono text-base font-semibold text-amber-300 outline-none transition-colors focus:border-amber-400 focus:bg-amber-500/20 [color-scheme:dark]"
                      />
                    </td>
                    <td className="px-4 py-2.5 text-center border-l border-border/40">
                      <input
                        type="number"
                        min={0}
                        max={20}
                        value={dinner[p.id] ?? 0}
                        aria-label={`${p.name} dinner`}
                        onChange={(e) =>
                          setDinner({
                            ...dinner,
                            [p.id]: clamp(e.target.value),
                          })
                        }
                        onFocus={(e) => e.target.select()}
                        className="w-20 border border-indigo-400/40 bg-indigo-500/10 py-2 min-h-[44px] text-center font-mono text-base font-semibold text-indigo-300 outline-none transition-colors focus:border-indigo-400 focus:bg-indigo-500/20 [color-scheme:dark]"
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
                    TOTALS:
                  </td>
                  <td className="px-4 py-3 text-center font-mono text-base font-bold text-amber-300 border-l border-border">
                    {sum(lunch)}
                  </td>
                  <td className="px-4 py-3 text-center font-mono text-base font-bold text-indigo-300 border-l border-border">
                    {sum(dinner)}
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
        <span>💾 Save All Meals for {short}</span>
      </button>
    </div>
  )
}
