"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const summary_controller_1 = require("./summary.controller");
const router = (0, express_1.Router)();
router.get("/monthly", (req, res) => summary_controller_1.summaryController.getMonthly(req, res));
exports.default = router;
