import { Router } from "express";
import authRouter from "../modules/auth/auth.routes";
import bazarRouter from "../modules/bazar/bazar.routes";
import fineRouter from "../modules/fine/fine.routes";
import mealsRouter from "../modules/meals/meals.routes";
import personsRouter from "../modules/persons/persons.routes";
import summaryRouter from "../modules/summary/summary.routes";
import utilitiesRouter from "../modules/utilities/utilities.routes";
import { auth } from "../shared/middlewares/checkAuth";

const router = Router();

// Public auth routes (login, profile, logout handle auth internally)
router.use("/auth", authRouter);

// Public summary routes for dashboard (e.g. GET /summary/monthly)
router.use("/summary", summaryRouter);

// Fine routes (GET /monthly is public for dashboard; other routes are protected inside fineRouter)
router.use("/fine", fineRouter);
router.use("/fines", fineRouter);

// Protected section routes — only authenticated users can access and update
router.use("/persons", auth(), personsRouter);
router.use("/meals", auth(), mealsRouter);
router.use("/bazar", auth(), bazarRouter);
router.use("/utilities", auth(), utilitiesRouter);

export default router;
