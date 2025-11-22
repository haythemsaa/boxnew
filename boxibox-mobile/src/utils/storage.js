import * as SecureStore from 'expo-secure-store';

// Keys for secure storage
const KEYS = {
  AUTH_TOKEN: 'auth_token',
  USER_DATA: 'user_data',
};

/**
 * Store authentication token securely
 */
export const storeToken = async (token) => {
  try {
    await SecureStore.setItemAsync(KEYS.AUTH_TOKEN, token);
    return true;
  } catch (error) {
    console.error('Error storing token:', error);
    return false;
  }
};

/**
 * Get authentication token
 */
export const getToken = async () => {
  try {
    const token = await SecureStore.getItemAsync(KEYS.AUTH_TOKEN);
    return token;
  } catch (error) {
    console.error('Error getting token:', error);
    return null;
  }
};

/**
 * Remove authentication token
 */
export const removeToken = async () => {
  try {
    await SecureStore.deleteItemAsync(KEYS.AUTH_TOKEN);
    return true;
  } catch (error) {
    console.error('Error removing token:', error);
    return false;
  }
};

/**
 * Store user data securely
 */
export const storeUserData = async (userData) => {
  try {
    await SecureStore.setItemAsync(KEYS.USER_DATA, JSON.stringify(userData));
    return true;
  } catch (error) {
    console.error('Error storing user data:', error);
    return false;
  }
};

/**
 * Get user data
 */
export const getUserData = async () => {
  try {
    const data = await SecureStore.getItemAsync(KEYS.USER_DATA);
    return data ? JSON.parse(data) : null;
  } catch (error) {
    console.error('Error getting user data:', error);
    return null;
  }
};

/**
 * Remove user data
 */
export const removeUserData = async () => {
  try {
    await SecureStore.deleteItemAsync(KEYS.USER_DATA);
    return true;
  } catch (error) {
    console.error('Error removing user data:', error);
    return false;
  }
};

/**
 * Clear all stored data
 */
export const clearAll = async () => {
  try {
    await removeToken();
    await removeUserData();
    return true;
  } catch (error) {
    console.error('Error clearing storage:', error);
    return false;
  }
};
