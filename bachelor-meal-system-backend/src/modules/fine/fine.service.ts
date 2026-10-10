import prisma from "../../shared/db/prisma";
import { getMonthDateRange } from "../../shared/utils/date.util";
import type { FineEntryInput, FineSummaryItem } from "./fine.types";

export class FineService {
  async getFinesByDate(fineDate: Date, targetDateStr: string) {
    const fines = await prisma.fine.findMany({
      where: { fineDate },
      include: { person: true },
      orderBy: { personId: "asc" },
    });

    const fineCounts: Record<number, number> = {};
    const amounts: Record<number, number> = {};
    const reasons: Record<number, string> = {};
    let totalCount = 0;
    let totalAmount = 0;

    for (const f of fines) {
      fineCounts[f.personId] = f.fineCount;
      amounts[f.personId] = Number(f.amount);
      if (f.reason) {
        reasons[f.personId] = f.reason;
      }
      totalCount += f.fineCount;
      totalAmount += Number(f.amount);
    }

    const hasSavedData = fines.length > 0;

    return {
      date: targetDateStr,
      hasSavedData,
      fineCounts,
      amounts,
      reasons,
      fines: fines.map((f) => ({
        id: f.id,
        fineDate: targetDateStr,
        personId: f.personId,
        personName: f.person.name,
        fineCount: f.fineCount,
        amount: Number(f.amount),
        reason: f.reason || "",
      })),
      totalCount,
      totalAmount,
    };
  }

  async saveDayFines(fineDate: Date, entries: FineEntryInput[], commonReason?: string) {
    let insertedRowsCount = 0;

    await prisma.$transaction(async (tx) => {
      // 1. Delete existing fines for that date
      await tx.fine.deleteMany({
        where: { fineDate },
      });

      // 2. Prepare records where count > 0 or amount > 0
      const recordsToInsert = entries
        .filter((item) => {
          const count = Number(item.count ?? item.fineCount ?? 0);
          const amount = Number(item.amount ?? 0);
          return count > 0 || amount > 0;
        })
        .map((item) => {
          const count = Number(item.count ?? item.fineCount ?? 0);
          const amount = Number(item.amount ?? 0);
          const reason = (item.reason || commonReason || "").trim();

          return {
            fineDate,
            personId: item.personId,
            fineCount: count > 0 ? count : 1,
            amount: amount,
            reason: reason,
          };
        });

      if (recordsToInsert.length > 0) {
        await tx.fine.createMany({
          data: recordsToInsert,
        });
        insertedRowsCount = recordsToInsert.length;
      }
    });

    return insertedRowsCount;
  }

  async getMonthlyFines(monthStr: string) {
    const { startDate, endDate } = getMonthDateRange(monthStr);

    const [fines, persons] = await Promise.all([
      prisma.fine.findMany({
        where: {
          fineDate: {
            gte: startDate,
            lte: endDate,
          },
        },
        include: { person: true },
        orderBy: [{ fineDate: "desc" }, { personId: "asc" }],
      }),
      prisma.person.findMany({ orderBy: { id: "asc" } }),
    ]);

    // Member summary
    const summaryMap: Record<number, FineSummaryItem> = {};
    for (const p of persons) {
      summaryMap[p.id] = {
        personId: p.id,
        personName: p.name,
        totalCount: 0,
        totalAmount: 0,
      };
    }

    let overallCount = 0;
    let overallAmount = 0;

    // Group by date for history table
    const dailyMap: Record<
      string,
      {
        date: string;
        totalCount: number;
        totalAmount: number;
        entries: Array<{
          personId: number;
          name: string;
          fineCount: number;
          amount: number;
          reason: string;
        }>;
      }
    > = {};

    for (const f of fines) {
      const dateKey = f.fineDate.toISOString().split("T")[0];
      const count = f.fineCount;
      const amt = Number(f.amount);

      overallCount += count;
      overallAmount += amt;

      if (summaryMap[f.personId]) {
        summaryMap[f.personId].totalCount += count;
        summaryMap[f.personId].totalAmount += amt;
      }

      if (!dailyMap[dateKey]) {
        dailyMap[dateKey] = {
          date: dateKey,
          totalCount: 0,
          totalAmount: 0,
          entries: [],
        };
      }

      dailyMap[dateKey].totalCount += count;
      dailyMap[dateKey].totalAmount += amt;
      dailyMap[dateKey].entries.push({
        personId: f.personId,
        name: f.person.name,
        fineCount: count,
        amount: amt,
        reason: f.reason || "",
      });
    }

    return {
      month: monthStr,
      overallCount,
      overallAmount,
      memberSummary: Object.values(summaryMap),
      dailyHistory: Object.values(dailyMap).sort((a, b) => b.date.localeCompare(a.date)),
    };
  }

  async getAllFines(limit = 100) {
    const fines = await prisma.fine.findMany({
      take: limit,
      include: { person: true },
      orderBy: [{ fineDate: "desc" }, { personId: "asc" }],
    });

    return fines.map((f) => ({
      id: f.id,
      fineDate: f.fineDate.toISOString().split("T")[0],
      personId: f.personId,
      personName: f.person.name,
      fineCount: f.fineCount,
      amount: Number(f.amount),
      reason: f.reason || "",
    }));
  }

  async deleteFine(id: number) {
    return prisma.fine.delete({
      where: { id },
    });
  }
}

export const fineService = new FineService();
