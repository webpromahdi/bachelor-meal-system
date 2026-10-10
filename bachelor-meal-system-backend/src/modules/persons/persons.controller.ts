import type { Request, Response } from "express";
import { apiResponse } from "../../shared/utils/apiResponse";
import { personsService } from "./persons.service";

export class PersonsController {
  async getAll(req: Request, res: Response) {
    try {
      const persons = await personsService.getAll();
      return apiResponse.success(res, persons);
    } catch (error) {
      return apiResponse.error(res, "Failed to fetch persons.", 500);
    }
  }

  async create(req: Request, res: Response) {
    const { name } = req.body;
    if (!name || typeof name !== "string" || !name.trim()) {
      return apiResponse.error(res, "Member name is required.", 400);
    }

    try {
      const person = await personsService.create(name);
      return apiResponse.success(res, person, {
        message: "Member added successfully.",
      });
    } catch (error: any) {
      if (error?.code === "P2002") {
        return apiResponse.error(res, "A member with this name already exists.", 400);
      }
      return apiResponse.error(res, "Failed to add member.", 500);
    }
  }

  async update(req: Request, res: Response) {
    const { id } = req.params;
    const { name } = req.body;

    if (!name || typeof name !== "string" || !name.trim()) {
      return apiResponse.error(res, "Member name is required.", 400);
    }

    const personId = parseInt(String(id));
    if (isNaN(personId)) {
      return apiResponse.error(res, "Invalid member ID.", 400);
    }

    try {
      const person = await personsService.update(personId, name);
      return apiResponse.success(res, person, {
        message: "Member updated successfully.",
      });
    } catch (error) {
      return apiResponse.error(res, "Failed to update member.", 500);
    }
  }

  async delete(req: Request, res: Response) {
    const { id } = req.params;
    const personId = parseInt(String(id));
    if (isNaN(personId)) {
      return apiResponse.error(res, "Invalid member ID.", 400);
    }

    try {
      await personsService.delete(personId);
      return apiResponse.success(res, undefined, {
        message: "Member deleted successfully.",
      });
    } catch (error) {
      return apiResponse.error(res, "Failed to delete member.", 500);
    }
  }
}

export const personsController = new PersonsController();
