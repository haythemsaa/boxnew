import apiClient from './api';

/**
 * Customer Service
 */
const customerService = {
  /**
   * Get customer profile
   */
  getProfile: async () => {
    try {
      const response = await apiClient.get('/auth/user');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Update customer profile
   */
  updateProfile: async (data) => {
    try {
      const response = await apiClient.put('/profile', data);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Update password
   */
  updatePassword: async (data) => {
    try {
      const response = await apiClient.put('/profile/password', data);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get customer statistics
   */
  getStatistics: async () => {
    try {
      const response = await apiClient.get('/profile/statistics');
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default customerService;
