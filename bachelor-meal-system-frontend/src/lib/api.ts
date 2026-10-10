import axios from "axios"

const API_BASE_URL =
  import.meta.env.VITE_API_URL || "http://localhost:5000/api/v1"

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    "Content-Type": "application/json",
  },
})

// Generic response data extractor
api.interceptors.response.use(
  (response) => response.data,
  (error) => Promise.reject(error),
)
