import { Router } from "express";
import { auth } from "../../shared/middlewares/checkAuth";
import { validateRequest } from "../../shared/middlewares/validateRequest";
import { authController } from "./auth.controller";
import { loginSchema } from "./auth.validation";

const router = Router();

router.post("/login", validateRequest(loginSchema), authController.loginUser);
router.get("/me", auth(), authController.getMe);
router.post("/logout", auth(), authController.logout);

export default router;
