import apiClient from './api';

/**
 * Reservation Service - Gestion des réservations
 */
const reservationService = {
  /**
   * Search available boxes
   */
  searchBoxes: async (searchParams) => {
    try {
      const response = await apiClient.post('/boxes/search', searchParams);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Calculate price for a reservation
   */
  calculatePrice: async (data) => {
    try {
      const response = await apiClient.post('/boxes/calculate-price', data);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Create new reservation
   */
  createReservation: async (data) => {
    try {
      const response = await apiClient.post('/reservations', data);
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Get all reservations
   */
  getReservations: async () => {
    try {
      const response = await apiClient.get('/reservations');
      return response;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Cancel reservation
   */
  cancelReservation: async (id) => {
    try {
      const response = await apiClient.post(`/reservations/${id}/cancel`);
      return response;
    } catch (error) {
      throw error;
    }
  },
};

export default reservationService;
