import { Router } from "express";
import { auth } from "../../shared/middlewares/checkAuth";
import { fineController } from "./fine.controller";

const router = Router();

// Public for dashboard
router.get("/monthly", (req, res) => fineController.getMonthly(req, res));

// Protected for authenticated users
router.get("/", auth(), (req, res) => fineController.getByDate(req, res));
router.get("/all", auth(), (req, res) => fineController.getAll(req, res));
router.post("/save-day", auth(), (req, res) => fineController.saveFines(req, res));
router.post("/", auth(), (req, res) => fineController.saveFines(req, res));
router.delete("/:id", auth(), (req, res) => fineController.deleteFine(req, res));

export default router;
