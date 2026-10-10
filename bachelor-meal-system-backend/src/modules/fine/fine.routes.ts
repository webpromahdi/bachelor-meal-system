import { Router } from "express";
import { fineController } from "./fine.controller";

const router = Router();

router.get("/", (req, res) => fineController.getByDate(req, res));
router.post("/save-day", (req, res) => fineController.saveFines(req, res));
router.post("/", (req, res) => fineController.saveFines(req, res));
router.get("/monthly", (req, res) => fineController.getMonthly(req, res));
router.get("/all", (req, res) => fineController.getAll(req, res));
router.delete("/:id", (req, res) => fineController.deleteFine(req, res));

export default router;
