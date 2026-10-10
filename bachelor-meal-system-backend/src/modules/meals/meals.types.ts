export const VALID_MEAL_TYPES = ["CHICKEN", "FISH", "DIM", "OTHER", "SPECIAL"] as const;

export type ValidMealType = (typeof VALID_MEAL_TYPES)[number];

export function normalizeMealType(type?: string | null): ValidMealType | null {
  if (!type) return null;
  const upper = type.toUpperCase().trim();
  if (VALID_MEAL_TYPES.includes(upper as ValidMealType)) {
    return upper as ValidMealType;
  }
  return null;
}

export interface MealCountEntry {
  personId: number;
  count: number;
}

export interface SessionMealInput {
  personId: string | number;
  mealCount: string | number;
  mealType?: string;
}

export interface SaveMealsPayload {
  date: string;
  globalLunchType?: string;
  globalDinnerType?: string;
  lunchType?: string;
  dinnerType?: string;
  lunchCounts?: any;
  dinnerCounts?: any;
  session?: string;
  meals?: SessionMealInput[];
}
