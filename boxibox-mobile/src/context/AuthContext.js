import React, { createContext, useState, useContext, useEffect } from 'react';
import authService from '../services/authService';
import { storeToken, getToken, removeToken, storeUserData, getUserData, clearAll } from '../utils/storage';
import { initializePushNotifications } from '../services/notificationService';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  // Check if user is logged in on app start
  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const token = await getToken();
      const userData = await getUserData();

      if (token && userData) {
        setUser(userData);
        setIsAuthenticated(true);

        // Initialize push notifications
        initializePushNotifications().catch(err =>
          console.log('Push notification init error:', err)
        );
      } else {
        setUser(null);
        setIsAuthenticated(false);
      }
    } catch (error) {
      console.error('Error checking auth status:', error);
      setUser(null);
      setIsAuthenticated(false);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Login user
   */
  const login = async (email, password) => {
    try {
      const response = await authService.login(email, password);

      if (response.token && response.user) {
        // Store token and user data
        await storeToken(response.token);
        await storeUserData(response.user);

        // Update state
        setUser(response.user);
        setIsAuthenticated(true);

        // Initialize push notifications
        initializePushNotifications().catch(err =>
          console.log('Push notification init error:', err)
        );

        return { success: true };
      } else {
        throw new Error('Invalid response from server');
      }
    } catch (error) {
      console.error('Login error:', error);
      return {
        success: false,
        message: error.message || 'Login failed',
        errors: error.errors || {},
      };
    }
  };

  /**
   * Register new user
   */
  const register = async (userData) => {
    try {
      const response = await authService.register(userData);

      if (response.token && response.user) {
        // Store token and user data
        await storeToken(response.token);
        await storeUserData(response.user);

        // Update state
        setUser(response.user);
        setIsAuthenticated(true);

        // Initialize push notifications
        initializePushNotifications().catch(err =>
          console.log('Push notification init error:', err)
        );

        return { success: true };
      } else {
        throw new Error('Invalid response from server');
      }
    } catch (error) {
      console.error('Registration error:', error);
      return {
        success: false,
        message: error.message || 'Registration failed',
        errors: error.errors || {},
      };
    }
  };

  /**
   * Logout user
   */
  const logout = async () => {
    try {
      // Call logout API
      await authService.logout();
    } catch (error) {
      console.error('Logout API error:', error);
      // Continue with local logout even if API call fails
    } finally {
      // Clear storage
      await clearAll();

      // Update state
      setUser(null);
      setIsAuthenticated(false);
    }
  };

  /**
   * Update user data
   */
  const updateUser = async (userData) => {
    try {
      await storeUserData(userData);
      setUser(userData);
    } catch (error) {
      console.error('Error updating user:', error);
    }
  };

  const value = {
    user,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    updateUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

// Custom hook to use auth context
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export default AuthContext;
