"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
const cors_1 = __importDefault(require("cors"));
const express_1 = __importDefault(require("express"));
const routes_1 = __importDefault(require("./routes"));
const errorHandler_1 = require("./shared/middlewares/errorHandler");
const notFound_1 = require("./shared/middlewares/notFound");
const app = (0, express_1.default)();
app.use((0, cors_1.default)());
app.use(express_1.default.json());
// Root health check
app.get("/", (req, res) => {
  res.json({ message: "Bachelor Meal System API is running" });
});
// Mount modular API v1 routes
app.use("/api/v1", routes_1.default);
// 404 Fallback
app.use(notFound_1.notFoundHandler);
// Centralized error handler
app.use(errorHandler_1.errorHandler);
exports.default = app;
