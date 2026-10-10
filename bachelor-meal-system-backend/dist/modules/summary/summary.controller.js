"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.summaryController = exports.SummaryController = void 0;
const apiResponse_1 = require("../../shared/utils/apiResponse");
const summary_service_1 = require("./summary.service");
class SummaryController {
  async getMonthly(req, res) {
    const { month } = req.query; // YYYY-MM
    const targetMonth =
      typeof month === "string" && month ? month : new Date().toISOString().slice(0, 7);
    try {
      const [year, monthNum] = targetMonth.split("-").map(Number);
      if (!year || !monthNum || isNaN(year) || isNaN(monthNum)) {
        return apiResponse_1.apiResponse.error(res, "Valid Month is required (YYYY-MM)", 400);
      }
      const summaryData = await summary_service_1.summaryService.getMonthlySummary(targetMonth);
      return apiResponse_1.apiResponse.success(res, summaryData);
    } catch (error) {
      console.error("Summary Calculation Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to calculate summary.", 500);
    }
  }
}
exports.SummaryController = SummaryController;
exports.summaryController = new SummaryController();
