"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.SummaryCalculator = void 0;
class SummaryCalculator {
  static aggregateBazar(bazarItems) {
    let totalBazar = 0;
    const categoryBazar = {
      CHICKEN: 0,
      FISH: 0,
      DIM: 0,
      OTHER: 0,
      SPECIAL: 0,
      RICE: 0,
    };
    const dailyBazarMap = {};
    for (const item of bazarItems) {
      const dateStr = item.bazarDate.toISOString().split("T")[0];
      const amount = parseFloat(item.amount.toString());
      const cat = item.category.toUpperCase();
      if (categoryBazar[cat] !== undefined) {
        categoryBazar[cat] += amount;
      }
      totalBazar += amount;
      if (!dailyBazarMap[dateStr]) {
        dailyBazarMap[dateStr] = {
          CHICKEN: 0,
          FISH: 0,
          DIM: 0,
          OTHER: 0,
          SPECIAL: 0,
          RICE: 0,
          total: 0,
        };
      }
      if (dailyBazarMap[dateStr][cat] !== undefined) {
        dailyBazarMap[dateStr][cat] += amount;
      }
      dailyBazarMap[dateStr].total += amount;
    }
    return { totalBazar, categoryBazar, dailyBazarMap };
  }
  static aggregateMeals(persons, meals, bazarItems) {
    let totalMeals = 0;
    const categoryMealsCount = {
      CHICKEN: 0,
      FISH: 0,
      DIM: 0,
      OTHER: 0,
      SPECIAL: 0,
    };
    const sessionMealTotals = {
      LUNCH: { CHICKEN: 0, FISH: 0, DIM: 0, OTHER: 0, SPECIAL: 0, total: 0 },
      DINNER: { CHICKEN: 0, FISH: 0, DIM: 0, OTHER: 0, SPECIAL: 0, total: 0 },
      TOTAL: {
        CHICKEN: 0,
        FISH: 0,
        DIM: 0,
        OTHER: 0,
        SPECIAL: 0,
        grandTotal: 0,
      },
    };
    const personStats = {};
    for (const p of persons) {
      personStats[p.id] = {
        id: p.id,
        name: p.name,
        meals: {
          CHICKEN: 0,
          FISH: 0,
          DIM: 0,
          OTHER: 0,
          SPECIAL: 0,
          LUNCH: 0,
          DINNER: 0,
          total: 0,
        },
        bazarPaid: 0,
        ricePaid: 0,
        matrix: {},
      };
    }
    for (const item of bazarItems) {
      const amount = parseFloat(item.amount.toString());
      if (personStats[item.paidById]) {
        personStats[item.paidById].bazarPaid += amount;
        if (item.category.toUpperCase() === "RICE") {
          personStats[item.paidById].ricePaid += amount;
        }
      }
    }
    const dailyMealsMap = {};
    for (const m of meals) {
      const dateStr = m.mealDate.toISOString().split("T")[0];
      const count = m.mealCount;
      const cat = m.mealType.toUpperCase();
      const sess = m.session.toUpperCase();
      totalMeals += count;
      if (categoryMealsCount[cat] !== undefined) {
        categoryMealsCount[cat] += count;
      }
      if (
        (sess === "LUNCH" || sess === "DINNER") &&
        ["CHICKEN", "FISH", "DIM", "OTHER", "SPECIAL"].includes(cat)
      ) {
        const catKey = cat;
        sessionMealTotals[sess][catKey] += count;
        sessionMealTotals[sess].total += count;
        sessionMealTotals.TOTAL[catKey] += count;
        sessionMealTotals.TOTAL.grandTotal += count;
      }
      if (!dailyMealsMap[dateStr]) {
        dailyMealsMap[dateStr] = {
          lunch: 0,
          dinner: 0,
          total: 0,
          types: new Set(),
        };
      }
      if (sess === "LUNCH") dailyMealsMap[dateStr].lunch += count;
      if (sess === "DINNER") dailyMealsMap[dateStr].dinner += count;
      dailyMealsMap[dateStr].total += count;
      dailyMealsMap[dateStr].types.add(cat);
      if (personStats[m.personId]) {
        if (personStats[m.personId].meals[cat] !== undefined) {
          personStats[m.personId].meals[cat] += count;
        }
        if (sess === "LUNCH") personStats[m.personId].meals.LUNCH += count;
        if (sess === "DINNER") personStats[m.personId].meals.DINNER += count;
        personStats[m.personId].meals.total += count;
        if (!personStats[m.personId].matrix[dateStr]) {
          personStats[m.personId].matrix[dateStr] = {};
        }
        personStats[m.personId].matrix[dateStr][sess] = {
          type: cat,
          count: count,
        };
      }
    }
    return {
      totalMeals,
      categoryMealsCount,
      sessionMealTotals,
      personStats,
      dailyMealsMap,
    };
  }
  static generateDailyLog(targetMonth, lastDayOfMonth, dailyMealsMap, dailyBazarMap) {
    const dailyLogList = [];
    const dailyLogTotals = {
      lunch: 0,
      dinner: 0,
      totalMeals: 0,
      chicken: 0,
      fish: 0,
      dim: 0,
      special: 0,
      other: 0,
      rice: 0,
      grandBazarTotal: 0,
    };
    for (let day = 1; day <= lastDayOfMonth; day++) {
      const dateStr = `${targetMonth}-${String(day).padStart(2, "0")}`;
      const dObj = new Date(`${dateStr}T00:00:00.000Z`);
      const dayName = dObj.toLocaleDateString("en-US", {
        weekday: "short",
        timeZone: "UTC",
      });
      const mealEntry = dailyMealsMap[dateStr];
      const bazarEntry = dailyBazarMap[dateStr];
      const lunch = mealEntry ? mealEntry.lunch : 0;
      const dinner = mealEntry ? mealEntry.dinner : 0;
      const total = lunch + dinner;
      const chicken = bazarEntry ? bazarEntry.CHICKEN || 0 : 0;
      const fish = bazarEntry ? bazarEntry.FISH || 0 : 0;
      const dim = bazarEntry ? bazarEntry.DIM || 0 : 0;
      const special = bazarEntry ? bazarEntry.SPECIAL || 0 : 0;
      const other = bazarEntry ? bazarEntry.OTHER || 0 : 0;
      const rice = bazarEntry ? bazarEntry.RICE || 0 : 0;
      const dailyBazar = chicken + fish + dim + special + other + rice;
      dailyLogTotals.lunch += lunch;
      dailyLogTotals.dinner += dinner;
      dailyLogTotals.totalMeals += total;
      dailyLogTotals.chicken += chicken;
      dailyLogTotals.fish += fish;
      dailyLogTotals.dim += dim;
      dailyLogTotals.special += special;
      dailyLogTotals.other += other;
      dailyLogTotals.rice += rice;
      dailyLogTotals.grandBazarTotal += dailyBazar;
      dailyLogList.push({
        date: dateStr,
        dayNum: day,
        dayName,
        lunch,
        dinner,
        total,
        chicken,
        fish,
        dim,
        special,
        other,
        rice,
        dailyBazar,
        types: mealEntry ? Array.from(mealEntry.types) : [],
        active: total > 0 || dailyBazar > 0,
      });
    }
    return { dailyLogList, dailyLogTotals };
  }
  static calculateCategoryRates(categoryBazar, categoryMealsCount) {
    const chickenMeals = categoryMealsCount.CHICKEN;
    const fishMeals = categoryMealsCount.FISH;
    const dimMeals = categoryMealsCount.DIM;
    const otherMeals = categoryMealsCount.OTHER;
    const specialMeals = categoryMealsCount.SPECIAL;
    const allRegularMeals = chickenMeals + fishMeals + dimMeals + otherMeals;
    const totalMonthlyMeals = allRegularMeals + specialMeals;
    const totalRiceMeals = allRegularMeals; // Rule: Special meal eaters do NOT eat rice
    const chickenRate = chickenMeals > 0 ? categoryBazar.CHICKEN / chickenMeals : 0;
    const fishRate = fishMeals > 0 ? categoryBazar.FISH / fishMeals : 0;
    const dimRate = dimMeals > 0 ? categoryBazar.DIM / dimMeals : 0;
    const otherRate = allRegularMeals > 0 ? categoryBazar.OTHER / allRegularMeals : 0;
    const specialRate = specialMeals > 0 ? categoryBazar.SPECIAL / specialMeals : 0;
    const mealBasedBazar =
      categoryBazar.CHICKEN +
      categoryBazar.FISH +
      categoryBazar.DIM +
      categoryBazar.OTHER +
      categoryBazar.SPECIAL;
    const overallRate = totalMonthlyMeals > 0 ? mealBasedBazar / totalMonthlyMeals : 0;
    const riceMealRate = totalRiceMeals > 0 ? categoryBazar.RICE / totalRiceMeals : 0;
    const effectiveOverallRate = overallRate + riceMealRate;
    return {
      totals: categoryBazar,
      counts: {
        CHICKEN: chickenMeals,
        FISH: fishMeals,
        DIM: dimMeals,
        OTHER: allRegularMeals,
        SPECIAL: specialMeals,
        RICE: totalRiceMeals,
      },
      rates: {
        CHICKEN: chickenRate,
        FISH: fishRate,
        DIM: dimRate,
        OTHER: otherRate,
        SPECIAL: specialRate,
        RICE: riceMealRate,
      },
      overallRate,
      riceMealRate,
      effectiveOverallRate,
      totalMonthlyMeals,
      specialMeals,
    };
  }
  static calculateCostDistributionAndBalances(persons, personStats, rates) {
    const costDistribution = [];
    const balanceSheet = [];
    const costDistributionTotals = {
      chickenMeals: 0,
      chickenCost: 0,
      fishMeals: 0,
      fishCost: 0,
      dimMeals: 0,
      dimCost: 0,
      otherMeals: 0,
      otherCost: 0,
      specialMeals: 0,
      specialCost: 0,
      riceCost: 0,
      totalMeals: 0,
      totalCost: 0,
    };
    const balanceSheetTotals = {
      totalMeals: 0,
      totalPaid: 0,
      shouldPay: 0,
      balance: 0,
    };
    const balancesList = [];
    for (const p of persons) {
      const stats = personStats[p.id];
      const personChicken = stats.meals.CHICKEN;
      const personFish = stats.meals.FISH;
      const personDim = stats.meals.DIM;
      const personOther = stats.meals.OTHER;
      const personSpecial = stats.meals.SPECIAL;
      const personTotalMeals = stats.meals.total;
      const personRegularExclSpecial = personTotalMeals - personSpecial;
      const chickenCost = personChicken * rates.CHICKEN;
      const fishCost = personFish * rates.FISH;
      const dimCost = personDim * rates.DIM;
      const otherCost = personRegularExclSpecial * rates.OTHER;
      const specialCost = personSpecial * rates.SPECIAL;
      const riceCost = personRegularExclSpecial * rates.RICE;
      const totalPersonCost = chickenCost + fishCost + dimCost + otherCost + specialCost + riceCost;
      const balance = stats.bazarPaid - totalPersonCost;
      costDistributionTotals.chickenMeals += personChicken;
      costDistributionTotals.chickenCost += chickenCost;
      costDistributionTotals.fishMeals += personFish;
      costDistributionTotals.fishCost += fishCost;
      costDistributionTotals.dimMeals += personDim;
      costDistributionTotals.dimCost += dimCost;
      costDistributionTotals.otherMeals += personOther;
      costDistributionTotals.otherCost += otherCost;
      costDistributionTotals.specialMeals += personSpecial;
      costDistributionTotals.specialCost += specialCost;
      costDistributionTotals.riceCost += riceCost;
      costDistributionTotals.totalMeals += personTotalMeals;
      costDistributionTotals.totalCost += totalPersonCost;
      costDistribution.push({
        id: p.id,
        name: p.name,
        chickenMeals: personChicken,
        chickenCost,
        fishMeals: personFish,
        fishCost,
        dimMeals: personDim,
        dimCost,
        otherMeals: personOther,
        otherCost,
        specialMeals: personSpecial,
        specialCost,
        ricePaid: stats.ricePaid,
        riceCost,
        totalMeals: personTotalMeals,
        totalCost: totalPersonCost,
        meals: stats.meals,
        cost: {
          CHICKEN: chickenCost,
          FISH: fishCost,
          DIM: dimCost,
          OTHER: otherCost,
          SPECIAL: specialCost,
          RICE: riceCost,
        },
      });
      let status = "settled";
      if (balance > 0.01) status = "to_receive";
      else if (balance < -0.01) status = "to_pay";
      balanceSheetTotals.totalMeals += personTotalMeals;
      balanceSheetTotals.totalPaid += stats.bazarPaid;
      balanceSheetTotals.shouldPay += totalPersonCost;
      balanceSheetTotals.balance += balance;
      balanceSheet.push({
        id: p.id,
        name: p.name,
        totalMeals: personTotalMeals,
        totalPaid: stats.bazarPaid,
        shouldPay: totalPersonCost,
        balance,
        status,
        ricePaid: stats.ricePaid,
        riceCost,
      });
      if (Math.abs(balance) > 0.01) {
        balancesList.push({ id: p.id, name: p.name, balance });
      }
    }
    return {
      costDistribution,
      costDistributionTotals,
      balanceSheet,
      balanceSheetTotals,
      balancesList,
    };
  }
  static buildBazarDetails(bazarItems, totalBazar) {
    const bazarDetailsItems = [];
    const bazarByPerson = {};
    const datesWithBazar = new Set();
    for (const item of bazarItems) {
      const amt = parseFloat(item.amount.toString());
      const dateStr = item.bazarDate.toISOString().split("T")[0];
      datesWithBazar.add(dateStr);
      const itemRecord = {
        id: item.id,
        date: dateStr,
        itemName: item.itemName || item.category,
        category: item.category.toLowerCase(),
        amount: amt,
        paidById: item.paidById,
        personName: item.paidBy?.name || "Unknown",
      };
      bazarDetailsItems.push(itemRecord);
      if (!bazarByPerson[item.paidById]) {
        bazarByPerson[item.paidById] = {
          id: item.paidById,
          name: item.paidBy?.name || "Unknown",
          items: [],
          total: 0,
        };
      }
      bazarByPerson[item.paidById].items.push(itemRecord);
      bazarByPerson[item.paidById].total += amt;
    }
    return {
      items: bazarDetailsItems,
      byPerson: bazarByPerson,
      stats: {
        totalEntries: bazarDetailsItems.length,
        contributorsCount: Object.keys(bazarByPerson).length,
        daysWithBazar: datesWithBazar.size,
        totalSpent: totalBazar,
      },
    };
  }
}
exports.SummaryCalculator = SummaryCalculator;
