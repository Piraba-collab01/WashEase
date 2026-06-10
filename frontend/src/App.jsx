// washease-frontend/src/App.jsx
import React, { useState, useEffect } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import { Navbar } from './components/Navbar';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { ForgotPassword } from './pages/ForgotPassword';
import { CustomerDashboard } from './pages/CustomerDashboard';
import { VendorDashboard } from './pages/VendorDashboard';
import { AdminDashboard } from './pages/AdminDashboard';
import { Landing } from './pages/Landing';

const AppContent = () => {
  const { user, loading } = useAuth();
  const [currentTab, setCurrentTab] = useState('landing');
  const [registerRole, setRegisterRole] = useState('customer');

  // Sync tab with authentication state
  useEffect(() => {
    if (!loading) {
      if (user) {
        if (user.role === 'customer') {
          setCurrentTab(prev => ['dashboard', 'search', 'book', 'orders', 'complaints', 'profile'].includes(prev) ? prev : 'dashboard');
        } else if (user.role === 'vendor') {
          setCurrentTab(prev => ['vendor-dashboard', 'vendor-orders', 'vendor-rewards', 'vendor-reports', 'vendor-profile'].includes(prev) ? prev : 'vendor-dashboard');
        } else if (user.role === 'admin') {
          setCurrentTab(prev => ['admin-dashboard', 'admin-users', 'admin-commissions', 'admin-fraud', 'admin-complaints', 'admin-reports'].includes(prev) ? prev : 'admin-dashboard');
        }
      } else {
        setCurrentTab(prev => ['landing', 'login', 'register', 'forgot-password'].includes(prev) ? prev : 'landing');
      }
    }
  }, [user, loading]);

  if (loading) {
    return (
      <div style={{
        display: 'flex',
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        minHeight: '100vh',
        background: 'var(--bg-gradient)',
        color: 'var(--text-main)'
      }}>
        <div style={{
          width: '60px',
          height: '60px',
          border: '5px solid var(--card-border)',
          borderTop: '5px solid var(--primary)',
          borderRadius: '50%',
          animation: 'spin 1s linear infinite',
          marginBottom: '1rem'
        }}></div>
        <h3 style={{ fontFamily: 'var(--font-title)', fontWeight: 600 }}>Loading WashEase...</h3>
        <style>{`
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `}</style>
      </div>
    );
  }

  const renderContent = () => {
    // Guest Routing
    if (!user) {
      switch (currentTab) {
        case 'register':
          return <Register setCurrentTab={setCurrentTab} initialRole={registerRole} />;
        case 'forgot-password':
          return <ForgotPassword setCurrentTab={setCurrentTab} />;
        case 'login':
          return <Login setCurrentTab={setCurrentTab} />;
        case 'landing':
        default:
          return <Landing setCurrentTab={setCurrentTab} setRegisterRole={setRegisterRole} />;
      }
    }

    // Customer Routing
    if (user.role === 'customer') {
      return <CustomerDashboard subTab={currentTab} setSubTab={setCurrentTab} />;
    }

    // Vendor Routing
    if (user.role === 'vendor') {
      return <VendorDashboard subTab={currentTab} setSubTab={setCurrentTab} />;
    }

    // Admin Routing
    if (user.role === 'admin') {
      return <AdminDashboard subTab={currentTab} setSubTab={setCurrentTab} />;
    }
  };

  return (
    <div className="app-container">
      <Navbar currentTab={currentTab} setCurrentTab={setCurrentTab} />
      {renderContent()}
    </div>
  );
};

function App() {
  return (
    <AuthProvider>
      <AppContent />
    </AuthProvider>
  );
}

export default App;
