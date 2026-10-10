"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.utilitiesService = exports.UtilitiesService = void 0;
const prisma_1 = __importDefault(require("../../shared/db/prisma"));
const date_util_1 = require("../../shared/utils/date.util");
class UtilitiesService {
  async getAll() {
    const [persons, utilities, sharedUtilities, utilityFunds] = await Promise.all([
      prisma_1.default.person.findMany({ orderBy: { id: "asc" } }),
      prisma_1.default.utility.findMany(),
      prisma_1.default.sharedUtility.findMany(),
      prisma_1.default.utilityFund.findMany(),
    ]);
    const utilMap = {};
    for (const u of utilities) {
      utilMap[u.personId] = u;
    }
    const fundMap = {};
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
    const sharedMap = {};
    for (const s of sharedUtilities) {
      const amt = parseFloat(s.amount.toString());
      const days = (0, date_util_1.getDaysSince)(s.lastReset);
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
  async updateExpenses(entries) {
    return prisma_1.default.$transaction(
      entries.map((e) =>
        prisma_1.default.utility.upsert({
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
  async updateFund(entries) {
    return prisma_1.default.$transaction(
      entries.map((e) =>
        prisma_1.default.utilityFund.upsert({
          where: { personId: e.personId },
          update: { collected: e.collected },
          create: { personId: e.personId, collected: e.collected },
        }),
      ),
    );
  }
  async resetExpenses() {
    return prisma_1.default.utility.updateMany({
      data: { wifi: 0, current: 0, gas: 0, other: 0 },
    });
  }
  async updateShared(normalizedType, amount, targetDate) {
    const updateData = { amount };
    if (targetDate) {
      updateData.lastReset = targetDate;
    }
    return prisma_1.default.sharedUtility.upsert({
      where: { type: normalizedType },
      update: updateData,
      create: {
        type: normalizedType,
        amount: updateData.amount,
        lastReset: updateData.lastReset || new Date(),
      },
    });
  }
  async resetShared(normalizedType) {
    return prisma_1.default.sharedUtility.upsert({
      where: { type: normalizedType },
      update: { amount: 0, lastReset: new Date() },
      create: { type: normalizedType, amount: 0, lastReset: new Date() },
    });
  }
  async resetFundAndExpenses() {
    return prisma_1.default.$transaction([
      prisma_1.default.utility.updateMany({
        data: { wifi: 0, current: 0, gas: 0, other: 0 },
      }),
      prisma_1.default.utilityFund.updateMany({ data: { collected: 0 } }),
    ]);
  }
}
exports.UtilitiesService = UtilitiesService;
exports.utilitiesService = new UtilitiesService();
