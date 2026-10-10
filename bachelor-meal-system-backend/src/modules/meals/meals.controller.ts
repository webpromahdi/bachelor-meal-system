import type { Request, Response } from "express";
import { apiResponse } from "../../shared/utils/apiResponse";
import { parseDateOnly } from "../../shared/utils/date.util";
import { mealsService } from "./meals.service";
import { type MealCountEntry, normalizeMealType, type SaveMealsPayload } from "./meals.types";

export class MealsController {
  async getByDate(req: Request, res: Response) {
    const { date } = req.query;
    const targetDateStr =
      typeof date === "string" && date ? date : new Date().toISOString().split("T")[0];

    try {
      const mealDate = parseDateOnly(targetDateStr);
      const data = await mealsService.getMealsByDate(mealDate, targetDateStr);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Meals Fetch Error:", error);
      return apiResponse.error(res, "Failed to fetch meals.", 500);
    }
  }

  async saveMeals(req: Request, res: Response) {
    const body: SaveMealsPayload = req.body;
    const {
      date,
      globalLunchType,
      globalDinnerType,
      lunchType,
      dinnerType,
      lunchCounts,
      dinnerCounts,
      session,
      meals,
    } = body;

    if (!date || typeof date !== "string") {
      return apiResponse.error(res, "Date is required (YYYY-MM-DD)", 400);
    }

    const mealDate = parseDateOnly(date);

    // 1. If payload is in single-session format ({ session, meals })
    if (session && Array.isArray(meals)) {
      const sessionUpper: "LUNCH" | "DINNER" =
        session.toUpperCase() === "LUNCH" ? "LUNCH" : "DINNER";

      try {
        await mealsService.saveSessionMeals(mealDate, sessionUpper, meals);
        return apiResponse.success(res, undefined, {
          message: `Meals for ${sessionUpper} updated successfully.`,
        });
      } catch (error) {
        console.error("Session Meal Upsert Error:", error);
        return apiResponse.error(res, "Failed to update meals.", 500);
      }
    }

    // 2. Unified whole-day format (matches meals.php)
    const lType = normalizeMealType(globalLunchType || lunchType);
    const dType = normalizeMealType(globalDinnerType || dinnerType);

    // Parse lunch counts object or array
    const parsedLunch: MealCountEntry[] = [];
    if (lunchCounts) {
      if (Array.isArray(lunchCounts)) {
        for (const item of lunchCounts) {
          const count = parseInt(item.count || item.mealCount || 0);
          const personId = parseInt(item.personId || item.id);
          if (personId && count > 0) parsedLunch.push({ personId, count });
        }
      } else if (typeof lunchCounts === "object") {
        for (const [pidStr, countVal] of Object.entries(lunchCounts)) {
          const count = parseInt((countVal as string) || "0");
          const personId = parseInt(pidStr);
          if (personId && count > 0) parsedLunch.push({ personId, count });
        }
      }
    }

    // Parse dinner counts object or array
    const parsedDinner: MealCountEntry[] = [];
    if (dinnerCounts) {
      if (Array.isArray(dinnerCounts)) {
        for (const item of dinnerCounts) {
          const count = parseInt(item.count || item.mealCount || 0);
          const personId = parseInt(item.personId || item.id);
          if (personId && count > 0) parsedDinner.push({ personId, count });
        }
      } else if (typeof dinnerCounts === "object") {
        for (const [pidStr, countVal] of Object.entries(dinnerCounts)) {
          const count = parseInt((countVal as string) || "0");
          const personId = parseInt(pidStr);
          if (personId && count > 0) parsedDinner.push({ personId, count });
        }
      }
    }

    // Exact validation rules:
    const validationErrors: string[] = [];
    if (parsedLunch.length > 0 && !lType) {
      validationErrors.push("Please select Lunch Meal Type (you have lunch entries)");
    }
    if (parsedDinner.length > 0 && !dType) {
      validationErrors.push("Please select Dinner Meal Type (you have dinner entries)");
    }

    if (validationErrors.length > 0) {
      return apiResponse.error(res, validationErrors.join("; "), 400, {
        errors: validationErrors,
      });
    }

    try {
      const insertedRowsCount = await mealsService.saveWholeDayMeals(
        mealDate,
        lType,
        dType,
        parsedLunch,
        parsedDinner,
      );

      return apiResponse.success(res, undefined, {
        message: `Successfully saved ${insertedRowsCount} meal entries for ${date}!`,
        totalEntries: insertedRowsCount,
      });
    } catch (error) {
      console.error("Unified Meals Save Error:", error);
      return apiResponse.error(res, "Failed to save meals.", 500);
    }
  }
}

export const mealsController = new MealsController();
