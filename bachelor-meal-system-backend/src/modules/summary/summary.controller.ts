import type { Request, Response } from "express";
import { apiResponse } from "../../shared/utils/apiResponse";
import { summaryService } from "./summary.service";

export class SummaryController {
  async getMonthly(req: Request, res: Response) {
    const { month } = req.query; // YYYY-MM
    const targetMonth =
      typeof month === "string" && month ? month : new Date().toISOString().slice(0, 7);

    try {
      const [year, monthNum] = targetMonth.split("-").map(Number);
      if (!year || !monthNum || isNaN(year) || isNaN(monthNum)) {
        return apiResponse.error(res, "Valid Month is required (YYYY-MM)", 400);
      }

      const summaryData = await summaryService.getMonthlySummary(targetMonth);
      return apiResponse.success(res, summaryData);
    } catch (error) {
      console.error("Summary Calculation Error:", error);
      return apiResponse.error(res, "Failed to calculate summary.", 500);
    }
  }
}

export const summaryController = new SummaryController();
