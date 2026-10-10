"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.apiResponse = void 0;
exports.apiResponse = {
  success(res, data, options = {}) {
    const { statusCode = 200, message, ...extra } = options;
    const body = { success: true };
    if (message !== undefined) {
      body.message = message;
    }
    if (data !== undefined) {
      body.data = data;
    }
    Object.assign(body, extra);
    return res.status(statusCode).json(body);
  },
  error(res, error, statusCode = 500, extra = {}) {
    return res.status(statusCode).json({
      success: false,
      error,
      ...extra,
    });
  },
};
