"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const bazar_routes_1 = __importDefault(require("../modules/bazar/bazar.routes"));
const meals_routes_1 = __importDefault(require("../modules/meals/meals.routes"));
const persons_routes_1 = __importDefault(require("../modules/persons/persons.routes"));
const summary_routes_1 = __importDefault(require("../modules/summary/summary.routes"));
const utilities_routes_1 = __importDefault(require("../modules/utilities/utilities.routes"));
const router = (0, express_1.Router)();
router.use("/persons", persons_routes_1.default);
router.use("/meals", meals_routes_1.default);
router.use("/bazar", bazar_routes_1.default);
router.use("/utilities", utilities_routes_1.default);
router.use("/summary", summary_routes_1.default);
exports.default = router;
