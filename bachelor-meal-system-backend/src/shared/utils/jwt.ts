import jwt, { type JwtPayload, type SignOptions } from "jsonwebtoken";

const createToken = (
  payload: JwtPayload,
  secret: string,
  expiresIn: string | number,
) => {
  const token = jwt.sign(payload, secret, {
    expiresIn,
  } as SignOptions);

  return token;
};

const verifyToken = (token: string, secret: string) => {
  try {
    const verifiedToken = jwt.verify(token, secret);
    return {
      success: true,
      data: verifiedToken as JwtPayload,
    };
  } catch (error: any) {
    return {
      success: false,
      error: error?.message || "Invalid token",
    };
  }
};

export const jwtUtils = {
  createToken,
  verifyToken,
};
