/**
 * Parses a date string (YYYY-MM-DD or ISO) into UTC Date at midnight.
 */
export function parseDateOnly(dateStr: string): Date {
  const clean = dateStr.trim().split("T")[0];
  return new Date(`${clean}T00:00:00.000Z`);
}

/**
 * Calculates full days elapsed between a date and now.
 */
export function getDaysSince(dateObj: Date): number {
  const now = new Date();
  const diffTime = now.getTime() - dateObj.getTime();
  return Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
}

/**
 * Calculates start and end Date boundaries and last day for a given YYYY-MM string.
 */
export function getMonthDateRange(targetMonth: string) {
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
