"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const utilities_controller_1 = require("./utilities.controller");
const router = (0, express_1.Router)();
router.get("/", (req, res) => utilities_controller_1.utilitiesController.getAll(req, res));
router.post("/expenses", (req, res) =>
  utilities_controller_1.utilitiesController.updateExpenses(req, res),
);
router.post("/fund", (req, res) => utilities_controller_1.utilitiesController.updateFund(req, res));
router.post("/reset-expenses", (req, res) =>
  utilities_controller_1.utilitiesController.resetExpenses(req, res),
);
router.post("/shared/update", (req, res) =>
  utilities_controller_1.utilitiesController.updateShared(req, res),
);
router.post("/shared/reset", (req, res) =>
  utilities_controller_1.utilitiesController.resetShared(req, res),
);
router.post("/fund/reset", (req, res) =>
  utilities_controller_1.utilitiesController.resetCycle(req, res),
);
exports.default = router;
