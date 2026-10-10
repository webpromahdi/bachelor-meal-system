import { Router } from "express";
import bazarRouter from "../modules/bazar/bazar.routes";
import fineRouter from "../modules/fine/fine.routes";
import mealsRouter from "../modules/meals/meals.routes";
import personsRouter from "../modules/persons/persons.routes";
import summaryRouter from "../modules/summary/summary.routes";
import utilitiesRouter from "../modules/utilities/utilities.routes";

const router = Router();

router.use("/persons", personsRouter);
router.use("/meals", mealsRouter);
router.use("/bazar", bazarRouter);
router.use("/fine", fineRouter);
router.use("/fines", fineRouter);
router.use("/utilities", utilitiesRouter);
router.use("/summary", summaryRouter);

export default router;
