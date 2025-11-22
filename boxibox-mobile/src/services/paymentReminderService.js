import apiClient from './api';

/**
 * Payment Reminder Service - Rappels de paiement
 */
const paymentReminderService = {
  /**
   * Get all payment reminders
   */
  getReminders: async () => {
    try {
      const response = await apiClient.get('/payment-reminders');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get reminder details by ID
   */
  getReminder: async (id) => {
    try {
      const response = await apiClient.get(`/payment-reminders/${id}`);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Acknowledge reminder
   */
  acknowledgeReminder: async (id) => {
    try {
      const response = await apiClient.post(`/payment-reminders/${id}/acknowledge`);
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default paymentReminderService;
