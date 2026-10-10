import { Router } from "express";
import { mealsController } from "./meals.controller";

const router = Router();

router.get("/", (req, res) => mealsController.getByDate(req, res));
router.post("/save-day", (req, res) => mealsController.saveMeals(req, res));
router.post("/upsert", (req, res) => mealsController.saveMeals(req, res));
router.post("/", (req, res) => mealsController.saveMeals(req, res));

export default router;
