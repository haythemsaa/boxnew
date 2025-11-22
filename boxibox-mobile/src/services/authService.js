import apiClient from './api';

/**
 * Authentication Service
 */
const authService = {
  /**
   * Login user
   */
  login: async (email, password) => {
    try {
      const response = await apiClient.post('/auth/login', {
        email,
        password,
      });
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Register new user
   */
  register: async (userData) => {
    try {
      const response = await apiClient.post('/auth/register', userData);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Logout user
   */
  logout: async () => {
    try {
      const response = await apiClient.post('/auth/logout');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get current user
   */
  getUser: async () => {
    try {
      const response = await apiClient.get('/auth/user');
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default authService;
