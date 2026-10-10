"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.personsService = exports.PersonsService = void 0;
const prisma_1 = __importDefault(require("../../shared/db/prisma"));
class PersonsService {
  async getAll() {
    return prisma_1.default.person.findMany({
      orderBy: { id: "asc" },
    });
  }
  async create(name) {
    return prisma_1.default.person.create({
      data: { name: name.trim() },
    });
  }
  async update(id, name) {
    return prisma_1.default.person.update({
      where: { id },
      data: { name: name.trim() },
    });
  }
  async delete(id) {
    return prisma_1.default.person.delete({
      where: { id },
    });
  }
}
exports.PersonsService = PersonsService;
exports.personsService = new PersonsService();
