"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.calculateSettlements = calculateSettlements;
/**
 * Greedy bilateral settlement pairing algorithm (Debtors vs Creditors)
 * Matches summary.php lines 435-463
 */
function calculateSettlements(balancesList) {
  const debtors = balancesList
    .filter((b) => b.balance < -0.01)
    .map((b) => ({ id: b.id, name: b.name, amount: Math.abs(b.balance) }))
    .sort((a, b) => b.amount - a.amount);
  const creditors = balancesList
    .filter((b) => b.balance > 0.01)
    .map((b) => ({ id: b.id, name: b.name, amount: b.balance }))
    .sort((a, b) => b.amount - a.amount);
  const settlementSuggestions = [];
  const workingDebtors = debtors.map((d) => ({ ...d }));
  const workingCreditors = creditors.map((c) => ({ ...c }));
  let di = 0;
  let ci = 0;
  while (di < workingDebtors.length && ci < workingCreditors.length) {
    const d = workingDebtors[di];
    const c = workingCreditors[ci];
    const matchAmt = Math.min(d.amount, c.amount);
    if (matchAmt > 0.01) {
      settlementSuggestions.push(`${d.name} pays ৳${matchAmt.toFixed(2)} to ${c.name}`);
      d.amount -= matchAmt;
      c.amount -= matchAmt;
    }
    if (d.amount < 0.01) di++;
    if (c.amount < 0.01) ci++;
  }
  return {
    debtors,
    creditors,
    suggestions: settlementSuggestions,
  };
}
