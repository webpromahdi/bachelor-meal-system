import cors from "cors";
import express from "express";
import apiRouter from "./routes";
import { errorHandler } from "./shared/middlewares/errorHandler";
import { notFoundHandler } from "./shared/middlewares/notFound";

const app = express();

app.use(cors());
app.use(express.json());

// Root health check
app.get("/", (req, res) => {
  res.json({ message: "Bachelor Meal System API is running" });
});

// Mount modular API v1 routes
app.use("/api/v1", apiRouter);

// 404 Fallback
app.use(notFoundHandler);

// Centralized error handler
app.use(errorHandler);

export default app;
