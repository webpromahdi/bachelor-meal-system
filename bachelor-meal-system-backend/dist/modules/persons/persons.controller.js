"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.personsController = exports.PersonsController = void 0;
const apiResponse_1 = require("../../shared/utils/apiResponse");
const persons_service_1 = require("./persons.service");
class PersonsController {
  async getAll(req, res) {
    try {
      const persons = await persons_service_1.personsService.getAll();
      return apiResponse_1.apiResponse.success(res, persons);
    } catch (error) {
      return apiResponse_1.apiResponse.error(res, "Failed to fetch persons.", 500);
    }
  }
  async create(req, res) {
    const { name } = req.body;
    if (!name || typeof name !== "string" || !name.trim()) {
      return apiResponse_1.apiResponse.error(res, "Member name is required.", 400);
    }
    try {
      const person = await persons_service_1.personsService.create(name);
      return apiResponse_1.apiResponse.success(res, person, {
        message: "Member added successfully.",
      });
    } catch (error) {
      if (error?.code === "P2002") {
        return apiResponse_1.apiResponse.error(res, "A member with this name already exists.", 400);
      }
      return apiResponse_1.apiResponse.error(res, "Failed to add member.", 500);
    }
  }
  async update(req, res) {
    const { id } = req.params;
    const { name } = req.body;
    if (!name || typeof name !== "string" || !name.trim()) {
      return apiResponse_1.apiResponse.error(res, "Member name is required.", 400);
    }
    const personId = parseInt(String(id));
    if (isNaN(personId)) {
      return apiResponse_1.apiResponse.error(res, "Invalid member ID.", 400);
    }
    try {
      const person = await persons_service_1.personsService.update(personId, name);
      return apiResponse_1.apiResponse.success(res, person, {
        message: "Member updated successfully.",
      });
    } catch (error) {
      return apiResponse_1.apiResponse.error(res, "Failed to update member.", 500);
    }
  }
  async delete(req, res) {
    const { id } = req.params;
    const personId = parseInt(String(id));
    if (isNaN(personId)) {
      return apiResponse_1.apiResponse.error(res, "Invalid member ID.", 400);
    }
    try {
      await persons_service_1.personsService.delete(personId);
      return apiResponse_1.apiResponse.success(res, undefined, {
        message: "Member deleted successfully.",
      });
    } catch (error) {
      return apiResponse_1.apiResponse.error(res, "Failed to delete member.", 500);
    }
  }
}
exports.PersonsController = PersonsController;
exports.personsController = new PersonsController();
