"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.utilitiesController = exports.UtilitiesController = void 0;
const apiResponse_1 = require("../../shared/utils/apiResponse");
const utilities_service_1 = require("./utilities.service");
class UtilitiesController {
  async getAll(req, res) {
    try {
      const data = await utilities_service_1.utilitiesService.getAll();
      return apiResponse_1.apiResponse.success(res, data);
    } catch (error) {
      console.error("Utilities Fetch Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to fetch utilities.", 500);
    }
  }
  async updateExpenses(req, res) {
    const { utilities, personId, wifi, current, gas, other } = req.body;
    try {
      // 1. Bulk update: utilities array or map
      if (utilities) {
        const entries = [];
        if (Array.isArray(utilities)) {
          for (const u of utilities) {
            const pid = parseInt(u.personId || u.id);
            if (pid) {
              entries.push({
                personId: pid,
                wifi: parseFloat(u.wifi || 0),
                current: parseFloat(u.current || 0),
                gas: parseFloat(u.gas || 0),
                other: parseFloat(u.other || 0),
              });
            }
          }
        } else if (typeof utilities === "object") {
          for (const [pidStr, data] of Object.entries(utilities)) {
            const pid = parseInt(pidStr);
            const d = data;
            if (pid) {
              entries.push({
                personId: pid,
                wifi: parseFloat(d.wifi || 0),
                current: parseFloat(d.current || 0),
                gas: parseFloat(d.gas || 0),
                other: parseFloat(d.other || 0),
              });
            }
          }
        }
        await utilities_service_1.utilitiesService.updateExpenses(entries);
        return apiResponse_1.apiResponse.success(res, undefined, {
          message: "Utility data updated successfully.",
        });
      }
      // 2. Single person update
      if (!personId) {
        return apiResponse_1.apiResponse.error(res, "Missing personId or utilities payload.", 400);
      }
      const pid = parseInt(personId);
      await utilities_service_1.utilitiesService.updateExpenses([
        {
          personId: pid,
          wifi: parseFloat(wifi || 0),
          current: parseFloat(current || 0),
          gas: parseFloat(gas || 0),
          other: parseFloat(other || 0),
        },
      ]);
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: "Utility data updated successfully.",
      });
    } catch (error) {
      console.error("Utility Expenses Update Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to update utility data.", 500);
    }
  }
  async updateFund(req, res) {
    const { fund, personId, collected } = req.body;
    try {
      // 1. Bulk update: fund array or map
      if (fund) {
        const entries = [];
        if (Array.isArray(fund)) {
          for (const f of fund) {
            const pid = parseInt(f.personId || f.id);
            if (pid) {
              entries.push({
                personId: pid,
                collected: parseFloat(f.collected || 0),
              });
            }
          }
        } else if (typeof fund === "object") {
          for (const [pidStr, data] of Object.entries(fund)) {
            const pid = parseInt(pidStr);
            const val = typeof data === "object" && data !== null ? data.collected : data;
            if (pid) {
              entries.push({
                personId: pid,
                collected: parseFloat(val || 0),
              });
            }
          }
        }
        await utilities_service_1.utilitiesService.updateFund(entries);
        return apiResponse_1.apiResponse.success(res, undefined, {
          message: "Fund data updated successfully.",
        });
      }
      // 2. Single person update
      if (!personId) {
        return apiResponse_1.apiResponse.error(res, "Missing personId or fund payload.", 400);
      }
      const pid = parseInt(personId);
      await utilities_service_1.utilitiesService.updateFund([
        {
          personId: pid,
          collected: parseFloat(collected || 0),
        },
      ]);
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: "Fund data updated successfully.",
      });
    } catch (error) {
      console.error("Fund Update Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to update fund.", 500);
    }
  }
  async resetExpenses(req, res) {
    try {
      await utilities_service_1.utilitiesService.resetExpenses();
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: "Utility data reset successfully.",
      });
    } catch (error) {
      console.error("Utility Reset Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to reset utility data.", 500);
    }
  }
  async updateShared(req, res) {
    const { type, amount, lastReset, start_date } = req.body;
    if (!type || !["current", "gas"].includes(type.toLowerCase())) {
      return apiResponse_1.apiResponse.error(res, "Invalid shared utility type.", 400);
    }
    const normalizedType = type.toLowerCase();
    const targetDateStr = start_date || lastReset;
    try {
      let targetDate;
      if (targetDateStr) {
        targetDate = new Date(
          targetDateStr.includes("T") ? targetDateStr : `${targetDateStr}T00:00:00.000Z`,
        );
      }
      await utilities_service_1.utilitiesService.updateShared(
        normalizedType,
        parseFloat(amount || 0),
        targetDate,
      );
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: `${normalizedType.toUpperCase()} data updated successfully.`,
      });
    } catch (error) {
      console.error("Shared Utility Update Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to update shared utility.", 500);
    }
  }
  async resetShared(req, res) {
    const { type } = req.body;
    if (!type || !["current", "gas"].includes(type.toLowerCase())) {
      return apiResponse_1.apiResponse.error(res, "Invalid shared utility type.", 400);
    }
    const normalizedType = type.toLowerCase();
    try {
      await utilities_service_1.utilitiesService.resetShared(normalizedType);
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: `${normalizedType.toUpperCase()} data reset successfully.`,
      });
    } catch (error) {
      console.error("Shared Utility Reset Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to reset shared utility.", 500);
    }
  }
  async resetCycle(req, res) {
    try {
      await utilities_service_1.utilitiesService.resetFundAndExpenses();
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: "Fund reset for new cycle. All utility expenses also cleared.",
      });
    } catch (error) {
      console.error("Cycle Reset Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to reset for new cycle.", 500);
    }
  }
}
exports.UtilitiesController = UtilitiesController;
exports.utilitiesController = new UtilitiesController();
