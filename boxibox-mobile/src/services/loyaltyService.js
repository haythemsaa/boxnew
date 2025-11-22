import apiClient from './api';

/**
 * Loyalty Service - Programme de fidélité
 */
const loyaltyService = {
  /**
   * Get loyalty points balance
   */
  getBalance: async () => {
    try {
      const response = await apiClient.get('/loyalty/balance');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get loyalty points history
   */
  getHistory: async () => {
    try {
      const response = await apiClient.get('/loyalty/history');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get loyalty program info
   */
  getProgramInfo: async () => {
    try {
      const response = await apiClient.get('/loyalty/info');
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default loyaltyService;
