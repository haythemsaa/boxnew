import api from './api';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import { Platform } from 'react-native';
import Constants from 'expo-constants';

// Configure how notifications should be displayed
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

/**
 * Request notification permissions from the user
 */
export const requestPermissions = async () => {
  try {
    if (!Device.isDevice) {
      console.log('Push notifications only work on physical devices');
      return false;
    }

    const { status: existingStatus } = await Notifications.getPermissionsAsync();
    let finalStatus = existingStatus;

    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }

    if (finalStatus !== 'granted') {
      console.log('Failed to get push notification permission');
      return false;
    }

    return true;
  } catch (error) {
    console.error('Error requesting notification permissions:', error);
    return false;
  }
};

/**
 * Get the Expo push token for this device
 */
export const getExpoPushToken = async () => {
  try {
    if (!Device.isDevice) {
      console.log('Push notifications only work on physical devices');
      return null;
    }

    const token = await Notifications.getExpoPushTokenAsync({
      projectId: Constants.expoConfig?.extra?.eas?.projectId || 'your-project-id',
    });

    return token.data;
  } catch (error) {
    console.error('Error getting Expo push token:', error);
    return null;
  }
};

/**
 * Get device information
 */
const getDeviceInfo = () => {
  return {
    device_name: Device.deviceName || 'Unknown Device',
    device_model: Device.modelName || 'Unknown Model',
    platform: Platform.OS,
    app_version: Constants.expoConfig?.version || '1.0.0',
  };
};

/**
 * Register push notification token with the backend
 */
export const registerToken = async (token) => {
  try {
    const deviceInfo = getDeviceInfo();

    const response = await api.post('/notifications/register-token', {
      token,
      ...deviceInfo,
    });

    return response.data;
  } catch (error) {
    console.error('Error registering push token:', error);
    throw error;
  }
};

/**
 * Unregister push notification token from the backend
 */
export const unregisterToken = async (token) => {
  try {
    const response = await api.post('/notifications/unregister-token', {
      token,
    });

    return response.data;
  } catch (error) {
    console.error('Error unregistering push token:', error);
    throw error;
  }
};

/**
 * Get all registered tokens for the current user
 */
export const getTokens = async () => {
  try {
    const response = await api.get('/notifications/tokens');
    return response.data;
  } catch (error) {
    console.error('Error fetching notification tokens:', error);
    throw error;
  }
};

/**
 * Get notification preferences
 */
export const getPreferences = async () => {
  try {
    const response = await api.get('/notifications/preferences');
    return response.data;
  } catch (error) {
    console.error('Error fetching notification preferences:', error);
    throw error;
  }
};

/**
 * Update notification preferences
 */
export const updatePreferences = async (preferences) => {
  try {
    const response = await api.put('/notifications/preferences', preferences);
    return response.data;
  } catch (error) {
    console.error('Error updating notification preferences:', error);
    throw error;
  }
};

/**
 * Initialize push notifications
 * Call this when the app starts and user is logged in
 */
export const initializePushNotifications = async () => {
  try {
    // Request permissions
    const hasPermission = await requestPermissions();
    if (!hasPermission) {
      return { success: false, message: 'Permission denied' };
    }

    // Get push token
    const token = await getExpoPushToken();
    if (!token) {
      return { success: false, message: 'Failed to get token' };
    }

    // Register token with backend
    const result = await registerToken(token);

    return { success: true, token, ...result };
  } catch (error) {
    console.error('Error initializing push notifications:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Handle notification received while app is in foreground
 */
export const addNotificationReceivedListener = (callback) => {
  return Notifications.addNotificationReceivedListener(callback);
};

/**
 * Handle notification response (user tapped on notification)
 */
export const addNotificationResponseListener = (callback) => {
  return Notifications.addNotificationResponseReceivedListener(callback);
};

/**
 * Schedule a local notification (for testing)
 */
export const scheduleLocalNotification = async (title, body, data = {}) => {
  try {
    await Notifications.scheduleNotificationAsync({
      content: {
        title,
        body,
        data,
        sound: true,
      },
      trigger: null, // Show immediately
    });
  } catch (error) {
    console.error('Error scheduling local notification:', error);
  }
};

/**
 * Cancel all notifications
 */
export const cancelAllNotifications = async () => {
  try {
    await Notifications.cancelAllScheduledNotificationsAsync();
  } catch (error) {
    console.error('Error canceling notifications:', error);
  }
};

/**
 * Get notification badge count
 */
export const getBadgeCount = async () => {
  try {
    return await Notifications.getBadgeCountAsync();
  } catch (error) {
    console.error('Error getting badge count:', error);
    return 0;
  }
};

/**
 * Set notification badge count
 */
export const setBadgeCount = async (count) => {
  try {
    await Notifications.setBadgeCountAsync(count);
  } catch (error) {
    console.error('Error setting badge count:', error);
  }
};

/**
 * Clear all notifications
 */
export const clearAllNotifications = async () => {
  try {
    await Notifications.dismissAllNotificationsAsync();
    await setBadgeCount(0);
  } catch (error) {
    console.error('Error clearing notifications:', error);
  }
};

export default {
  requestPermissions,
  getExpoPushToken,
  registerToken,
  unregisterToken,
  getTokens,
  getPreferences,
  updatePreferences,
  initializePushNotifications,
  addNotificationReceivedListener,
  addNotificationResponseListener,
  scheduleLocalNotification,
  cancelAllNotifications,
  getBadgeCount,
  setBadgeCount,
  clearAllNotifications,
};
