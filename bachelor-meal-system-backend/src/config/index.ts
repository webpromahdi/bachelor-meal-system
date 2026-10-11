import dotenv from "dotenv";
import path from "path";

dotenv.config({ path: path.join(process.cwd(), ".env") });

export const config = {
  node_env: process.env.NODE_ENV || "development",
  port: process.env.PORT || 5000,
  database_url: process.env.DATABASE_URL,
  direct_url: process.env.DIRECT_URL,
  bcrypt_salt_rounds: Number(process.env.BCRYPT_SALT_ROUNDS) || 10,
  jwt_access_secret: process.env.JWT_ACCESS_SECRET || "bachelor_meal_system_jwt_access_secret_key_2026_secure",
  jwt_refresh_secret: process.env.JWT_REFRESH_SECRET || "bachelor_meal_system_jwt_refresh_secret_key_2026_secure",
  jwt_access_expires_in: process.env.JWT_ACCESS_EXPIRES_IN || "1d",
  jwt_refresh_expires_in: process.env.JWT_REFRESH_EXPIRES_IN || "7d",
  frontend_url: process.env.FRONTEND_URL || "http://localhost:5173",
};

export default config;
