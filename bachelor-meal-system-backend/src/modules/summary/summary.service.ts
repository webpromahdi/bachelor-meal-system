import prisma from "../../shared/db/prisma";
import { getMonthDateRange } from "../../shared/utils/date.util";
import { SummaryCalculator } from "./summary.calculator";
import { calculateSettlements } from "./summary.settlement";

export class SummaryService {
  async getMonthlySummary(targetMonth: string) {
    const { startDate, endDate, lastDayOfMonth } = getMonthDateRange(targetMonth);

    const [persons, meals, bazarItems] = await Promise.all([
      prisma.person.findMany({ orderBy: { id: "asc" } }),
      prisma.dailyMeal.findMany({
        where: { mealDate: { gte: startDate, lte: endDate } },
        include: { person: true },
        orderBy: { mealDate: "asc" },
      }),
      prisma.bazarItem.findMany({
        where: { bazarDate: { gte: startDate, lte: endDate } },
        include: { paidBy: true },
        orderBy: { bazarDate: "asc" },
      }),
    ]);

    // 1. Bazar Aggregations
    const { totalBazar, categoryBazar, dailyBazarMap } =
      SummaryCalculator.aggregateBazar(bazarItems);

    // 2. Meal Aggregations & Person Stats Tracking
    const { totalMeals, categoryMealsCount, sessionMealTotals, personStats, dailyMealsMap } =
      SummaryCalculator.aggregateMeals(persons, meals, bazarItems);

    // 3. Tab 1: Build Full Month Daily Meal Log with Bazar Breakdown
    const { dailyLogList, dailyLogTotals } = SummaryCalculator.generateDailyLog(
      targetMonth,
      lastDayOfMonth,
      dailyMealsMap,
      dailyBazarMap,
    );

    // 4. Category Rates Logic
    const ratesResult = SummaryCalculator.calculateCategoryRates(categoryBazar, categoryMealsCount);

    // 5. Cost Distribution & Balance Sheet
    const {
      costDistribution,
      costDistributionTotals,
      balanceSheet,
      balanceSheetTotals,
      balancesList,
    } = SummaryCalculator.calculateCostDistributionAndBalances(
      persons,
      personStats,
      ratesResult.rates,
    );

    // 6. Tab 6: Item-wise Bazar Details & Person Grouping
    const bazarDetails = SummaryCalculator.buildBazarDetails(bazarItems, totalBazar);

    // 7. Settlement Suggestions (Debtors / Creditors + Bilateral pairings)
    const settlementResult = calculateSettlements(balancesList);

    return {
      month: targetMonth,
      stats: {
        totalMeals,
        totalBazar,
        overallRate: ratesResult.overallRate,
        riceMealRate: ratesResult.riceMealRate,
        effectiveOverallRate: ratesResult.effectiveOverallRate,
        membersCount: persons.length,
        activeDays: Object.keys(dailyMealsMap).length,
        specialMeals: ratesResult.specialMeals,
        totalRice: categoryBazar.RICE,
      },
      dailyLog: dailyLogList,
      dailyLogTotals,
      matrix: personStats,
      mealTypeSummary: sessionMealTotals,
      categoryRates: {
        totals: categoryBazar,
        counts: ratesResult.counts,
        rates: ratesResult.rates,
      },
      costDistribution,
      costDistributionTotals,
      balanceSheet,
      balanceSheetTotals,
      bazarDetails,
      settlements: {
        debtors: settlementResult.debtors,
        creditors: settlementResult.creditors,
        suggestions: settlementResult.suggestions,
      },
      settlementSuggestions: settlementResult.suggestions,
    };
  }
}

export const summaryService = new SummaryService();
