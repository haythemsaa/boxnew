import apiClient from './api';

/**
 * Issue Service - Gestion des signalements
 */
const issueService = {
  /**
   * Get all issues for the authenticated customer
   */
  getIssues: async () => {
    try {
      const response = await apiClient.get('/issues');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get issue details by ID
   */
  getIssue: async (id) => {
    try {
      const response = await apiClient.get(`/issues/${id}`);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Create new issue
   */
  createIssue: async (data) => {
    try {
      const response = await apiClient.post('/issues', data);
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default issueService;
