import prisma from "../src/shared/db/prisma";

async function resetDatabase() {
  console.log("=== RESETTING DATABASE (TRANSACTIONAL DATA ONLY) ===");

  const result = await prisma.$transaction(async (tx) => {
    // 1. Delete all meals
    const deletedMeals = await tx.dailyMeal.deleteMany({});

    // 2. Delete all bazar items
    const deletedBazar = await tx.bazarItem.deleteMany({});

    // 3. Reset or clear individual utility expenses
    const deletedUtilities = await tx.utility.deleteMany({});

    // 4. Reset or clear utility funds
    const deletedFunds = await tx.utilityFund.deleteMany({});

    // 5. Delete all fines
    const deletedFines = await tx.fine.deleteMany({});

    // 6. Reset shared utilities to 0 and current timestamp
    await tx.sharedUtility.updateMany({
      data: {
        amount: 0,
        lastReset: new Date(),
      },
    });

    // 6. Ensure default shared utilities exist
    const defaultShared = ["current", "gas"];
    for (const type of defaultShared) {
      await tx.sharedUtility.upsert({
        where: { type },
        update: { amount: 0, lastReset: new Date() },
        create: { type, amount: 0, lastReset: new Date() },
      });
    }

    const persons = await tx.person.findMany({ orderBy: { id: "asc" } });

    return {
      deletedMeals: deletedMeals.count,
      deletedBazar: deletedBazar.count,
      deletedUtilities: deletedUtilities.count,
      deletedFunds: deletedFunds.count,
      personsCount: persons.length,
      persons: persons.map((p) => p.name),
    };
  });

  console.log("Reset completed successfully:");
  console.log(`- Deleted meals: ${result.deletedMeals}`);
  console.log(`- Deleted bazar items: ${result.deletedBazar}`);
  console.log(`- Reset utility records: ${result.deletedUtilities}`);
  console.log(`- Reset fund records: ${result.deletedFunds}`);
  console.log(`- Preserved ${result.personsCount} members: ${result.persons.join(", ")}`);

  await prisma.$disconnect();
}

resetDatabase().catch(async (e) => {
  console.error("Reset failed:", e);
  await prisma.$disconnect();
  process.exit(1);
});
