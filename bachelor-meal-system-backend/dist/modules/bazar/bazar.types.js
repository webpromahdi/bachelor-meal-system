"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DEFAULT_ITEM_NAMES = exports.BAZAR_CATEGORIES = void 0;
exports.normalizeBazarCategory = normalizeBazarCategory;
exports.BAZAR_CATEGORIES = ["CHICKEN", "FISH", "DIM", "OTHER", "SPECIAL", "RICE"];
exports.DEFAULT_ITEM_NAMES = {
  CHICKEN: "Chicken/Meat",
  FISH: "Fish/Seafood",
  DIM: "Dim/Egg",
  OTHER: "Other/Vegetables",
  SPECIAL: "Special Meal",
  RICE: "Rice (Chal)",
};
function normalizeBazarCategory(cat) {
  if (!cat) return null;
  const upper = cat.toUpperCase().trim();
  if (exports.BAZAR_CATEGORIES.includes(upper)) {
    return upper;
  }
  return null;
}
