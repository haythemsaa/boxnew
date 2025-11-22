import apiClient from './api';

/**
 * Contract Service
 */
const contractService = {
  /**
   * Get all contracts for the authenticated customer
   */
  getContracts: async () => {
    try {
      const response = await apiClient.get('/contracts');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get contract details by ID
   */
  getContract: async (id) => {
    try {
      const response = await apiClient.get(`/contracts/${id}`);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Download contract PDF
   */
  downloadContractPDF: async (id) => {
    try {
      const response = await apiClient.get(`/contracts/${id}/pdf`, {
        responseType: 'blob',
      });
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Request contract termination
   */
  requestTermination: async (id, data) => {
    try {
      const response = await apiClient.post(`/contracts/${id}/request-termination`, data);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get termination requests
   */
  getTerminationRequests: async () => {
    try {
      const response = await apiClient.get('/contracts/termination-requests');
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default contractService;
