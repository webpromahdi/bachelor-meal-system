"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.parseDateOnly = parseDateOnly;
exports.getDaysSince = getDaysSince;
exports.getMonthDateRange = getMonthDateRange;
/**
 * Parses a date string (YYYY-MM-DD or ISO) into UTC Date at midnight.
 */
function parseDateOnly(dateStr) {
  const clean = dateStr.trim().split("T")[0];
  return new Date(`${clean}T00:00:00.000Z`);
}
/**
 * Calculates full days elapsed between a date and now.
 */
function getDaysSince(dateObj) {
  const now = new Date();
  const diffTime = now.getTime() - dateObj.getTime();
  return Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
}
/**
 * Calculates start and end Date boundaries and last day for a given YYYY-MM string.
 */
function getMonthDateRange(targetMonth) {
  const [year, monthNum] = targetMonth.split("-").map(Number);
  const startDate = new Date(`${targetMonth}-01T00:00:00.000Z`);
  const lastDayOfMonth = new Date(year, monthNum, 0).getDate();
  const endDate = new Date(
    `${targetMonth}-${String(lastDayOfMonth).padStart(2, "0")}T23:59:59.999Z`,
  );
  return {
    year,
    monthNum,
    startDate,
    endDate,
    lastDayOfMonth,
  };
}
