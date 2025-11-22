import apiClient from './api';

/**
 * Promotion Service - Gestion des promotions
 */
const promotionService = {
  /**
   * Get all active promotions
   */
  getPromotions: async () => {
    try {
      const response = await apiClient.get('/promotions');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Validate promo code
   */
  validatePromoCode: async (code) => {
    try {
      const response = await apiClient.post('/promotions/validate', { code });
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default promotionService;
