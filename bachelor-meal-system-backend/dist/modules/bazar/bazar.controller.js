"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.bazarController = exports.BazarController = void 0;
const apiResponse_1 = require("../../shared/utils/apiResponse");
const date_util_1 = require("../../shared/utils/date.util");
const bazar_service_1 = require("./bazar.service");
const bazar_types_1 = require("./bazar.types");
class BazarController {
  async getByDate(req, res) {
    const { date } = req.query;
    const targetDateStr =
      typeof date === "string" && date ? date : new Date().toISOString().split("T")[0];
    try {
      const bazarDate = (0, date_util_1.parseDateOnly)(targetDateStr);
      const data = await bazar_service_1.bazarService.getByDate(bazarDate, targetDateStr);
      return apiResponse_1.apiResponse.success(res, data);
    } catch (error) {
      console.error("Bazar Fetch Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to fetch bazar items.", 500);
    }
  }
  async getByPerson(req, res) {
    const { date, personId, paid_by } = req.query;
    const pidStr = personId || paid_by;
    if (!date || !pidStr) {
      return apiResponse_1.apiResponse.error(res, "Date and personId are required.", 400);
    }
    try {
      const bazarDate = (0, date_util_1.parseDateOnly)(date);
      const pid = parseInt(pidStr);
      const data = await bazar_service_1.bazarService.getByPerson(bazarDate, pid);
      return apiResponse_1.apiResponse.success(res, data);
    } catch (error) {
      console.error("Person Bazar Fetch Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to fetch person bazar items.", 500);
    }
  }
  async saveBazar(req, res) {
    const body = req.body;
    const {
      date,
      bazar_date,
      personId,
      paid_by,
      paidBy,
      amounts,
      chicken_amount,
      fish_amount,
      dim_amount,
      other_amount,
      special_amount,
      rice_amount,
      items,
    } = body;
    const targetDate = date || bazar_date;
    const targetPersonId = parseInt(personId || paid_by || paidBy);
    if (!targetDate || !targetPersonId || isNaN(targetPersonId)) {
      return apiResponse_1.apiResponse.error(res, "Please select date and who paid.", 400);
    }
    const bazarDate = (0, date_util_1.parseDateOnly)(targetDate);
    const itemsToInsert = [];
    // Format A: Structured items array
    if (Array.isArray(items)) {
      for (const item of items) {
        const cat = (0, bazar_types_1.normalizeBazarCategory)(item.category);
        const amt = parseFloat(String(item.amount || 0));
        if (cat && amt > 0) {
          itemsToInsert.push({
            bazarDate,
            paidById: targetPersonId,
            category: cat,
            itemName: item.itemName || bazar_types_1.DEFAULT_ITEM_NAMES[cat],
            amount: amt,
          });
        }
      }
    } else {
      // Format B: Category-wise amounts object or individual fields
      const amountMap = {
        CHICKEN: parseFloat(amounts?.chicken || amounts?.CHICKEN || chicken_amount || 0),
        FISH: parseFloat(amounts?.fish || amounts?.FISH || fish_amount || 0),
        DIM: parseFloat(amounts?.dim || amounts?.DIM || dim_amount || 0),
        OTHER: parseFloat(amounts?.other || amounts?.OTHER || other_amount || 0),
        SPECIAL: parseFloat(amounts?.special || amounts?.SPECIAL || special_amount || 0),
        RICE: parseFloat(amounts?.rice || amounts?.RICE || rice_amount || 0),
      };
      for (const [cat, amt] of Object.entries(amountMap)) {
        const categoryEnum = cat;
        if (amt > 0) {
          itemsToInsert.push({
            bazarDate,
            paidById: targetPersonId,
            category: categoryEnum,
            itemName: bazar_types_1.DEFAULT_ITEM_NAMES[categoryEnum],
            amount: amt,
          });
        }
      }
    }
    try {
      const result = await bazar_service_1.bazarService.saveItems(
        bazarDate,
        targetPersonId,
        itemsToInsert,
      );
      if (itemsToInsert.length > 0) {
        return apiResponse_1.apiResponse.success(res, undefined, {
          message: `Saved ${itemsToInsert.length} category entries totaling BDT ${result.savedTotal.toFixed(2)}`,
          totalAmount: result.savedTotal,
          entriesCount: itemsToInsert.length,
        });
      } else {
        return apiResponse_1.apiResponse.success(res, undefined, {
          message: "No amounts entered. Existing entries for this date/payer were cleared.",
          totalAmount: 0,
          entriesCount: 0,
        });
      }
    } catch (error) {
      console.error("Bazar Upsert Error:", error);
      return apiResponse_1.apiResponse.error(res, "Failed to update bazar.", 500);
    }
  }
}
exports.BazarController = BazarController;
exports.bazarController = new BazarController();
