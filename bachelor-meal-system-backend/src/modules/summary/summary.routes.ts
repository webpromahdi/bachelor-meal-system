import { Router } from "express";
import { summaryController } from "./summary.controller";

const router = Router();

router.get("/monthly", (req, res) => summaryController.getMonthly(req, res));

export default router;
