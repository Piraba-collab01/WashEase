// washease-frontend/src/context/AuthContext.jsx
import React, { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext(null);

export const API_URL = 'http://localhost:8000/index.php';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [notifications, setNotifications] = useState([]);

  const fetchWithAuth = async (url, options = {}) => {
    options.credentials = 'include';
    options.headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };
    return fetch(url, options);
  };

  const checkAuth = async () => {
    try {
      const res = await fetchWithAuth(`${API_URL}?action=check-auth`);
      const data = await res.json();
      if (data.success && data.user) {
        setUser(data.user);
        fetchNotifications();
      } else {
        setUser(null);
      }
    } catch (err) {
      console.error('Auth check failed:', err);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const fetchNotifications = async () => {
    try {
      const res = await fetchWithAuth(`${API_URL}?action=get-notifications`);
      const data = await res.json();
      if (data.success) {
        setNotifications(data.data || []);
      }
    } catch (err) {
      console.error('Failed to fetch notifications:', err);
    }
  };

  const markNotificationsRead = async () => {
    try {
      const res = await fetchWithAuth(`${API_URL}?action=mark-notifications-read`, {
        method: 'POST',
      });
      const data = await res.json();
      if (data.success) {
        fetchNotifications();
      }
    } catch (err) {
      console.error('Failed to mark notifications read:', err);
    }
  };

  useEffect(() => {
    checkAuth();
    // Poll notifications every 30 seconds
    const interval = setInterval(() => {
      if (user) fetchNotifications();
    }, 30000);
    return () => clearInterval(interval);
  }, [user]);

  const login = async (username, password) => {
    const res = await fetchWithAuth(`${API_URL}?action=login`, {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    });
    const data = await res.json();
    if (data.success && data.user) {
      setUser(data.user);
      fetchNotifications();
    }
    return data;
  };

  const logout = async () => {
    try {
      const res = await fetchWithAuth(`${API_URL}?action=logout`);
      const data = await res.json();
      if (data.success) {
        setUser(null);
        setNotifications([]);
      }
      return data;
    } catch (err) {
      console.error('Logout failed:', err);
      setUser(null);
      setNotifications([]);
      return { success: true };
    }
  };

  const register = async (regData) => {
    const res = await fetchWithAuth(`${API_URL}?action=register`, {
      method: 'POST',
      body: JSON.stringify(regData),
    });
    return await res.json();
  };

  const verifyOTP = async (email, otp) => {
    const res = await fetchWithAuth(`${API_URL}?action=verify-otp`, {
      method: 'POST',
      body: JSON.stringify({ email, otp }),
    });
    return await res.json();
  };

  const forgotPassword = async (email) => {
    const res = await fetchWithAuth(`${API_URL}?action=forgot-password`, {
      method: 'POST',
      body: JSON.stringify({ email }),
    });
    return await res.json();
  };

  const resetPassword = async (email, otp, password, confirm_password) => {
    const res = await fetchWithAuth(`${API_URL}?action=reset-password`, {
      method: 'POST',
      body: JSON.stringify({ email, otp, password, confirm_password }),
    });
    return await res.json();
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        setUser,
        loading,
        checkAuth,
        login,
        logout,
        register,
        verifyOTP,
        forgotPassword,
        resetPassword,
        fetchWithAuth,
        notifications,
        markNotificationsRead,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
