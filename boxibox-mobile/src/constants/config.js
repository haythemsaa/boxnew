// API Configuration
export const API_CONFIG = {
  // Change this to your actual API URL
  BASE_URL: __DEV__
    ? 'http://localhost:8000/api/v1'  // Development
    : 'https://api.boxibox.fr/api/v1', // Production

  TIMEOUT: 30000, // 30 seconds
};

// App Configuration
export const APP_CONFIG = {
  APP_NAME: 'Boxibox',
  VERSION: '1.0.0',
};
