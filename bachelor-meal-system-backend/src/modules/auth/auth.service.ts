import httpStatus from "http-status";
import config from "../../config";
import type { IAuthenticatedUser } from "../../config/passport";
import prisma from "../../shared/db/prisma";
import { AppError } from "../../shared/utils/AppError";
import { jwtUtils } from "../../shared/utils/jwt";

const loginUser = async (user: IAuthenticatedUser) => {
  if (!user) {
    throw new AppError(httpStatus.UNAUTHORIZED, "Invalid email or password");
  }

  const userId = user.id || user.userId;

  const jwtPayload = {
    id: userId,
    userId,
    name: user.name,
    email: user.email,
    role: user.role,
  };

  const accessToken = jwtUtils.createToken(
    jwtPayload,
    config.jwt_access_secret,
    config.jwt_access_expires_in,
  );

  const refreshToken = jwtUtils.createToken(
    jwtPayload,
    config.jwt_refresh_secret,
    config.jwt_refresh_expires_in,
  );

  return {
    accessToken,
    refreshToken,
    user: {
      id: userId,
      email: user.email,
      name: user.name,
      role: user.role,
    },
  };
};

const getMe = async (userId: number) => {
  const user = await prisma.user.findUnique({
    where: { id: userId },
    select: {
      id: true,
      name: true,
      email: true,
      role: true,
      createdAt: true,
      updatedAt: true,
    },
  });

  if (!user) {
    throw new AppError(httpStatus.NOT_FOUND, "User not found");
  }

  return user;
};

export const authService = {
  loginUser,
  getMe,
};
