import axios from "axios"

const API_BASE_URL =
  import.meta.env.VITE_API_URL || "http://localhost:5000/api/v1"

export const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
  },
})

// Automatically attach JWT token from localStorage if present
api.interceptors.request.use((config) => {
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
