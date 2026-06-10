// washease-frontend/src/components/Navbar.jsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';

export const Navbar = ({ currentTab, setCurrentTab }) => {
  const { user, logout, notifications, markNotificationsRead } = useAuth();
  const [showNotifications, setShowNotifications] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [theme, setTheme] = useState(localStorage.getItem('theme') || 'light');

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
  }, [theme]);

  useEffect(() => {
    const unread = notifications.filter(n => !n.is_read).length;
    setUnreadCount(unread);
  }, [notifications]);

  const toggleTheme = () => {
    setTheme(prev => (prev === 'light' ? 'dark' : 'light'));
  };

  const handleLogout = async () => {
    await logout();
    setCurrentTab('login');
  };

  const handleNotificationClick = () => {
    setShowNotifications(!showNotifications);
    if (!showNotifications && unreadCount > 0) {
      markNotificationsRead();
    }
  };

  const renderNavLinks = () => {
    if (!user) {
      return (
        <>
          <li className={`nav-link ${currentTab === 'landing' ? 'active' : ''}`} onClick={() => setCurrentTab('landing')}>Home</li>
          <li className={`nav-link ${currentTab === 'login' ? 'active' : ''}`} onClick={() => setCurrentTab('login')}>Login</li>
          <li className={`nav-link ${currentTab === 'register' ? 'active' : ''}`} onClick={() => setCurrentTab('register')}>Register</li>
        </>
      );
    }

    if (user.role === 'customer') {
      return (
        <>
          <li className={`nav-link ${currentTab === 'dashboard' ? 'active' : ''}`} onClick={() => setCurrentTab('dashboard')}>Dashboard</li>
          <li className={`nav-link ${currentTab === 'search' ? 'active' : ''}`} onClick={() => setCurrentTab('search')}>Search Shops</li>
          <li className={`nav-link ${currentTab === 'book' ? 'active' : ''}`} onClick={() => setCurrentTab('book')}>Book Pickup</li>
          <li className={`nav-link ${currentTab === 'orders' ? 'active' : ''}`} onClick={() => setCurrentTab('orders')}>Track Orders</li>
          <li className={`nav-link ${currentTab === 'complaints' ? 'active' : ''}`} onClick={() => setCurrentTab('complaints')}>Complaints</li>
          <li className={`nav-link ${currentTab === 'profile' ? 'active' : ''}`} onClick={() => setCurrentTab('profile')}>Profile</li>
        </>
      );
    }

    if (user.role === 'vendor') {
      return (
        <>
          <li className={`nav-link ${currentTab === 'vendor-dashboard' ? 'active' : ''}`} onClick={() => setCurrentTab('vendor-dashboard')}>Dashboard</li>
          <li className={`nav-link ${currentTab === 'vendor-orders' ? 'active' : ''}`} onClick={() => setCurrentTab('vendor-orders')}>Orders</li>
          <li className={`nav-link ${currentTab === 'vendor-rewards' ? 'active' : ''}`} onClick={() => setCurrentTab('vendor-rewards')}>Rewards</li>
          <li className={`nav-link ${currentTab === 'vendor-reports' ? 'active' : ''}`} onClick={() => setCurrentTab('vendor-reports')}>Reports</li>
          <li className={`nav-link ${currentTab === 'vendor-profile' ? 'active' : ''}`} onClick={() => setCurrentTab('vendor-profile')}>Profile</li>
        </>
      );
    }

    if (user.role === 'admin') {
      return (
        <>
          <li className={`nav-link ${currentTab === 'admin-dashboard' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-dashboard')}>Dashboard</li>
          <li className={`nav-link ${currentTab === 'admin-users' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-users')}>Users</li>
          <li className={`nav-link ${currentTab === 'admin-commissions' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-commissions')}>Commissions</li>
          <li className={`nav-link ${currentTab === 'admin-fraud' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-fraud')}>Fraud Alerts</li>
          <li className={`nav-link ${currentTab === 'admin-complaints' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-complaints')}>Complaints Board</li>
          <li className={`nav-link ${currentTab === 'admin-reports' ? 'active' : ''}`} onClick={() => setCurrentTab('admin-reports')}>Reports</li>
        </>
      );
    }
  };

  return (
    <nav className="navbar glass-panel">
      <div className="logo-container" onClick={() => setCurrentTab(user ? (user.role === 'customer' ? 'dashboard' : user.role === 'vendor' ? 'vendor-dashboard' : 'admin-dashboard') : 'landing')}>
        WashEase<span className="logo-dot"></span>
      </div>

      <ul className="nav-links">
        {renderNavLinks()}
      </ul>

      <div className="nav-actions">
        {/* Dark Mode Switcher */}
        <button className="theme-switch" onClick={toggleTheme} title="Toggle Theme">
          {theme === 'light' ? '🌙' : '☀️'}
        </button>

        {/* Notification Bell */}
        {user && (
          <div style={{ position: 'relative' }}>
            <button 
              className="theme-switch" 
              onClick={handleNotificationClick} 
              title="Notifications"
              style={{ position: 'relative', cursor: 'pointer' }}
            >
              🔔
              {unreadCount > 0 && (
                <span style={{
                  position: 'absolute',
                  top: '-4px',
                  right: '-4px',
                  background: 'var(--danger)',
                  color: 'white',
                  borderRadius: '50%',
                  width: '18px',
                  height: '18px',
                  fontSize: '10px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontWeight: 'bold'
                }}>
                  {unreadCount}
                </span>
              )}
            </button>

            {showNotifications && (
              <div className="glass-panel" style={{
                position: 'absolute',
                top: '40px',
                right: '0',
                width: '320px',
                maxHeight: '400px',
                overflowY: 'auto',
                zIndex: 1000,
                padding: '1rem',
                display: 'flex',
                flexDirection: 'column',
                gap: '0.75rem',
                border: '1px solid var(--card-border)',
                boxShadow: 'var(--shadow)',
                background: 'var(--card-bg)'
              }}>
                <h4 style={{ borderBottom: '1px solid var(--card-border)', paddingBottom: '0.5rem', display: 'flex', justifyContent: 'space-between' }}>
                  <span>Notifications</span>
                  <span style={{ fontSize: '0.75rem', color: 'var(--text-muted)', cursor: 'pointer' }} onClick={() => setShowNotifications(false)}>Close</span>
                </h4>
                {notifications.length === 0 ? (
                  <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)', textAlign: 'center', padding: '1rem' }}>No notifications</p>
                ) : (
                  notifications.map(n => (
                    <div 
                      key={n.id} 
                      style={{ 
                        fontSize: '0.85rem', 
                        padding: '0.5rem', 
                        borderRadius: '6px',
                        background: n.is_read ? 'transparent' : 'rgba(138, 43, 226, 0.08)',
                        borderLeft: n.is_read ? 'none' : '3px solid var(--primary)'
                      }}
                    >
                      <p style={{ color: 'var(--text-main)' }}>{n.message}</p>
                      <span style={{ fontSize: '0.7rem', color: 'var(--text-muted)' }}>{n.created_at}</span>
                    </div>
                  ))
                )}
              </div>
            )}
          </div>
        )}

        {user && (
          <button className="btn btn-secondary" onClick={handleLogout} style={{ padding: '0.4rem 1rem', fontSize: '0.85rem' }}>
            Logout
          </button>
        )}
      </div>
    </nav>
  );
};
