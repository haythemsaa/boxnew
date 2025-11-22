import axios from 'axios';
import { API_CONFIG } from '../constants/config';
import { getToken } from '../utils/storage';

// Create axios instance
const apiClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Add auth token to requests
apiClient.interceptors.request.use(
  async (config) => {
    const token = await getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => {
    return response.data;
  },
  (error) => {
    // Handle different error scenarios
    if (error.response) {
      // Server responded with error
      const { status, data } = error.response;

      switch (status) {
        case 401:
          // Unauthorized - token expired or invalid
          console.error('Unauthorized access - please login again');
          break;
        case 403:
          // Forbidden
          console.error('Access forbidden');
          break;
        case 404:
          // Not found
          console.error('Resource not found');
          break;
        case 422:
          // Validation error
          console.error('Validation error:', data.errors);
          break;
        case 500:
          // Server error
          console.error('Server error');
          break;
        default:
          console.error('API Error:', data.message || 'Unknown error');
      }

      return Promise.reject({
        status,
        message: data.message || 'An error occurred',
        errors: data.errors || {},
      });
    } else if (error.request) {
      // Request made but no response
      console.error('Network error - no response received');
      return Promise.reject({
        status: 0,
        message: 'Network error - please check your connection',
        errors: {},
      });
    } else {
      // Error setting up request
      console.error('Request error:', error.message);
      return Promise.reject({
        status: 0,
        message: error.message || 'An error occurred',
        errors: {},
      });
    }
  }
);

export default apiClient;
