import type { NextFunction, Request, Response } from "express";
import httpStatus from "http-status";
import type { JwtPayload } from "jsonwebtoken";
import config from "../../config";
import prisma from "../db/prisma";
import { AppError } from "../utils/AppError";
import { catchAsync } from "../utils/catchAsync";
import { jwtUtils } from "../utils/jwt";

export interface RequestUser {
  id: number;
  email: string;
  name: string;
  role: string;
}

declare global {
  namespace Express {
    interface User extends RequestUser {}
  }
}

export const auth = (...requiredRoles: string[]) => {
  return catchAsync(async (req: Request, res: Response, next: NextFunction) => {
    const token = req.cookies?.accessToken
      ? req.cookies.accessToken
      : req.headers.authorization?.startsWith("Bearer ")
        ? req.headers.authorization?.split(" ")[1]
        : req.headers.authorization;

    if (!token) {
      throw new AppError(
        httpStatus.UNAUTHORIZED,
        "You are not logged in. Please log in to access this resource.",
      );
    }

    const verifiedToken = jwtUtils.verifyToken(token, config.jwt_access_secret);

    if (!verifiedToken.success) {
      const jwtError = verifiedToken.error ?? "";
      let friendlyMessage = "You are not logged in. Please log in to access this resource.";

      if (jwtError.includes("expired")) {
        friendlyMessage = "Your session has expired. Please log in again.";
      } else if (
        jwtError.includes("malformed") ||
        jwtError.includes("invalid") ||
        jwtError.includes("signature")
      ) {
        friendlyMessage = "Invalid token. Please log in again.";
      }

      throw new AppError(httpStatus.UNAUTHORIZED, friendlyMessage);
    }

    const payload = verifiedToken.data as JwtPayload;
    const userId = Number(payload.id ?? payload.userId);

    if (!userId) {
      throw new AppError(httpStatus.UNAUTHORIZED, "Invalid token payload.");
    }

    const user = await prisma.user.findUnique({
      where: { id: userId },
    });

    if (!user) {
      throw new AppError(
        httpStatus.UNAUTHORIZED,
        "User not found. Please log in again.",
      );
    }

    if (requiredRoles.length && !requiredRoles.includes(user.role)) {
      throw new AppError(
        httpStatus.FORBIDDEN,
        "Forbidden. You don't have permission to access this resource.",
      );
    }

    req.user = {
      id: user.id,
      email: user.email,
      name: user.name,
      role: user.role,
    };

    next();
  });
};
