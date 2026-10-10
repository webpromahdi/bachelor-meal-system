export interface CategoryBazarMap {
  CHICKEN: number;
  FISH: number;
  DIM: number;
  OTHER: number;
  SPECIAL: number;
  RICE: number;
}

export interface CategoryMealCountMap {
  CHICKEN: number;
  FISH: number;
  DIM: number;
  OTHER: number;
  SPECIAL: number;
}

export interface SessionMealBreakdown {
  CHICKEN: number;
  FISH: number;
  DIM: number;
  OTHER: number;
  SPECIAL: number;
  total: number;
}

export interface SessionMealTotals {
  LUNCH: SessionMealBreakdown;
  DINNER: SessionMealBreakdown;
  TOTAL: {
    CHICKEN: number;
    FISH: number;
    DIM: number;
    OTHER: number;
    SPECIAL: number;
    grandTotal: number;
  };
}

export interface PersonSummaryStats {
  id: number;
  name: string;
  meals: {
    CHICKEN: number;
    FISH: number;
    DIM: number;
    OTHER: number;
    SPECIAL: number;
    LUNCH: number;
    DINNER: number;
    total: number;
  };
  bazarPaid: number;
  ricePaid: number;
  matrix: Record<
    string,
    {
      LUNCH?: { type: string; count: number };
      DINNER?: { type: string; count: number };
    }
  >;
}

export interface DailyLogEntry {
  date: string;
  dayNum: number;
  dayName: string;
  lunch: number;
  dinner: number;
  total: number;
  chicken: number;
  fish: number;
  dim: number;
  special: number;
  other: number;
  rice: number;
  dailyBazar: number;
  types: string[];
  active: boolean;
}

export interface DailyLogTotals {
  lunch: number;
  dinner: number;
  totalMeals: number;
  chicken: number;
  fish: number;
  dim: number;
  special: number;
  other: number;
  rice: number;
  grandBazarTotal: number;
}

export interface CategoryRatesResult {
  totals: CategoryBazarMap;
  counts: {
    CHICKEN: number;
    FISH: number;
    DIM: number;
    OTHER: number;
    SPECIAL: number;
    RICE: number;
  };
  rates: {
    CHICKEN: number;
    FISH: number;
    DIM: number;
    OTHER: number;
    SPECIAL: number;
    RICE: number;
  };
  overallRate: number;
  riceMealRate: number;
  effectiveOverallRate: number;
  totalMonthlyMeals: number;
  specialMeals: number;
}

export interface DebtorEntry {
  id: number;
  name: string;
  amount: number;
}

export interface CreditorEntry {
  id: number;
  name: string;
  amount: number;
}
