import prisma from "../../shared/db/prisma";

export class PersonsService {
  async getAll() {
    return prisma.person.findMany({
      orderBy: { id: "asc" },
    });
  }

  async create(name: string) {
    return prisma.person.create({
      data: { name: name.trim() },
    });
  }

  async update(id: number, name: string) {
    return prisma.person.update({
      where: { id },
      data: { name: name.trim() },
    });
  }

  async delete(id: number) {
    return prisma.person.delete({
      where: { id },
    });
  }
}

export const personsService = new PersonsService();
