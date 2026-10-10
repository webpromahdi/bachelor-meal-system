"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.errorHandler = errorHandler;
function errorHandler(err, req, res, next) {
  console.error("Unhandled Error:", err);
  if (res.headersSent) {
    return next(err);
  }
  const statusCode = err.statusCode || err.status || 500;
  const message = err.message || "An unexpected error occurred.";
  return res.status(statusCode).json({
    success: false,
    error: message,
  });
}
