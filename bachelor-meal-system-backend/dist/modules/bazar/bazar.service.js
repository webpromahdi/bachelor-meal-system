"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.bazarService = exports.BazarService = void 0;
const prisma_1 = __importDefault(require("../../shared/db/prisma"));
class BazarService {
  async getByDate(bazarDate, targetDateStr) {
    const [items, persons] = await Promise.all([
      prisma_1.default.bazarItem.findMany({
        where: { bazarDate },
        include: { paidBy: true },
        orderBy: { id: "asc" },
      }),
      prisma_1.default.person.findMany({ orderBy: { id: "asc" } }),
    ]);
    const categoryTotals = {
      chicken: 0,
      fish: 0,
      dim: 0,
      other: 0,
      special: 0,
      rice: 0,
    };
    let grandTotal = 0;
    // Grouping by person
    const byPerson = {};
    for (const p of persons) {
      byPerson[p.id] = {
        name: p.name,
        amounts: { chicken: 0, fish: 0, dim: 0, other: 0, special: 0, rice: 0 },
        total: 0,
      };
    }
    for (const item of items) {
      const amt = parseFloat(item.amount.toString());
      const catKey = item.category.toLowerCase();
      if (categoryTotals[catKey] !== undefined) {
        categoryTotals[catKey] += amt;
      }
      grandTotal += amt;
      if (byPerson[item.paidById]) {
        byPerson[item.paidById].amounts[catKey] =
          (byPerson[item.paidById].amounts[catKey] || 0) + amt;
        byPerson[item.paidById].total += amt;
      }
    }
    return {
      date: targetDateStr,
      items,
      categoryTotals,
      grandTotal,
      byPerson,
    };
  }
  async getByPerson(bazarDate, paidById) {
    const items = await prisma_1.default.bazarItem.findMany({
      where: { bazarDate, paidById },
    });
    const categoryAmounts = {
      chicken: 0,
      fish: 0,
      dim: 0,
      other: 0,
      special: 0,
      rice: 0,
    };
    let total = 0;
    for (const i of items) {
      const amt = parseFloat(i.amount.toString());
      const catKey = i.category.toLowerCase();
      if (categoryAmounts[catKey] !== undefined) {
        categoryAmounts[catKey] += amt;
      }
      total += amt;
    }
    return {
      items,
      categoryAmounts,
      total,
      hasExistingData: items.length > 0,
    };
  }
  async saveItems(bazarDate, targetPersonId, itemsToInsert) {
    let savedTotal = 0;
    await prisma_1.default.$transaction(async (tx) => {
      // 1. Delete existing items for this person on this date
      await tx.bazarItem.deleteMany({
        where: { bazarDate, paidById: targetPersonId },
      });
      // 2. Insert new items where amount > 0
      if (itemsToInsert.length > 0) {
        await tx.bazarItem.createMany({
          data: itemsToInsert,
        });
        savedTotal = itemsToInsert.reduce((sum, item) => sum + item.amount, 0);
      }
    });
    return {
      savedTotal,
      entriesCount: itemsToInsert.length,
    };
  }
}
exports.BazarService = BazarService;
exports.bazarService = new BazarService();
