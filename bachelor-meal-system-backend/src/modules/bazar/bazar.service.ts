import prisma from "../../shared/db/prisma";
import type { BazarItemInsert } from "./bazar.types";

export class BazarService {
  async getByDate(bazarDate: Date, targetDateStr: string) {
    const [items, persons] = await Promise.all([
      prisma.bazarItem.findMany({
        where: { bazarDate },
        include: { paidBy: true },
        orderBy: { id: "asc" },
      }),
      prisma.person.findMany({ orderBy: { id: "asc" } }),
    ]);

    const categoryTotals: Record<string, number> = {
      chicken: 0,
      fish: 0,
      dim: 0,
      other: 0,
      special: 0,
      rice: 0,
    };
    let grandTotal = 0;

    // Grouping by person
    const byPerson: Record<
      number,
      { name: string; amounts: Record<string, number>; total: number }
    > = {};

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

  async getByPerson(bazarDate: Date, paidById: number) {
    const items = await prisma.bazarItem.findMany({
      where: { bazarDate, paidById },
    });

    const categoryAmounts: Record<string, number> = {
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

  async saveItems(bazarDate: Date, targetPersonId: number, itemsToInsert: BazarItemInsert[]) {
    let savedTotal = 0;

    await prisma.$transaction(async (tx) => {
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

export const bazarService = new BazarService();
