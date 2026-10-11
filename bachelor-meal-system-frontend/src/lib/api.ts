import axios from "axios"

const API_BASE_URL =
  import.meta.env.VITE_API_URL ||
  (import.meta.env.PROD
    ? "https://bachelor-meal-system-backend.vercel.app/api/v1"
    : "http://localhost:5000/api/v1")

export const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
  },
})

// Automatically attach JWT token & cache-buster timestamp parameter for GET requests
api.interceptors.request.use((config) => {
  // Prevent browser & proxy disk caching on GET requests
  if (config.method?.toLowerCase() === "get") {
    config.params = {
      ...config.params,
      _t: Date.now(),
    }
  }

  const token = localStorage.getItem("auth_token")
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Extract response data and handle session expiration
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      const isAuthCheck = error.config?.url?.includes("/auth/me")
      const isLogin = error.config?.url?.includes("/auth/login")
      if (!isAuthCheck && !isLogin) {
        localStorage.removeItem("auth_token")
        localStorage.removeItem("auth_user")
        window.dispatchEvent(new Event("auth:unauthorized"))
      }
    }
    return Promise.reject(error)
  },
)
