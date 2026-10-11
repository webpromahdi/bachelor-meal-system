import bcrypt from "bcryptjs";
import passport from "passport";
import { Strategy as LocalStrategy } from "passport-local";
import prisma from "../shared/db/prisma";

export interface IAuthenticatedUser {
  id: number;
  userId: number;
  email: string;
  name: string;
  role: string;
}

passport.use(
  new LocalStrategy(
    {
      usernameField: "email",
      passwordField: "password",
    },
    async (email, password, done) => {
      try {
        const normalizedEmail = email ? email.trim().toLowerCase() : "";
        const user = await prisma.user.findUnique({
          where: { email: normalizedEmail },
        });

        if (!user) {
          return done(null, false, {
            message: "User does not exist with this email!",
          });
        }

        const isPasswordMatch = await bcrypt.compare(password, user.password);

        if (!isPasswordMatch) {
          return done(null, false, {
            message: "Password does not match!",
          });
        }

        const userPayload: IAuthenticatedUser = {
          id: user.id,
          userId: user.id,
          email: user.email,
          name: user.name,
          role: user.role,
        };

        return done(null, userPayload);
      } catch (error) {
        return done(error);
      }
    },
  ),
);

export default passport;
