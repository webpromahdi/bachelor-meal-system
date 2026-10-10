export const BAZAR_CATEGORIES = ["CHICKEN", "FISH", "DIM", "OTHER", "SPECIAL", "RICE"] as const;

export type BazarCategoryType = (typeof BAZAR_CATEGORIES)[number];

export const DEFAULT_ITEM_NAMES: Record<BazarCategoryType, string> = {
  CHICKEN: "Chicken/Meat",
  FISH: "Fish/Seafood",
  DIM: "Dim/Egg",
  OTHER: "Other/Vegetables",
  SPECIAL: "Special Meal",
  RICE: "Rice (Chal)",
};

export function normalizeBazarCategory(cat?: string | null): BazarCategoryType | null {
  if (!cat) return null;
  const upper = cat.toUpperCase().trim();
  if (BAZAR_CATEGORIES.includes(upper as BazarCategoryType)) {
    return upper as BazarCategoryType;
  }
  return null;
}

export interface BazarItemInsert {
  bazarDate: Date;
  paidById: number;
  category: BazarCategoryType;
  itemName: string;
  amount: number;
}

export interface SaveBazarPayload {
  date?: string;
  bazar_date?: string;
  personId?: string | number;
  paid_by?: string | number;
  paidBy?: string | number;
  amounts?: Record<string, any>;
  chicken_amount?: string | number;
  fish_amount?: string | number;
  dim_amount?: string | number;
  other_amount?: string | number;
  special_amount?: string | number;
  rice_amount?: string | number;
  items?: Array<{
    category: string;
    amount: string | number;
    itemName?: string;
  }>;
}
