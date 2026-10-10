import { Router } from "express";
import { bazarController } from "./bazar.controller";

const router = Router();

router.get("/", (req, res) => bazarController.getByDate(req, res));
router.get("/person", (req, res) => bazarController.getByPerson(req, res));
router.post("/upsert", (req, res) => bazarController.saveBazar(req, res));
router.post("/", (req, res) => bazarController.saveBazar(req, res));

export default router;
