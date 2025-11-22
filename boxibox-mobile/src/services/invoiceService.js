import apiClient from './api';

/**
 * Invoice Service
 */
const invoiceService = {
  /**
   * Get all invoices for the authenticated customer
   */
  getInvoices: async () => {
    try {
      const response = await apiClient.get('/invoices');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get invoice details by ID
   */
  getInvoice: async (id) => {
    try {
      const response = await apiClient.get(`/invoices/${id}`);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Download invoice PDF
   */
  downloadInvoicePDF: async (id) => {
    try {
      const response = await apiClient.get(`/invoices/${id}/pdf`, {
        responseType: 'blob',
      });
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default invoiceService;
