"use strict";
var __importDefault =
  (this && this.__importDefault) ||
  function (mod) {
    return mod && mod.__esModule ? mod : { default: mod };
  };
Object.defineProperty(exports, "__esModule", { value: true });
exports.default = void 0;
var persons_routes_1 = require("../modules/persons/persons.routes");
Object.defineProperty(exports, "default", {
  enumerable: true,
  get: function () {
    return __importDefault(persons_routes_1).default;
  },
});
