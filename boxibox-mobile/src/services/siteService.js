import apiClient from './api';

/**
 * Site Service - Gestion des sites
 */
const siteService = {
  /**
   * Get all sites
   */
  getSites: async () => {
    try {
      const response = await apiClient.get('/sites');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get site details by ID
   */
  getSite: async (id) => {
    try {
      const response = await apiClient.get(`/sites/${id}`);
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default siteService;
