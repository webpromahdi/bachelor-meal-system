import type { Response } from "express";

export interface ApiResponseOptions {
  message?: string;
  statusCode?: number;
  [key: string]: any;
}

export const apiResponse = {
  success<T>(res: Response, data?: T, options: ApiResponseOptions = {}) {
    const { statusCode = 200, message, ...extra } = options;
    const body: Record<string, any> = { success: true };

    if (message !== undefined) {
      body.message = message;
    }
    if (data !== undefined) {
      body.data = data;
    }

    Object.assign(body, extra);
    return res.status(statusCode).json(body);
  },

  error(res: Response, error: string, statusCode = 500, extra: Record<string, any> = {}) {
    return res.status(statusCode).json({
      success: false,
      error,
      ...extra,
    });
  },
};
