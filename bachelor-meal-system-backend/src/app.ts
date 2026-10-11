import cookieParser from "cookie-parser";
import cors from "cors";
import express from "express";
import passport from "passport";
import "./config/passport";
import apiRouter from "./routes";
import { errorHandler } from "./shared/middlewares/errorHandler";
import { notFoundHandler } from "./shared/middlewares/notFound";

const app = express();

app.use(
  cors({
    origin: (origin, callback) => {
      // Allow requests with no origin (curl, postman, mobile) or dynamically mirror caller origin
      callback(null, true);
    },
    credentials: true,
    methods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allowedHeaders: [
      "Content-Type",
      "Authorization",
      "Cache-Control",
      "Pragma",
      "Expires",
      "X-Requested-With",
    ],
  }),
);
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());
app.use(passport.initialize());

// Prevent response caching across all API endpoints
app.use((req, res, next) => {
  res.setHeader(
    "Cache-Control",
    "no-store, no-cache, must-revalidate, proxy-revalidate",
  );
  res.setHeader("Pragma", "no-cache");
  res.setHeader("Expires", "0");
  next();
});

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
