import type { Request, Response } from "express";
import { apiResponse } from "../../shared/utils/apiResponse";
import { parseDateOnly } from "../../shared/utils/date.util";
import { fineService } from "./fine.service";
import type { FineEntryInput, SaveFinesPayload } from "./fine.types";

export class FineController {
  async getByDate(req: Request, res: Response) {
    const { date } = req.query;
    const targetDateStr =
      typeof date === "string" && date ? date : new Date().toISOString().split("T")[0];

    try {
      const fineDate = parseDateOnly(targetDateStr);
      const data = await fineService.getFinesByDate(fineDate, targetDateStr);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Fine Fetch Error:", error);
      return apiResponse.error(res, "Failed to fetch fines.", 500);
    }
  }

  async saveFines(req: Request, res: Response) {
    const body: SaveFinesPayload = req.body;
    const { date, counts, fineCounts, amounts, reason, fines } = body;

    if (!date || typeof date !== "string") {
      return apiResponse.error(res, "Date is required (YYYY-MM-DD)", 400);
    }

    try {
      const fineDate = parseDateOnly(date);
      const parsedEntries: FineEntryInput[] = [];

      // 1. If payload contains fines array
      if (Array.isArray(fines)) {
        for (const item of fines) {
          const personId = parseInt(String(item.personId), 10);
          const count = parseInt(String(item.count ?? item.fineCount ?? 0), 10);
          const amount = parseFloat(String(item.amount ?? 0));
          if (personId) {
            parsedEntries.push({
              personId,
              count,
              amount: Number.isNaN(amount) ? 0 : amount,
              reason: item.reason || reason || "",
            });
          }
        }
      } else {
        // 2. If payload contains object/map format counts or fineCounts
        const sourceCounts = fineCounts || counts;
        if (sourceCounts) {
          if (Array.isArray(sourceCounts)) {
            for (const item of sourceCounts) {
              const personId = parseInt(String(item.personId), 10);
              const count = parseInt(String(item.count ?? item.fineCount ?? 0), 10);
              const amount = amounts ? parseFloat(String(amounts[personId] ?? 0)) : 0;
              if (personId) {
                parsedEntries.push({
                  personId,
                  count,
                  amount: Number.isNaN(amount) ? 0 : amount,
                  reason: item.reason || reason || "",
                });
              }
            }
          } else if (typeof sourceCounts === "object") {
            for (const [pidStr, countVal] of Object.entries(sourceCounts)) {
              const personId = parseInt(pidStr, 10);
              const count = parseInt(String(countVal ?? 0), 10);
              const amount = amounts ? parseFloat(String(amounts[personId] ?? 0)) : 0;
              if (personId) {
                parsedEntries.push({
                  personId,
                  count,
                  amount: Number.isNaN(amount) ? 0 : amount,
                  reason: reason || "",
                });
              }
            }
          }
        }
      }

      const insertedRowsCount = await fineService.saveDayFines(fineDate, parsedEntries, reason);

      return apiResponse.success(res, undefined, {
        message: `Successfully saved ${insertedRowsCount} fine entries for ${date}!`,
        totalEntries: insertedRowsCount,
      });
    } catch (error) {
      console.error("Fine Save Error:", error);
      return apiResponse.error(res, "Failed to save fines.", 500);
    }
  }

  async getMonthly(req: Request, res: Response) {
    const { month } = req.query;
    const currentMonth = new Date().toISOString().slice(0, 7);
    const targetMonth =
      typeof month === "string" && /^\d{4}-\d{2}$/.test(month) ? month : currentMonth;

    try {
      const data = await fineService.getMonthlyFines(targetMonth);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Fine Monthly Fetch Error:", error);
      return apiResponse.error(res, "Failed to fetch monthly fines.", 500);
    }
  }

  async getAll(req: Request, res: Response) {
    try {
      const limit = req.query.limit ? parseInt(String(req.query.limit), 10) : 100;
      const data = await fineService.getAllFines(limit);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Fine GetAll Error:", error);
      return apiResponse.error(res, "Failed to fetch fines list.", 500);
    }
  }

  async deleteFine(req: Request, res: Response) {
    const idParam = req.params.id;
    const fineId = parseInt(String(idParam), 10);

    if (Number.isNaN(fineId)) {
      return apiResponse.error(res, "Valid Fine ID is required", 400);
    }

    try {
      await fineService.deleteFine(fineId);
      return apiResponse.success(res, undefined, {
        message: "Fine entry deleted successfully.",
      });
    } catch (error) {
      console.error("Fine Delete Error:", error);
      return apiResponse.error(res, "Failed to delete fine entry.", 500);
    }
  }
}

export const fineController = new FineController();
