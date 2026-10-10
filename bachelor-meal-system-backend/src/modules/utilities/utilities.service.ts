import prisma from "../../shared/db/prisma";
import { getDaysSince } from "../../shared/utils/date.util";
import type { FundEntry, UtilityEntry } from "./utilities.types";

export class UtilitiesService {
  async getAll() {
    const [persons, utilities, sharedUtilities, utilityFunds] = await Promise.all([
      prisma.person.findMany({ orderBy: { id: "asc" } }),
      prisma.utility.findMany(),
      prisma.sharedUtility.findMany(),
      prisma.utilityFund.findMany(),
    ]);

    const utilMap: Record<number, any> = {};
    for (const u of utilities) {
      utilMap[u.personId] = u;
    }

    const fundMap: Record<number, any> = {};
    for (const f of utilityFunds) {
      fundMap[f.personId] = f;
    }

    // 1. Process Utility Expenses Table
    const colTotals = { wifi: 0, current: 0, gas: 0, other: 0 };
    let grandTotal = 0;

    const utilityRows = persons.map((p) => {
      const u = utilMap[p.id];
      const wifi = u ? parseFloat(u.wifi.toString()) : 0;
      const current = u ? parseFloat(u.current.toString()) : 0;
      const gas = u ? parseFloat(u.gas.toString()) : 0;
      const other = u ? parseFloat(u.other.toString()) : 0;
      const subtotal = wifi + current + gas + other;

      colTotals.wifi += wifi;
      colTotals.current += current;
      colTotals.gas += gas;
      colTotals.other += other;
      grandTotal += subtotal;

      return {
        id: p.id,
        name: p.name,
        wifi,
        current,
        gas,
        other,
        subtotal,
      };
    });

    // 2. Process Fund Overview Table
    let totalCollected = 0;
    let totalSpent = 0;

    const fundRows = persons.map((p) => {
      const f = fundMap[p.id];
      const u = utilMap[p.id];

      const collected = f ? parseFloat(f.collected.toString()) : 0;
      const spent = u
        ? parseFloat(u.wifi.toString()) +
          parseFloat(u.current.toString()) +
          parseFloat(u.gas.toString()) +
          parseFloat(u.other.toString())
        : 0;
      const balance = collected - spent;

      totalCollected += collected;
      totalSpent += spent;

      return {
        id: p.id,
        name: p.name,
        collected,
        spent,
        balance,
      };
    });

    const totalBalance = totalCollected - totalSpent;

    // 3. Process Shared Utilities with Running Days
    const sharedMap: Record<string, any> = {};
    for (const s of sharedUtilities) {
      const amt = parseFloat(s.amount.toString());
      const days = getDaysSince(s.lastReset);
      sharedMap[s.type] = {
        type: s.type,
        amount: amt,
        lastReset: s.lastReset,
        daysRunning: days,
      };
    }

    // Ensure default current and gas objects exist
    if (!sharedMap.current) {
      sharedMap.current = {
        type: "current",
        amount: 0,
        lastReset: new Date(),
        daysRunning: 0,
      };
    }
    if (!sharedMap.gas) {
      sharedMap.gas = {
        type: "gas",
        amount: 0,
        lastReset: new Date(),
        daysRunning: 0,
      };
    }

    return {
      utilities: utilityRows,
      columnTotals: colTotals,
      grandTotal,
      fund: fundRows,
      fundTotals: {
        totalCollected,
        totalSpent,
        totalBalance,
      },
      sharedUtilities: sharedMap,
    };
  }

  async updateExpenses(entries: UtilityEntry[]) {
    return prisma.$transaction(
      entries.map((e) =>
        prisma.utility.upsert({
          where: { personId: e.personId },
          update: {
            wifi: e.wifi,
            current: e.current,
            gas: e.gas,
            other: e.other,
          },
          create: {
            personId: e.personId,
            wifi: e.wifi,
            current: e.current,
            gas: e.gas,
            other: e.other,
          },
        }),
      ),
    );
  }

  async updateFund(entries: FundEntry[]) {
    return prisma.$transaction(
      entries.map((e) =>
        prisma.utilityFund.upsert({
          where: { personId: e.personId },
          update: { collected: e.collected },
          create: { personId: e.personId, collected: e.collected },
        }),
      ),
    );
  }

  async resetExpenses() {
    return prisma.utility.updateMany({
      data: { wifi: 0, current: 0, gas: 0, other: 0 },
    });
  }

  async updateShared(normalizedType: string, amount: number, targetDate?: Date) {
    const updateData: any = { amount };
    if (targetDate) {
      updateData.lastReset = targetDate;
    }

    return prisma.sharedUtility.upsert({
      where: { type: normalizedType },
      update: updateData,
      create: {
        type: normalizedType,
        amount: updateData.amount,
        lastReset: updateData.lastReset || new Date(),
      },
    });
  }

  async resetShared(normalizedType: string) {
    return prisma.sharedUtility.upsert({
      where: { type: normalizedType },
      update: { amount: 0, lastReset: new Date() },
      create: { type: normalizedType, amount: 0, lastReset: new Date() },
    });
  }

  async resetFundAndExpenses() {
    return prisma.$transaction([
      prisma.utility.updateMany({
        data: { wifi: 0, current: 0, gas: 0, other: 0 },
      }),
      prisma.utilityFund.updateMany({ data: { collected: 0 } }),
    ]);
  }
}

export const utilitiesService = new UtilitiesService();
