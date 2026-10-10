"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.mealsService = exports.MealsService = void 0;
const prisma_1 = __importDefault(require("../../shared/db/prisma"));
const meals_types_1 = require("./meals.types");
class MealsService {
  async getMealsByDate(mealDate, targetDateStr) {
    const meals = await prisma_1.default.dailyMeal.findMany({
      where: { mealDate },
      include: { person: true },
      orderBy: { personId: "asc" },
    });
    let lunchType = null;
    let dinnerType = null;
    const lunchCounts = {};
    const dinnerCounts = {};
    for (const m of meals) {
      if (m.session === "LUNCH") {
        lunchCounts[m.personId] = (lunchCounts[m.personId] || 0) + m.mealCount;
        if (!lunchType) lunchType = m.mealType;
      } else if (m.session === "DINNER") {
        dinnerCounts[m.personId] = (dinnerCounts[m.personId] || 0) + m.mealCount;
        if (!dinnerType) dinnerType = m.mealType;
      }
    }
    const hasSavedData = meals.length > 0;
    return {
      date: targetDateStr,
      hasSavedData,
      lunchType: lunchType || "",
      dinnerType: dinnerType || "",
      lunchCounts,
      dinnerCounts,
      meals,
    };
  }
  async saveSessionMeals(mealDate, sessionUpper, meals) {
    return prisma_1.default.$transaction(async (tx) => {
      await tx.dailyMeal.deleteMany({
        where: { mealDate, session: sessionUpper },
      });
      const newMeals = meals
        .filter((m) => parseInt(String(m.mealCount)) > 0)
        .map((m) => ({
          mealDate,
          session: sessionUpper,
          personId: parseInt(String(m.personId)),
          mealType: (0, meals_types_1.normalizeMealType)(m.mealType) || "OTHER",
          mealCount: parseInt(String(m.mealCount)),
        }));
      if (newMeals.length > 0) {
        await tx.dailyMeal.createMany({ data: newMeals });
      }
      return newMeals.length;
    });
  }
  async saveWholeDayMeals(mealDate, lType, dType, parsedLunch, parsedDinner) {
    let insertedRowsCount = 0;
    await prisma_1.default.$transaction(async (tx) => {
      await tx.dailyMeal.deleteMany({
        where: { mealDate },
      });
      const recordsToInsert = [];
      if (lType) {
        for (const item of parsedLunch) {
          recordsToInsert.push({
            mealDate,
            personId: item.personId,
            session: "LUNCH",
            mealType: lType,
            mealCount: item.count,
          });
          insertedRowsCount += item.count;
        }
      }
      if (dType) {
        for (const item of parsedDinner) {
          recordsToInsert.push({
            mealDate,
            personId: item.personId,
            session: "DINNER",
            mealType: dType,
            mealCount: item.count,
          });
          insertedRowsCount += item.count;
        }
      }
      if (recordsToInsert.length > 0) {
        await tx.dailyMeal.createMany({
          data: recordsToInsert,
        });
      }
    });
    return insertedRowsCount;
  }
}
exports.MealsService = MealsService;
exports.mealsService = new MealsService();
