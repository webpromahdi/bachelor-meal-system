"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.summaryService = exports.SummaryService = void 0;
const prisma_1 = __importDefault(require("../../shared/db/prisma"));
const date_util_1 = require("../../shared/utils/date.util");
const summary_calculator_1 = require("./summary.calculator");
const summary_settlement_1 = require("./summary.settlement");
class SummaryService {
  async getMonthlySummary(targetMonth) {
    const { startDate, endDate, lastDayOfMonth } = (0, date_util_1.getMonthDateRange)(targetMonth);
    const [persons, meals, bazarItems] = await Promise.all([
      prisma_1.default.person.findMany({ orderBy: { id: "asc" } }),
      prisma_1.default.dailyMeal.findMany({
        where: { mealDate: { gte: startDate, lte: endDate } },
        include: { person: true },
        orderBy: { mealDate: "asc" },
      }),
      prisma_1.default.bazarItem.findMany({
        where: { bazarDate: { gte: startDate, lte: endDate } },
        include: { paidBy: true },
        orderBy: { bazarDate: "asc" },
      }),
    ]);
    // 1. Bazar Aggregations
    const { totalBazar, categoryBazar, dailyBazarMap } =
      summary_calculator_1.SummaryCalculator.aggregateBazar(bazarItems);
    // 2. Meal Aggregations & Person Stats Tracking
    const { totalMeals, categoryMealsCount, sessionMealTotals, personStats, dailyMealsMap } =
      summary_calculator_1.SummaryCalculator.aggregateMeals(persons, meals, bazarItems);
    // 3. Tab 1: Build Full Month Daily Meal Log with Bazar Breakdown
    const { dailyLogList, dailyLogTotals } =
      summary_calculator_1.SummaryCalculator.generateDailyLog(
        targetMonth,
        lastDayOfMonth,
        dailyMealsMap,
        dailyBazarMap,
      );
    // 4. Category Rates Logic
    const ratesResult = summary_calculator_1.SummaryCalculator.calculateCategoryRates(
      categoryBazar,
      categoryMealsCount,
    );
    // 5. Cost Distribution & Balance Sheet
    const {
      costDistribution,
      costDistributionTotals,
      balanceSheet,
      balanceSheetTotals,
      balancesList,
    } = summary_calculator_1.SummaryCalculator.calculateCostDistributionAndBalances(
      persons,
      personStats,
      ratesResult.rates,
    );
    // 6. Tab 6: Item-wise Bazar Details & Person Grouping
    const bazarDetails = summary_calculator_1.SummaryCalculator.buildBazarDetails(
      bazarItems,
      totalBazar,
    );
    // 7. Settlement Suggestions (Debtors / Creditors + Bilateral pairings)
    const settlementResult = (0, summary_settlement_1.calculateSettlements)(balancesList);
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
exports.SummaryService = SummaryService;
exports.summaryService = new SummaryService();
