"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.VALID_MEAL_TYPES = void 0;
exports.normalizeMealType = normalizeMealType;
exports.VALID_MEAL_TYPES = ["CHICKEN", "FISH", "DIM", "OTHER", "SPECIAL"];
function normalizeMealType(type) {
  if (!type) return null;
  const upper = type.toUpperCase().trim();
  if (exports.VALID_MEAL_TYPES.includes(upper)) {
    return upper;
  }
  return null;
}
