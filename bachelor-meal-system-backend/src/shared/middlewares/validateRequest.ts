import type { NextFunction, Request, Response } from "express";
import httpStatus from "http-status";
import type { ZodTypeAny } from "zod";
import { AppError } from "../utils/AppError";
import { catchAsync } from "../utils/catchAsync";

export const validateRequest = (zodSchema: ZodTypeAny) => {
  return catchAsync(async (req: Request, res: Response, next: NextFunction) => {
    const payload = req.body ?? {};
    const result = await zodSchema.safeParseAsync(payload);

    if (!result.success) {
      const errorMessage = result.error.issues[0]?.message || "Validation error";
      throw new AppError(httpStatus.BAD_REQUEST, errorMessage);
    }

    req.body = result.data;
    next();
  });
};
