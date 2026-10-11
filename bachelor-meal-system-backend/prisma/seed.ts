import bcrypt from "bcryptjs";
import prisma from "../src/shared/db/prisma";

async function main() {
  console.log("=== SEEDING DATABASE ===");

  const adminName = process.env.ADMIN_NAME || "Admin";
  const adminEmail = (process.env.ADMIN_EMAIL || "admin@mealsystem.local").trim().toLowerCase();
  const adminPassword = process.env.ADMIN_PASSWORD || "admin123456";
  const saltRounds = Number(process.env.BCRYPT_SALT_ROUNDS) || 10;

  const hashedPassword = await bcrypt.hash(adminPassword, saltRounds);

  const user = await prisma.user.upsert({
    where: { email: adminEmail },
    update: {
      name: adminName,
      password: hashedPassword,
      role: "ADMIN",
    },
    create: {
      email: adminEmail,
      name: adminName,
      password: hashedPassword,
      role: "ADMIN",
    },
  });

  console.log(`- Preconfigured User seeded successfully:`);
  console.log(`  ID: ${user.id}`);
  console.log(`  Email: ${user.email}`);
  console.log(`  Name: ${user.name}`);
  console.log(`  Role: ${user.role}`);
  console.log(`  Password configured: [PROTECTED]`);

  // Ensure default shared utilities exist
  const defaultShared = ["current", "gas"];
  for (const type of defaultShared) {
    await prisma.sharedUtility.upsert({
      where: { type },
      update: {},
      create: { type, amount: 0, lastReset: new Date() },
    });
  }
  console.log("- Shared utility records verified.");

  console.log("=== SEEDING COMPLETED ===");
}

main()
  .catch((e) => {
    console.error("Seeding failed:", e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
