import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  RotateCcw,
  Filter,
  CheckCircle2,
} from "lucide-react"
import { useEffect, useState } from "react"
import { api } from "../lib/api"

const TABS = [
  { id: "log", label: "📋 Daily Log" },
  { id: "matrix", label: "📅 Matrix" },
  { id: "rates", label: "📊 Monthly Summary" },
  { id: "cost", label: "💰 Cost Distribution" },
  { id: "balance", label: "💳 Balance Sheet" },
  { id: "bazar", label: "🛒 Bazar Details" },
]

const AVATAR = [
  "bg-orange-500",
  "bg-green-500",
  "bg-blue-500",
  "bg-pink-500",
  "bg-yellow-500",
  "bg-purple-500",
  "bg-teal-500",
]

const CODE_CLS: Record<string, string> = {
  C: "text-red-400",
  F: "text-blue-400",
  D: "text-yellow-400",
  O: "text-green-400",
  S: "text-pink-400",
}

const CAT_ICON: Record<string, string> = {
  CHICKEN: "🍗",
  FISH: "🐟",
  DIM: "🥚",
  SPECIAL: "⭐",
  OTHER: "🥗",
  RICE: "🍚",
}

const fmtN = (n: number) => Math.round(n).toLocaleString("en-US")
const fmtTk = (n: number) => (n < 0 ? "-" : "") + "৳" + fmtN(Math.abs(n))
const fmtTk2 = (n: number) =>
  (n < 0 ? "-" : "") +
  "৳" +
  Math.abs(n).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
const Dash = () => <span className="text-zinc-500">—</span>

function Code({ c }: { c: { t: string; n: number } | null }) {
  if (!c) return <span className="text-zinc-600">·</span>
  return (
    <span className={CODE_CLS[c.t] || "text-zinc-400"}>
      {c.t}
      {c.n > 1 && <sup className="ml-px text-[9px]">{c.n}</sup>}
    </span>
  )
}

// -------------------------------------------------------------
// TAB 1: Daily Log
// -------------------------------------------------------------
function DailyLog({ log, totals }: { log: any[]; totals?: any }) {
  const cellVal = (v: number) => (v > 0 ? fmtN(v) : "—")

  return (
    <div className="border border-border bg-card shadow-sm overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[960px]">
          <thead>
            <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
              <th className="px-4 py-3 text-left">Date</th>
              <th className="px-3 py-3 text-right">Lunch</th>
              <th className="px-3 py-3 text-right">Dinner</th>
              <th className="px-3 py-3 text-right">Meals Total</th>
              <th className="px-3 py-3 text-right">🍗 Chicken</th>
              <th className="px-3 py-3 text-right">🐟 Fish</th>
              <th className="px-3 py-3 text-right">🥚 Dim</th>
              <th className="px-3 py-3 text-right">⭐ Special</th>
              <th className="px-3 py-3 text-right">🥗 Other</th>
              <th className="px-3 py-3 text-right">🍚 Rice</th>
              <th className="px-4 py-3 text-right text-zinc-300">
                Daily Bazar
              </th>
            </tr>
          </thead>
          <tbody>
            {log.map((d: any, i: number) => (
              <tr
                key={i}
                className={`border-b border-border/60 transition-colors ${
                  d.active
                    ? "hover:bg-accent/30"
                    : "bg-background/20 text-zinc-500"
                }`}
              >
                <td
                  className={`px-4 py-2 font-medium ${
                    d.active ? "text-foreground" : "text-zinc-500"
                  }`}
                >
                  <span className="font-mono">{d.date}</span>
                  <span className="text-xs text-zinc-400 ml-1.5 font-sans">
                    ({d.dayName})
                  </span>
                </td>
                {d.active ? (
                  <>
                    <td className="px-3 py-2 text-right font-mono text-foreground">
                      {d.lunch || 0}
                    </td>
                    <td className="px-3 py-2 text-right font-mono text-foreground">
                      {d.dinner || 0}
                    </td>
                    <td className="px-3 py-2 text-right font-mono font-semibold text-primary">
                      {d.total || 0}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.chicken
                          ? "text-foreground font-medium"
                          : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.chicken)}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.fish ? "text-foreground font-medium" : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.fish)}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.dim ? "text-foreground font-medium" : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.dim)}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.special
                          ? "text-foreground font-medium"
                          : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.special)}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.other
                          ? "text-foreground font-medium"
                          : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.other)}
                    </td>
                    <td
                      className={`px-3 py-2 text-right font-mono ${
                        d.rice ? "text-amber-400 font-medium" : "text-zinc-500"
                      }`}
                    >
                      {cellVal(d.rice)}
                    </td>
                    <td className="px-4 py-2 text-right font-mono font-bold text-primary">
                      {d.dailyBazar > 0 ? fmtTk(d.dailyBazar) : "—"}
                    </td>
                  </>
                ) : (
                  Array.from({ length: 10 }, (_, k) => (
                    <td key={k} className="px-3 py-2 text-right">
                      <Dash />
                    </td>
                  ))
                )}
              </tr>
            ))}
          </tbody>
          {totals && (
            <tfoot>
              <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                <td className="px-4 py-3 text-xs uppercase tracking-wider text-zinc-400">
                  Totals
                </td>
                <td className="px-3 py-3 text-right font-mono text-primary font-bold">
                  {totals.lunch}
                </td>
                <td className="px-3 py-3 text-right font-mono text-primary font-bold">
                  {totals.dinner}
                </td>
                <td className="px-3 py-3 text-right font-mono text-primary font-bold text-base">
                  {totals.totalMeals}
                </td>
                <td className="px-3 py-3 text-right font-mono text-zinc-200">
                  {fmtN(totals.chicken)}
                </td>
                <td className="px-3 py-3 text-right font-mono text-zinc-200">
                  {fmtN(totals.fish)}
                </td>
                <td className="px-3 py-3 text-right font-mono text-zinc-200">
                  {fmtN(totals.dim)}
                </td>
                <td className="px-3 py-3 text-right font-mono text-zinc-200">
                  {fmtN(totals.special)}
                </td>
                <td className="px-3 py-3 text-right font-mono text-zinc-200">
                  {fmtN(totals.other)}
                </td>
                <td className="px-3 py-3 text-right font-mono text-amber-400">
                  {fmtN(totals.rice)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-primary font-bold text-base">
                  {fmtTk(totals.grandBazarTotal)}
                </td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>
    </div>
  )
}

// -------------------------------------------------------------
// TAB 2: Matrix
// -------------------------------------------------------------
function Matrix({ stats, log }: { stats: any[]; log: any[] }) {
  return (
    <div className="border border-border bg-card shadow-sm overflow-hidden">
      <div className="overflow-x-auto">
        <table className="border-separate border-spacing-0 text-xs w-full">
          <thead>
            <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
              <th className="sticky left-0 z-20 w-32 sm:w-40 md:w-44 min-w-32 sm:min-w-40 md:min-w-44 border-r border-b border-border bg-muted px-3 sm:px-4 py-3 text-left">
                Person
              </th>
              {log.map((d) => (
                <th
                  key={d.date}
                  className="min-w-[44px] border-b border-border px-2 py-3 text-center font-mono text-zinc-300"
                >
                  {d.date.slice(-2)}
                </th>
              ))}
              <th className="lg:sticky lg:right-40 z-20 w-16 sm:w-20 min-w-16 sm:min-w-20 border-l border-b border-border bg-muted px-2.5 sm:px-3 py-3 text-right font-semibold text-zinc-200">
                Lunch
              </th>
              <th className="lg:sticky lg:right-20 z-20 w-16 sm:w-20 min-w-16 sm:min-w-20 border-b border-border bg-muted px-2.5 sm:px-3 py-3 text-right font-semibold text-zinc-200">
                Dinner
              </th>
              <th className="lg:sticky lg:right-0 z-20 w-16 sm:w-20 min-w-16 sm:min-w-20 border-b border-border bg-muted px-2.5 sm:px-3 py-3 text-right font-semibold text-primary">
                Total
              </th>
            </tr>
          </thead>
          <tbody>
            {stats.map((s, mi) => (
              <tr
                key={s.name || mi}
                className="group hover:bg-accent/30 transition-colors"
              >
                <td className="sticky left-0 z-10 w-32 sm:w-40 md:w-44 min-w-32 sm:min-w-40 md:min-w-44 border-r border-t border-border bg-card px-3 sm:px-4 py-2 text-sm font-medium text-foreground">
                  <span className="flex items-center gap-2 sm:gap-2.5">
                    <span
                      className={`flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold text-[#09090b] ${AVATAR[mi % AVATAR.length]}`}
                    >
                      {s.name?.[0] || "?"}
                    </span>
                    <span className="truncate">{s.name}</span>
                  </span>
                </td>
                {log.map((d) => {
                  const matrixDay = s.matrix?.[d.date]
                  if (!matrixDay) {
                    return (
                      <td
                        key={d.date}
                        className="border-t border-border/60 px-1 py-2 text-center"
                      >
                        <Dash />
                      </td>
                    )
                  }
                  const l = matrixDay.LUNCH
                    ? { t: matrixDay.LUNCH.type[0], n: matrixDay.LUNCH.count }
                    : null
                  const dn = matrixDay.DINNER
                    ? { t: matrixDay.DINNER.type[0], n: matrixDay.DINNER.count }
                    : null
                  return (
                    <td
                      key={d.date}
                      className="border-t border-border/60 px-1 py-2 text-center font-mono font-semibold"
                    >
                      {l || dn ? (
                        <>
                          <Code c={l} />
                          {l && dn && <span className="text-zinc-500">+</span>}
                          <Code c={dn} />
                        </>
                      ) : (
                        <Dash />
                      )}
                    </td>
                  )
                })}
                <td className="lg:sticky lg:right-40 z-10 w-16 sm:w-20 min-w-16 sm:min-w-20 border-l border-t border-border bg-card px-2.5 sm:px-3 py-2 text-right font-mono font-medium text-foreground">
                  {s.meals?.LUNCH || 0}
                </td>
                <td className="lg:sticky lg:right-20 z-10 w-16 sm:w-20 min-w-16 sm:min-w-20 border-t border-border bg-card px-2.5 sm:px-3 py-2 text-right font-mono font-medium text-foreground">
                  {s.meals?.DINNER || 0}
                </td>
                <td className="lg:sticky lg:right-0 z-10 w-16 sm:w-20 min-w-16 sm:min-w-20 border-t border-border bg-card px-2.5 sm:px-3 py-2 text-right font-mono font-bold text-primary">
                  {s.meals?.total || 0}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

// -------------------------------------------------------------
// TAB 3: Rates & Meal Type Breakdown
// -------------------------------------------------------------
function Rates({
  rates,
  overall,
  effectiveRate,
  mealTypeSummary,
}: {
  rates: any
  overall: number
  effectiveRate?: number
  mealTypeSummary?: any
}) {
  const cats = [
    {
      id: "CHICKEN",
      name: "Chicken",
      icon: "🍗",
      border: "border-l-orange-500",
    },
    { id: "FISH", name: "Fish", icon: "🐟", border: "border-l-sky-500" },
    { id: "DIM", name: "Dim (Egg)", icon: "🥚", border: "border-l-yellow-400" },
    { id: "OTHER", name: "Other", icon: "🥗", border: "border-l-green-500" },
    {
      id: "SPECIAL",
      name: "Special",
      icon: "⭐",
      border: "border-l-fuchsia-500",
    },
    { id: "RICE", name: "Rice", icon: "🍚", border: "border-l-amber-600" },
  ]

  return (
    <div className="space-y-6">
      {/* Category-wise Rates */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">
            Category-wise Rates
          </h2>
          <p className="text-xs text-zinc-400 mt-0.5">
            Cost breakdown and effective per-meal rates for each category
          </p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[560px]">
            <thead>
              <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                <th className="px-5 py-3 text-left">Category</th>
                <th className="px-4 py-3 text-right">Total Bazar Cost</th>
                <th className="px-4 py-3 text-right">Total Meals</th>
                <th className="px-5 py-3 text-right">Rate Per Meal</th>
              </tr>
            </thead>
            <tbody>
              {cats.map((r) => {
                const cost = rates?.totals?.[r.id] || 0
                const meals = rates?.counts?.[r.id] || 0
                const rate = rates?.rates?.[r.id] || 0
                return (
                  <tr
                    key={r.name}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td
                      className={`border-l-4 px-5 py-3 font-medium text-foreground ${r.border}`}
                    >
                      <span className="flex items-center gap-2">
                        <span>{r.icon}</span>
                        <span>{r.name}</span>
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-semibold text-foreground">
                      {fmtTk(cost)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-zinc-300">
                      {meals} <span className="text-zinc-500">meals</span>
                    </td>
                    <td className="px-5 py-3 text-right font-mono font-bold text-primary">
                      ৳{rate.toFixed(2)}
                      <span className="font-normal text-xs text-zinc-400 ml-1">
                        /meal
                      </span>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </section>

      {/* Meal Type Summary Session Breakdown Table (matches summary.php lines 876-928) */}
      {mealTypeSummary && (
        <section className="border border-border bg-card shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border">
            <h2 className="text-base font-semibold text-foreground">
              Meal Type Summary
            </h2>
            <p className="text-xs text-zinc-400 mt-0.5">
              Breakdown of meal sessions (Lunch, Dinner) across distinct meal
              types
            </p>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm min-w-[620px]">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                  <th className="px-5 py-3 text-left">Session</th>
                  <th className="px-4 py-3 text-right">🐟 Fish</th>
                  <th className="px-4 py-3 text-right">🍗 Chicken</th>
                  <th className="px-4 py-3 text-right">🥚 Dim</th>
                  <th className="px-4 py-3 text-right">⭐ Special</th>
                  <th className="px-4 py-3 text-right">🥗 Other</th>
                  <th className="px-5 py-3 text-right text-zinc-300">
                    Total Meals
                  </th>
                </tr>
              </thead>
              <tbody>
                {["LUNCH", "DINNER"].map((sess) => {
                  const sData = mealTypeSummary[sess] || {}
                  return (
                    <tr
                      key={sess}
                      className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                    >
                      <td className="px-5 py-3 font-semibold text-foreground capitalize">
                        {sess.toLowerCase()}
                      </td>
                      <td className="px-4 py-3 text-right font-mono text-zinc-300">
                        {sData.FISH || 0}
                      </td>
                      <td className="px-4 py-3 text-right font-mono text-zinc-300">
                        {sData.CHICKEN || 0}
                      </td>
                      <td className="px-4 py-3 text-right font-mono text-zinc-300">
                        {sData.DIM || 0}
                      </td>
                      <td className="px-4 py-3 text-right font-mono text-zinc-300">
                        {sData.SPECIAL || 0}
                      </td>
                      <td className="px-4 py-3 text-right font-mono text-zinc-300">
                        {sData.OTHER || 0}
                      </td>
                      <td className="px-5 py-3 text-right font-mono font-bold text-primary">
                        {sData.total || 0}
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
                  <td className="px-4 py-3 text-right font-mono text-zinc-200">
                    {mealTypeSummary.TOTAL?.FISH || 0}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-zinc-200">
                    {mealTypeSummary.TOTAL?.CHICKEN || 0}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-zinc-200">
                    {mealTypeSummary.TOTAL?.DIM || 0}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-zinc-200">
                    {mealTypeSummary.TOTAL?.SPECIAL || 0}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-zinc-200">
                    {mealTypeSummary.TOTAL?.OTHER || 0}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-primary text-base">
                    {mealTypeSummary.TOTAL?.grandTotal || 0}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </section>
      )}

      {/* Overall Rate Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div className="border border-border bg-card p-5 sm:p-6 shadow-sm">
          <div className="text-xs font-semibold uppercase tracking-wider text-zinc-400">
            Overall Rate (Regular meal based)
          </div>
          <div className="mt-2 font-mono text-3xl font-bold text-primary">
            ৳{overall.toFixed(2)}
            <span className="text-sm font-normal text-zinc-400 ml-1">
              /meal
            </span>
          </div>
          <p className="mt-1 text-xs text-zinc-400">
            Calculated across shared regular meals (excluding rice)
          </p>
        </div>

        <div className="border border-primary/40 bg-primary/10 p-5 sm:p-6 shadow-sm ring-1 ring-primary/30">
          <div className="text-xs font-semibold uppercase tracking-wider text-primary">
            Effective Overall Rate (with Rice)
          </div>
          <div className="mt-2 font-mono text-3xl font-bold text-foreground">
            ৳{(effectiveRate ?? overall).toFixed(2)}
            <span className="text-sm font-normal text-zinc-400 ml-1">
              /meal
            </span>
          </div>
          <p className="mt-1 text-xs text-zinc-400">
            Official monthly meal rate applied including shared rice cost
          </p>
        </div>
      </div>
    </div>
  )
}

// -------------------------------------------------------------
// TAB 4: Cost Distribution
// -------------------------------------------------------------
function CostDist({ stats, totals }: { stats: any[]; totals?: any }) {
  const cols = [
    {
      key: "CHICKEN",
      label: "🍗 Chicken",
      mealsKey: "chickenMeals",
      costKey: "chickenCost",
    },
    {
      key: "FISH",
      label: "🐟 Fish",
      mealsKey: "fishMeals",
      costKey: "fishCost",
    },
    { key: "DIM", label: "🥚 Dim", mealsKey: "dimMeals", costKey: "dimCost" },
  ]

  return (
    <div className="border border-border bg-card shadow-sm overflow-hidden">
      <div className="px-5 py-4 border-b border-border">
        <h2 className="text-base font-semibold text-foreground">
          Cost Distribution
        </h2>
        <p className="text-xs text-zinc-400 mt-0.5">
          Breakdown of individual member consumption and shared costs
        </p>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[1000px]">
          <thead>
            <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
              <th className="px-4 py-3 text-left">Member</th>
              {cols.map((c) => (
                <th key={c.key} className="px-4 py-3 text-right">
                  {c.label} Meals / Cost
                </th>
              ))}
              <th className="px-4 py-3 text-right">🥗 Other Cost</th>
              <th className="px-4 py-3 text-right">🍚 Rice Cost</th>
              <th className="px-4 py-3 text-right">⭐ Special Cost</th>
              <th className="px-4 py-3 text-right text-zinc-300">Total Cost</th>
            </tr>
          </thead>
          <tbody>
            {stats.map((s) => (
              <tr
                key={s.name}
                className="border-b border-border/60 hover:bg-accent/30 transition-colors"
              >
                <td className="px-4 py-3 font-medium text-foreground">
                  {s.name}
                </td>
                {cols.map((c) => (
                  <td key={c.key} className="px-4 py-3 text-right">
                    <div className="text-xs text-zinc-400 font-medium">
                      {s.meals?.[c.key] || s[c.mealsKey] || 0} meals
                    </div>
                    <div className="font-mono font-medium text-foreground">
                      {fmtTk(s.cost?.[c.key] || s[c.costKey] || 0)}
                    </div>
                  </td>
                ))}
                <td className="px-4 py-3 text-right font-mono font-medium text-foreground">
                  <div className="text-xs text-zinc-400 font-medium">
                    {s.otherMeals || 0} meals
                  </div>
                  {fmtTk(s.cost?.OTHER || s.otherCost || 0)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-[11px]">
                  <div className="text-amber-400 font-medium">
                    Cost: {fmtTk(s.cost?.RICE || s.riceCost || 0)}
                  </div>
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="text-xs text-zinc-400 font-medium">
                    {s.meals?.SPECIAL || s.specialMeals || 0} meals
                  </div>
                  <div className="font-mono font-medium text-foreground">
                    {fmtTk(s.cost?.SPECIAL || s.specialCost || 0)}
                  </div>
                </td>
                <td className="px-4 py-3 text-right font-mono text-lg font-bold text-primary">
                  {fmtTk(s.totalCost)}
                </td>
              </tr>
            ))}
          </tbody>
          {totals && (
            <tfoot>
              <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                <td className="px-4 py-3 text-xs uppercase tracking-wider text-zinc-400">
                  Total
                </td>
                <td className="px-4 py-3 text-right font-mono">
                  <div className="text-xs text-zinc-400">
                    {totals.chickenMeals} meals
                  </div>
                  <div className="text-primary">
                    {fmtTk(totals.chickenCost)}
                  </div>
                </td>
                <td className="px-4 py-3 text-right font-mono">
                  <div className="text-xs text-zinc-400">
                    {totals.fishMeals} meals
                  </div>
                  <div className="text-primary">{fmtTk(totals.fishCost)}</div>
                </td>
                <td className="px-4 py-3 text-right font-mono">
                  <div className="text-xs text-zinc-400">
                    {totals.dimMeals} meals
                  </div>
                  <div className="text-primary">{fmtTk(totals.dimCost)}</div>
                </td>
                <td className="px-4 py-3 text-right font-mono text-primary">
                  {fmtTk(totals.otherCost)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-amber-400">
                  {fmtTk(totals.riceCost)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-primary">
                  {fmtTk(totals.specialCost)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-xl font-bold text-primary">
                  {fmtTk(totals.totalCost)}
                </td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>
    </div>
  )
}

// -------------------------------------------------------------
// TAB 5: Balance Sheet
// -------------------------------------------------------------
function Balance({
  sheet,
  totals,
  settlements,
}: {
  sheet: any[]
  totals?: any
  settlements: string[]
}) {
  const rec = sheet
    .filter((s) => s.balance > 0.01)
    .sort((a, b) => b.balance - a.balance)
  const pay = sheet
    .filter((s) => s.balance < -0.01)
    .sort((a, b) => a.balance - b.balance)

  return (
    <div className="space-y-6">
      {/* Receivable / Payable Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <section className="border border-border bg-card shadow-sm overflow-hidden">
          <div className="bg-emerald-500/15 border-b border-border px-4 py-2.5 font-semibold text-emerald-400 flex items-center justify-between">
            <span>Will Receive 💵</span>
            <span className="text-xs font-mono font-normal">
              {rec.length} {rec.length === 1 ? "member" : "members"}
            </span>
          </div>
          {rec.length === 0 ? (
            <div className="p-4 text-center text-xs text-zinc-400">
              No members to receive
            </div>
          ) : (
            rec.map((s) => (
              <div
                key={s.name}
                className="flex items-center gap-3 border-t border-border/60 px-4 py-3 first:border-t-0 hover:bg-accent/20 transition-colors"
              >
                <span className="text-emerald-400">✅</span>
                <span className="flex-1 font-bold text-foreground">
                  {s.name}
                </span>
                <span className="font-mono text-lg font-bold text-emerald-400">
                  +{fmtTk(s.balance)}
                </span>
              </div>
            ))
          )}
        </section>

        <section className="border border-border bg-card shadow-sm overflow-hidden">
          <div className="bg-rose-500/15 border-b border-border px-4 py-2.5 font-semibold text-rose-400 flex items-center justify-between">
            <span>Will Pay 💸</span>
            <span className="text-xs font-mono font-normal">
              {pay.length} {pay.length === 1 ? "member" : "members"}
            </span>
          </div>
          {pay.length === 0 ? (
            <div className="p-4 text-center text-xs text-zinc-400">
              No members to pay
            </div>
          ) : (
            pay.map((s) => (
              <div
                key={s.name}
                className="flex items-center gap-3 border-t border-border/60 px-4 py-3 first:border-t-0 hover:bg-accent/20 transition-colors"
              >
                <span className="text-rose-400">❌</span>
                <span className="flex-1 font-bold text-foreground">
                  {s.name}
                </span>
                <span className="font-mono text-lg font-bold text-rose-400">
                  {fmtTk(s.balance)}
                </span>
              </div>
            ))
          )}
        </section>
      </div>

      {/* Balance Sheet Table */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">
            Complete Balance Sheet
          </h2>
          <p className="text-xs text-zinc-400 mt-0.5">
            Net calculation: Total Paid (bazar) minus Total Cost Should Pay
          </p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[680px]">
            <thead>
              <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                <th className="px-5 py-3 text-left">Member</th>
                <th className="px-4 py-3 text-right">Total Meals</th>
                <th className="px-4 py-3 text-right">Total Paid (bazar)</th>
                <th className="px-4 py-3 text-right">Should Pay</th>
                <th className="px-5 py-3 text-right text-zinc-300">Balance</th>
                <th className="px-4 py-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              {sheet.map((s) => {
                const isReceive = s.balance > 0.01
                const isPay = s.balance < -0.01
                return (
                  <tr
                    key={s.name}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td className="px-5 py-3 font-medium text-foreground">
                      {s.name}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-foreground">
                      {s.totalMeals}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-medium text-foreground">
                      {fmtTk(s.totalPaid)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-medium text-foreground">
                      {fmtTk(s.shouldPay)}
                    </td>
                    <td
                      className={`px-5 py-3 text-right font-mono font-bold ${
                        isReceive
                          ? "text-emerald-400"
                          : isPay
                            ? "text-rose-400"
                            : "text-zinc-400"
                      }`}
                    >
                      {isReceive ? "+" : ""}
                      {fmtTk(s.balance)}
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span
                        className={`inline-block px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider rounded-none ${
                          isReceive
                            ? "bg-emerald-500/10 text-emerald-400 border border-emerald-500/30"
                            : isPay
                              ? "bg-rose-500/10 text-rose-400 border border-rose-500/30"
                              : "bg-muted text-zinc-400 border border-border"
                        }`}
                      >
                        {isReceive
                          ? "Will Receive"
                          : isPay
                            ? "Will Pay"
                            : "Settled"}
                      </span>
                    </td>
                  </tr>
                )
              })}
            </tbody>
            {totals && (
              <tfoot>
                <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                  <td className="px-5 py-3 text-xs uppercase tracking-wider text-zinc-400">
                    Total
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-primary">
                    {totals.totalMeals}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-primary font-bold">
                    {fmtTk(totals.totalPaid)}
                  </td>
                  <td className="px-4 py-3 text-right font-mono text-primary font-bold">
                    {fmtTk(totals.shouldPay)}
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-primary font-bold text-base">
                    {fmtTk(totals.balance)}
                  </td>
                  <td className="px-4 py-3 text-center text-xs text-zinc-400 font-mono">
                    Balanced
                  </td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </section>

      {/* Settlement Suggestions */}
      <section className="border border-border bg-card shadow-sm p-5 space-y-3">
        <h2 className="text-base font-semibold text-foreground">
          Settlement Suggestions
        </h2>
        {settlements.length === 0 ? (
          <p className="text-zinc-400 text-sm">All balances are settled.</p>
        ) : (
          <div className="space-y-2">
            {settlements.map((s, i) => (
              <div
                key={i}
                className="flex items-center gap-3 border border-border bg-background/40 p-3 text-sm rounded-none"
              >
                <span className="text-primary font-mono font-bold bg-primary/10 border border-primary/30 px-2.5 py-0.5 text-xs">
                  PAY
                </span>
                <span className="font-semibold text-foreground">{s}</span>
              </div>
            ))}
          </div>
        )}
      </section>
    </div>
  )
}

// -------------------------------------------------------------
// TAB 6: Bazar Details & Itemized List (matches summary.php lines 1377-1470)
// -------------------------------------------------------------
function BazarDetails({
  stats,
  rates,
  totalBazar,
  bazarDetails,
}: {
  stats: any[]
  rates: any
  totalBazar: number
  bazarDetails?: any
}) {
  const [filterPersonId, setFilterPersonId] = useState<string>("all")

  const cats = [
    {
      id: "CHICKEN",
      name: "Chicken",
      icon: "🍗",
      border: "border-l-orange-500",
    },
    { id: "FISH", name: "Fish", icon: "🐟", border: "border-l-sky-500" },
    { id: "DIM", name: "Dim (Egg)", icon: "🥚", border: "border-l-yellow-400" },
    { id: "OTHER", name: "Other", icon: "🥗", border: "border-l-green-500" },
    {
      id: "SPECIAL",
      name: "Special",
      icon: "⭐",
      border: "border-l-fuchsia-500",
    },
    { id: "RICE", name: "Rice", icon: "🍚", border: "border-l-amber-600" },
  ]

  // Filter items
  const allItems: any[] = bazarDetails?.items || []
  const filteredItems =
    filterPersonId === "all"
      ? allItems
      : allItems.filter((i) => String(i.paidById) === filterPersonId)

  const filterTotal = filteredItems.reduce((acc, i) => acc + (i.amount || 0), 0)

  return (
    <div className="space-y-6">
      {/* Category-wise Bazar Breakdown */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">
            Category-wise Bazar Breakdown
          </h2>
          <p className="text-xs text-zinc-400 mt-0.5">
            Total expenses recorded per category for this month
          </p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[560px]">
            <thead>
              <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                <th className="px-4 py-3 text-left">Category</th>
                <th className="px-4 py-3 text-right">Cost (BDT)</th>
                <th className="px-4 py-3 text-right">Share of Budget</th>
              </tr>
            </thead>
            <tbody>
              {cats.map((c) => {
                const cost = rates?.totals?.[c.id] || 0
                const pct =
                  totalBazar > 0 ? ((cost / totalBazar) * 100).toFixed(1) : "0"
                return (
                  <tr
                    key={c.id}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td
                      className={`border-l-4 px-4 py-3 font-medium text-foreground ${c.border}`}
                    >
                      <span className="flex items-center gap-2">
                        <span>{c.icon}</span>
                        <span>{c.name}</span>
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-semibold text-foreground">
                      {fmtTk(cost)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-zinc-400">
                      {pct}%
                    </td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                <td className="px-4 py-3 text-xs uppercase tracking-wider text-zinc-400">
                  Total Bazar
                </td>
                <td className="px-4 py-3 text-right font-mono text-primary text-base">
                  {fmtTk(totalBazar)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-zinc-300">
                  100%
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      {/* Member-wise Bazar Contributions */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">
            Member-wise Bazar Contributions
          </h2>
          <p className="text-xs text-zinc-400 mt-0.5">
            Total amount paid by each member for bazar purchases
          </p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm min-w-[560px]">
            <thead>
              <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                <th className="px-4 py-3 text-left">Member</th>
                <th className="px-4 py-3 text-right">Total Paid (bazar)</th>
                <th className="px-4 py-3 text-right">Rice Paid</th>
                <th className="px-4 py-3 text-right">Contribution Share</th>
              </tr>
            </thead>
            <tbody>
              {stats.map((s) => {
                const paid = s.totalPaid || 0
                const ricePaid = s.ricePaid || 0
                const pct =
                  totalBazar > 0 ? ((paid / totalBazar) * 100).toFixed(1) : "0"
                return (
                  <tr
                    key={s.name}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td className="px-4 py-3 font-medium text-foreground">
                      {s.name}
                    </td>
                    <td className="px-4 py-3 text-right font-mono font-semibold text-primary">
                      {fmtTk(paid)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-emerald-400">
                      {fmtTk(ricePaid)}
                    </td>
                    <td className="px-4 py-3 text-right font-mono text-zinc-400">
                      {pct}%
                    </td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                <td className="px-4 py-3 text-xs uppercase tracking-wider text-zinc-400">
                  Total
                </td>
                <td className="px-4 py-3 text-right font-mono text-primary text-base">
                  {fmtTk(totalBazar)}
                </td>
                <td className="px-4 py-3 text-right font-mono text-emerald-400">
                  {fmtTk(stats.reduce((a, s) => a + (s.ricePaid || 0), 0))}
                </td>
                <td className="px-4 py-3 text-right font-mono text-zinc-300">
                  100%
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      {/* Item-wise Bazar Purchase Log with Filter (matches summary.php lines 1377-1470) */}
      <section className="border border-border bg-card shadow-sm overflow-hidden">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-border">
          <div>
            <h2 className="text-base font-semibold text-foreground">
              Itemized Bazar Purchase Log
            </h2>
            <p className="text-xs text-zinc-400 mt-0.5">
              Individual category items purchased throughout the month
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-zinc-400 shrink-0" />
            <select
              value={filterPersonId}
              onChange={(e) => setFilterPersonId(e.target.value)}
              className="border border-border bg-card/80 px-3 py-2 min-h-[44px] text-base sm:text-xs font-semibold text-foreground outline-none transition-colors hover:border-zinc-500 focus:border-primary [color-scheme:dark]"
            >
              <option value="all">All Members ({allItems.length} items)</option>
              {stats.map((s) => (
                <option key={s.id || s.name} value={String(s.id)}>
                  {s.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Filter Stats */}
        {bazarDetails?.stats && (
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 border-b border-border bg-muted/20 p-4 text-center">
            <div>
              <div className="text-[11px] uppercase font-semibold text-zinc-400">
                Total Entries
              </div>
              <div className="font-mono text-base font-bold text-foreground mt-0.5">
                {filterPersonId === "all"
                  ? bazarDetails.stats.totalEntries
                  : filteredItems.length}
              </div>
            </div>
            <div>
              <div className="text-[11px] uppercase font-semibold text-zinc-400">
                Contributors
              </div>
              <div className="font-mono text-base font-bold text-foreground mt-0.5">
                {filterPersonId === "all"
                  ? bazarDetails.stats.contributorsCount
                  : 1}
              </div>
            </div>
            <div>
              <div className="text-[11px] uppercase font-semibold text-zinc-400">
                Days with Bazar
              </div>
              <div className="font-mono text-base font-bold text-foreground mt-0.5">
                {bazarDetails.stats.daysWithBazar}
              </div>
            </div>
            <div>
              <div className="text-[11px] uppercase font-semibold text-zinc-400">
                Filtered Amount
              </div>
              <div className="font-mono text-base font-bold text-primary mt-0.5">
                {fmtTk(filterTotal)}
              </div>
            </div>
          </div>
        )}

        <div className="overflow-x-auto">
          {filteredItems.length === 0 ? (
            <div className="p-8 text-center text-xs text-zinc-400">
              No bazar entries found for the selected filter.
            </div>
          ) : (
            <table className="w-full text-sm min-w-[560px]">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-xs uppercase tracking-wider text-zinc-400 font-semibold">
                  <th className="px-5 py-3 text-left">Date</th>
                  <th className="px-4 py-3 text-left">Item / Category</th>
                  <th className="px-4 py-3 text-left">Paid By</th>
                  <th className="px-5 py-3 text-right">Amount (BDT)</th>
                </tr>
              </thead>
              <tbody>
                {filteredItems.map((item: any, idx: number) => (
                  <tr
                    key={item.id || idx}
                    className="border-b border-border/60 hover:bg-accent/30 transition-colors"
                  >
                    <td className="px-5 py-2.5 font-mono text-zinc-300">
                      {item.date}
                    </td>
                    <td className="px-4 py-2.5 font-medium text-foreground">
                      <span className="flex items-center gap-1.5 capitalize">
                        <span>
                          {CAT_ICON[item.category?.toUpperCase()] || "🛒"}
                        </span>
                        <span>{item.itemName}</span>
                      </span>
                    </td>
                    <td className="px-4 py-2.5 text-zinc-300">
                      {item.personName}
                    </td>
                    <td className="px-5 py-2.5 text-right font-mono font-bold text-primary">
                      {fmtTk2(item.amount)}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="border-t-2 border-border bg-muted/40 font-bold text-foreground">
                  <td
                    colSpan={3}
                    className="px-5 py-3 text-xs uppercase tracking-wider text-zinc-400"
                  >
                    Total Filtered Purchases
                  </td>
                  <td className="px-5 py-3 text-right font-mono text-primary text-base">
                    {fmtTk2(filterTotal)}
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

// -------------------------------------------------------------
// MAIN PAGE COMPONENT
// -------------------------------------------------------------
export default function Summary() {
  const [tab, setTab] = useState("log")

  // Dynamic Month Selection: Defaults to October 2026 or current year-month
  const [month, setMonth] = useState<string>("2026-10")
  const [data, setData] = useState<any>(null)
  const [loading, setLoading] = useState(true)

  // Month navigation helpers
  const shiftMonth = (delta: number) => {
    const [yStr, mStr] = month.split("-")
    const y = parseInt(yStr, 10)
    const m = parseInt(mStr, 10)
    if (!isNaN(y) && !isNaN(m)) {
      const d = new Date(y, m - 1 + delta, 1)
      const nextMonthStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`
      setMonth(nextMonthStr)
    }
  }

  const getMonthLabel = (mStr: string) => {
    const [yStr, mMonthStr] = mStr.split("-")
    const y = parseInt(yStr, 10)
    const m = parseInt(mMonthStr, 10)
    if (isNaN(y) || isNaN(m)) return mStr
    const d = new Date(y, m - 1, 1)
    return d.toLocaleDateString("en-US", { month: "long", year: "numeric" })
  }

  useEffect(() => {
    const fetchSummary = async () => {
      setLoading(true)
      try {
        const res: any = await api.get(`/summary/monthly?month=${month}`)
        if (res.success && res.data) {
          setData(res.data)
        } else {
          setData(null)
        }
      } catch (e) {
        console.error("Summary fetch error", e)
        setData(null)
      } finally {
        setLoading(false)
      }
    }
    fetchSummary()
  }, [month])

  return (
    <div className="p-3.5 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-5 sm:space-y-6">
      {/* Month Navigation Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-border bg-card px-3.5 py-3 sm:px-4 sm:py-3.5 shadow-sm">
        <div className="flex flex-wrap items-center gap-2 sm:gap-3">
          <button
            type="button"
            onClick={() => shiftMonth(-1)}
            className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1.5"
            aria-label="Previous month"
          >
            <ChevronLeft className="w-4 h-4 text-zinc-300" />
            <span className="hidden sm:inline">Prev</span>
          </button>

          <div className="flex items-center gap-2 px-3 py-2 min-h-[44px] border border-border bg-background/60">
            <Calendar className="w-4 h-4 text-primary shrink-0" />
            <span className="text-base sm:text-lg font-bold text-foreground">
              {getMonthLabel(month)}
            </span>
          </div>

          <button
            type="button"
            onClick={() => shiftMonth(1)}
            className="cursor-pointer border border-border bg-card/80 px-3 py-2 min-h-[44px] text-sm font-medium text-foreground transition-all hover:bg-accent hover:text-accent-foreground active:scale-95 flex items-center gap-1.5"
            aria-label="Next month"
          >
            <span className="hidden sm:inline">Next</span>
            <ChevronRight className="w-4 h-4 text-zinc-300" />
          </button>

          <input
            type="month"
            value={month}
            onChange={(e) => e.target.value && setMonth(e.target.value)}
            className="border border-border bg-card/80 px-3 py-2 min-h-[44px] text-base sm:text-xs font-semibold text-foreground outline-none [color-scheme:dark]"
            aria-label="Pick month directly"
          />
        </div>

        {month !== "2026-10" && (
          <button
            type="button"
            onClick={() => setMonth("2026-10")}
            className="w-full sm:w-auto cursor-pointer border border-border bg-card/50 px-3 py-2 min-h-[44px] text-xs font-medium text-zinc-400 hover:text-foreground hover:bg-accent transition-colors flex items-center justify-center gap-1.5"
          >
            <RotateCcw className="w-3.5 h-3.5" />
            <span>Reset to October 2026</span>
          </button>
        )}
      </div>

      {loading ? (
        <div className="border border-border bg-card p-12 text-center text-zinc-400 animate-pulse font-medium shadow-sm">
          Loading monthly summary data...
        </div>
      ) : !data || !data.dailyLog || data.dailyLog.length === 0 ? (
        <div className="border border-dashed border-border bg-card p-12 text-sm text-zinc-400 text-center shadow-sm">
          <p>No entries recorded for {getMonthLabel(month)}.</p>
          <button
            type="button"
            className="cursor-pointer text-primary underline mt-3 font-semibold hover:text-primary/80 transition-colors"
            onClick={() => setMonth("2026-10")}
          >
            Reset to October 2026
          </button>
        </div>
      ) : (
        <>
          {/* Top KPI Metric Cards Grid with Odd-Item Rule on Mobile */}
          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 sm:gap-3">
            {[
              {
                v: data.stats.totalMeals,
                l: "Total Meals",
                cls: "text-foreground",
              },
              {
                v: fmtTk(data.stats.totalBazar),
                l: "Total Bazar",
                cls: "text-primary",
              },
              {
                v: `৳${(data.stats.effectiveOverallRate || data.stats.overallRate || 0).toFixed(2)}`,
                l: "Meal Rate",
                cls: "text-primary",
                box: true,
              },
              {
                v: data.stats.membersCount,
                l: "Members",
                cls: "text-foreground",
              },
              {
                v: data.stats.activeDays,
                l: "Active Days",
                cls: "text-foreground",
              },
              {
                v: data.stats.specialMeals,
                l: "Special Meals",
                cls: "text-foreground",
              },
              {
                v: fmtTk(data.stats.totalRice),
                l: "Total Rice",
                cls: "text-amber-400",
              },
            ].map((b, idx) => (
              <div
                key={b.l}
                className={`border bg-card p-3 sm:p-4 text-center shadow-sm ${
                  idx === 6 ? "col-span-2 sm:col-span-1 lg:col-span-1" : ""
                } ${
                  b.box
                    ? "border-primary/50 bg-primary/10 ring-1 ring-primary/30"
                    : "border-border"
                }`}
              >
                <div
                  className={`font-mono text-xl sm:text-2xl font-bold tracking-tight ${b.cls}`}
                >
                  {b.v}
                </div>
                <div className="mt-1 text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-zinc-400">
                  {b.l}
                </div>
              </div>
            ))}
          </div>

          {/* Navigation Tabs */}
          <div
            role="tablist"
            className="flex border-b border-border overflow-x-auto gap-1 pb-px"
          >
            {TABS.map((t) => (
              <button
                key={t.id}
                role="tab"
                aria-selected={tab === t.id}
                onClick={() => setTab(t.id)}
                className={`-mb-px cursor-pointer border-b-2 px-3.5 sm:px-5 py-2.5 sm:py-3 min-h-[44px] text-xs sm:text-sm font-semibold transition-colors whitespace-nowrap flex items-center justify-center ${
                  tab === t.id
                    ? "border-primary bg-primary/10 text-foreground"
                    : "border-transparent text-zinc-400 hover:text-foreground hover:bg-accent/40"
                }`}
              >
                {t.label}
              </button>
            ))}
          </div>

          {/* Tab Content Display */}
          <div className="pt-2">
            {tab === "log" && (
              <DailyLog log={data.dailyLog} totals={data.dailyLogTotals} />
            )}
            {tab === "matrix" && (
              <Matrix
                stats={Object.values(data.matrix || {})}
                log={data.dailyLog}
              />
            )}
            {tab === "rates" && (
              <Rates
                rates={data.categoryRates}
                overall={data.stats.overallRate || 0}
                effectiveRate={data.stats.effectiveOverallRate}
                mealTypeSummary={data.mealTypeSummary}
              />
            )}
            {tab === "cost" && (
              <CostDist
                stats={data.costDistribution || []}
                totals={data.costDistributionTotals}
              />
            )}
            {tab === "balance" && (
              <Balance
                sheet={data.balanceSheet || []}
                totals={data.balanceSheetTotals}
                settlements={data.settlementSuggestions || []}
              />
            )}
            {tab === "bazar" && (
              <BazarDetails
                stats={data.balanceSheet || []}
                rates={data.categoryRates}
                totalBazar={data.stats.totalBazar}
                bazarDetails={data.bazarDetails}
              />
            )}
          </div>
        </>
      )}
    </div>
  )
}
