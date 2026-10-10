import prisma from "../../shared/db/prisma";
import {
  type MealCountEntry,
  normalizeMealType,
  type SessionMealInput,
  type ValidMealType,
} from "./meals.types";

export class MealsService {
  async getMealsByDate(mealDate: Date, targetDateStr: string) {
    const meals = await prisma.dailyMeal.findMany({
      where: { mealDate },
      include: { person: true },
      orderBy: { personId: "asc" },
    });

    let lunchType: string | null = null;
    let dinnerType: string | null = null;
    const lunchCounts: Record<number, number> = {};
    const dinnerCounts: Record<number, number> = {};

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

  async saveSessionMeals(
    mealDate: Date,
    sessionUpper: "LUNCH" | "DINNER",
    meals: SessionMealInput[],
  ) {
    return prisma.$transaction(async (tx) => {
      await tx.dailyMeal.deleteMany({
        where: { mealDate, session: sessionUpper },
      });

      const newMeals = meals
        .filter((m) => parseInt(String(m.mealCount)) > 0)
        .map((m) => ({
          mealDate,
          session: sessionUpper,
          personId: parseInt(String(m.personId)),
          mealType: normalizeMealType(m.mealType) || "OTHER",
          mealCount: parseInt(String(m.mealCount)),
        }));

      if (newMeals.length > 0) {
        await tx.dailyMeal.createMany({ data: newMeals });
      }

      return newMeals.length;
    });
  }

  async saveWholeDayMeals(
    mealDate: Date,
    lType: ValidMealType | null,
    dType: ValidMealType | null,
    parsedLunch: MealCountEntry[],
    parsedDinner: MealCountEntry[],
  ) {
    let insertedRowsCount = 0;

    await prisma.$transaction(async (tx) => {
      await tx.dailyMeal.deleteMany({
        where: { mealDate },
      });

      const recordsToInsert: {
        mealDate: Date;
        personId: number;
        session: "LUNCH" | "DINNER";
        mealType: ValidMealType;
        mealCount: number;
      }[] = [];

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

export const mealsService = new MealsService();
