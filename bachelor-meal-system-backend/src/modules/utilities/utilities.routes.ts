import { Router } from "express";
import { utilitiesController } from "./utilities.controller";

const router = Router();

router.get("/", (req, res) => utilitiesController.getAll(req, res));
router.post("/expenses", (req, res) => utilitiesController.updateExpenses(req, res));
router.post("/fund", (req, res) => utilitiesController.updateFund(req, res));
router.post("/reset-expenses", (req, res) => utilitiesController.resetExpenses(req, res));
router.post("/shared/update", (req, res) => utilitiesController.updateShared(req, res));
router.post("/shared/reset", (req, res) => utilitiesController.resetShared(req, res));
router.post("/fund/reset", (req, res) => utilitiesController.resetCycle(req, res));

export default router;
