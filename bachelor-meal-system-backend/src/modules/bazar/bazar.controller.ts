import type { Request, Response } from "express";
import { apiResponse } from "../../shared/utils/apiResponse";
import { parseDateOnly } from "../../shared/utils/date.util";
import { bazarService } from "./bazar.service";
import {
  type BazarCategoryType,
  type BazarItemInsert,
  DEFAULT_ITEM_NAMES,
  normalizeBazarCategory,
  type SaveBazarPayload,
} from "./bazar.types";

export class BazarController {
  async getByDate(req: Request, res: Response) {
    const { date } = req.query;
    const targetDateStr =
      typeof date === "string" && date ? date : new Date().toISOString().split("T")[0];

    try {
      const bazarDate = parseDateOnly(targetDateStr);
      const data = await bazarService.getByDate(bazarDate, targetDateStr);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Bazar Fetch Error:", error);
      return apiResponse.error(res, "Failed to fetch bazar items.", 500);
    }
  }

  async getByPerson(req: Request, res: Response) {
    const { date, personId, paid_by } = req.query;
    const pidStr = (personId || paid_by) as string;

    if (!date || !pidStr) {
      return apiResponse.error(res, "Date and personId are required.", 400);
    }

    try {
      const bazarDate = parseDateOnly(date as string);
      const pid = parseInt(pidStr);
      const data = await bazarService.getByPerson(bazarDate, pid);
      return apiResponse.success(res, data);
    } catch (error) {
      console.error("Person Bazar Fetch Error:", error);
      return apiResponse.error(res, "Failed to fetch person bazar items.", 500);
    }
  }

  async saveBazar(req: Request, res: Response) {
    const body: SaveBazarPayload = req.body;
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

    const targetDate = (date || bazar_date) as string;
    const targetPersonId = parseInt((personId || paid_by || paidBy) as string);

    if (!targetDate || !targetPersonId || isNaN(targetPersonId)) {
      return apiResponse.error(res, "Please select date and who paid.", 400);
    }

    const bazarDate = parseDateOnly(targetDate);
    const itemsToInsert: BazarItemInsert[] = [];

    // Format A: Structured items array
    if (Array.isArray(items)) {
      for (const item of items) {
        const cat = normalizeBazarCategory(item.category);
        const amt = parseFloat(String(item.amount || 0));
        if (cat && amt > 0) {
          itemsToInsert.push({
            bazarDate,
            paidById: targetPersonId,
            category: cat,
            itemName: item.itemName || DEFAULT_ITEM_NAMES[cat],
            amount: amt,
          });
        }
      }
    } else {
      // Format B: Category-wise amounts object or individual fields
      const amountMap: Record<string, number> = {
        CHICKEN: parseFloat(amounts?.chicken || amounts?.CHICKEN || chicken_amount || 0),
        FISH: parseFloat(amounts?.fish || amounts?.FISH || fish_amount || 0),
        DIM: parseFloat(amounts?.dim || amounts?.DIM || dim_amount || 0),
        OTHER: parseFloat(amounts?.other || amounts?.OTHER || other_amount || 0),
        SPECIAL: parseFloat(amounts?.special || amounts?.SPECIAL || special_amount || 0),
        RICE: parseFloat(amounts?.rice || amounts?.RICE || rice_amount || 0),
      };

      for (const [cat, amt] of Object.entries(amountMap)) {
        const categoryEnum = cat as BazarCategoryType;
        if (amt > 0) {
          itemsToInsert.push({
            bazarDate,
            paidById: targetPersonId,
            category: categoryEnum,
            itemName: DEFAULT_ITEM_NAMES[categoryEnum],
            amount: amt,
          });
        }
      }
    }

    try {
      const result = await bazarService.saveItems(bazarDate, targetPersonId, itemsToInsert);

      if (itemsToInsert.length > 0) {
        return apiResponse.success(res, undefined, {
          message: `Saved ${itemsToInsert.length} category entries totaling BDT ${result.savedTotal.toFixed(2)}`,
          totalAmount: result.savedTotal,
          entriesCount: itemsToInsert.length,
        });
      } else {
        return apiResponse.success(res, undefined, {
          message: "No amounts entered. Existing entries for this date/payer were cleared.",
          totalAmount: 0,
          entriesCount: 0,
        });
      }
    } catch (error) {
      console.error("Bazar Upsert Error:", error);
      return apiResponse.error(res, "Failed to update bazar.", 500);
    }
  }
}

export const bazarController = new BazarController();
