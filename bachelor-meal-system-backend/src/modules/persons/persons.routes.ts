import { Router } from "express";
import { personsController } from "./persons.controller";

const router = Router();

router.get("/", (req, res) => personsController.getAll(req, res));
router.post("/", (req, res) => personsController.create(req, res));
router.put("/:id", (req, res) => personsController.update(req, res));
router.delete("/:id", (req, res) => personsController.delete(req, res));

export default router;
