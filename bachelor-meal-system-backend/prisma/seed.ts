import "dotenv/config";
import { PrismaPg } from "@prisma/adapter-pg";
import { PrismaClient } from "@prisma/client";
import { Pool } from "pg";

const pool = new Pool({ connectionString: process.env.DATABASE_URL });
const adapter = new PrismaPg(pool);
const prisma = new PrismaClient({ adapter });

const initialMembers = ["Mahdi", "Rafi", "Riyad", "Rakib", "Munna", "Saif", "Munjil"];

async function main() {
  console.log("Seeding database...");

  // Seed Members
  for (const name of initialMembers) {
    await prisma.person.upsert({
      where: { name },
      update: {},
      create: { name },
    });
  }
  console.log("Seeded persons.");

  // Seed Shared Utilities
  const sharedUtilities = ["current", "gas"];
  for (const type of sharedUtilities) {
    await prisma.sharedUtility.upsert({
      where: { type },
      update: {},
      create: {
        type,
        amount: 0,
        lastReset: new Date(),
      },
    });
  }
  console.log("Seeded shared utilities.");

  console.log("Database seeding completed.");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
