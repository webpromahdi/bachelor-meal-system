import type { NextFunction, Request, Response } from "express";
import httpStatus from "http-status";
import config from "../../config";
import passport, { type IAuthenticatedUser } from "../../config/passport";
import { AppError } from "../../shared/utils/AppError";
import { apiResponse } from "../../shared/utils/apiResponse";
import { catchAsync } from "../../shared/utils/catchAsync";
import { authService } from "./auth.service";

const loginUser = catchAsync(
	async (req: Request, res: Response, next: NextFunction) => {
		passport.authenticate(
			"local",
			async (
				err: Error | null,
				user: IAuthenticatedUser | false,
				info: { message?: string } | undefined,
			) => {
				try {
					if (err) {
						return next(err);
					}
					if (!user) {
						return next(
							new AppError(
								httpStatus.UNAUTHORIZED,
								info?.message || "Invalid credentials!",
							),
						);
					}

					const result = await authService.loginUser(user);
					const isProduction = config.node_env === "production";

					res.cookie("accessToken", result.accessToken, {
						httpOnly: true,
						secure: isProduction,
						sameSite: isProduction ? "none" : "lax",
						maxAge: 1000 * 60 * 60 * 24, // 24 hours
					});

					res.cookie("refreshToken", result.refreshToken, {
						httpOnly: true,
						secure: isProduction,
						sameSite: isProduction ? "none" : "lax",
						maxAge: 1000 * 60 * 60 * 24 * 7, // 7 days
					});

					return apiResponse.success(res, result, {
						statusCode: httpStatus.OK,
						message: "User logged in successfully",
					});
				} catch (error) {
					return next(error);
				}
			},
		)(req, res, next);
	},
);

const getMe = catchAsync(async (req: Request, res: Response) => {
	if (!req.user) {
		throw new AppError(httpStatus.UNAUTHORIZED, "User not authenticated");
	}
	const userId = req.user.id;
	const user = await authService.getMe(userId);

	return apiResponse.success(res, user, {
		statusCode: httpStatus.OK,
		message: "User profile retrieved successfully",
	});
});

const logout = catchAsync(async (req: Request, res: Response) => {
	const isProduction = config.node_env === "production";

	res.clearCookie("accessToken", {
		httpOnly: true,
		secure: isProduction,
		sameSite: isProduction ? "none" : "lax",
	});

	res.clearCookie("refreshToken", {
		httpOnly: true,
		secure: isProduction,
		sameSite: isProduction ? "none" : "lax",
	});

	return apiResponse.success(res, null, {
		statusCode: httpStatus.OK,
		message: "User logged out successfully",
	});
});

export const authController = {
	loginUser,
	getMe,
	logout,
};
