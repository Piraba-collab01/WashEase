// washease-frontend/src/App.jsx
import React, { useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { Navbar } from './components/Navbar';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { ForgotPassword } from './pages/ForgotPassword';
import { CustomerDashboard } from './pages/CustomerDashboard';
import { VendorDashboard } from './pages/VendorDashboard';
import { AdminDashboard } from './pages/AdminDashboard';
import { Landing } from './pages/Landing';

const ProtectedRoute = ({ allowedRoles, children }) => {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  if (allowedRoles && !allowedRoles.includes(user.role)) {
    if (user.role === 'customer') return <Navigate to="/dashboard" replace />;
    if (user.role === 'vendor') return <Navigate to="/vendor-dashboard" replace />;
    if (user.role === 'admin') return <Navigate to="/admin-dashboard" replace />;
  }
  return children;
};

const PublicRoute = ({ children }) => {
  const { user, loading } = useAuth();
  if (loading) return null;
  if (user) {
    if (user.role === 'customer') return <Navigate to="/dashboard" replace />;
    if (user.role === 'vendor') return <Navigate to="/vendor-dashboard" replace />;
    if (user.role === 'admin') return <Navigate to="/admin-dashboard" replace />;
  }
  return children;
};

const FallbackRedirect = () => {
  const { user } = useAuth();
  if (user) {
    if (user.role === 'customer') return <Navigate to="/dashboard" replace />;
    if (user.role === 'vendor') return <Navigate to="/vendor-dashboard" replace />;
    if (user.role === 'admin') return <Navigate to="/admin-dashboard" replace />;
  }
  return <Navigate to="/" replace />;
};

const AppContent = () => {
  const { user, loading } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [registerRole, setRegisterRole] = useState('customer');

  // Compute the current active tab based on the URL path
  const currentPath = location.pathname.substring(1);
  const currentTab = currentPath || 'landing';

  // Wrapper handlers to translate legacy state changes into navigations
  const handleSetCurrentTab = (tab) => {
    if (tab === 'landing') {
      navigate('/');
    } else {
      navigate(`/${tab}`);
    }
  };

  const handleSetSubTab = (subTab) => {
    navigate(`/${subTab}`);
  };

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

  return (
    <div className="app-container">
      <Navbar currentTab={currentTab} setCurrentTab={handleSetCurrentTab} />
      <Routes>
        {/* Guest Routes */}
        <Route path="/" element={
          <PublicRoute>
            <Landing setCurrentTab={handleSetCurrentTab} setRegisterRole={setRegisterRole} />
          </PublicRoute>
        } />
        <Route path="/login" element={
          <PublicRoute>
            <Login setCurrentTab={handleSetCurrentTab} />
          </PublicRoute>
        } />
        <Route path="/register" element={
          <PublicRoute>
            <Register setCurrentTab={handleSetCurrentTab} initialRole={registerRole} />
          </PublicRoute>
        } />
        <Route path="/forgot-password" element={
          <PublicRoute>
            <ForgotPassword setCurrentTab={handleSetCurrentTab} />
          </PublicRoute>
        } />

        {/* Customer Protected Routes */}
        <Route path="/dashboard" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="dashboard" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/search" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="search" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/book" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="book" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/orders" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="orders" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/complaints" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="complaints" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/profile" element={
          <ProtectedRoute allowedRoles={['customer']}>
            <CustomerDashboard subTab="profile" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />

        {/* Vendor Protected Routes */}
        <Route path="/vendor-dashboard" element={
          <ProtectedRoute allowedRoles={['vendor']}>
            <VendorDashboard subTab="vendor-dashboard" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/vendor-orders" element={
          <ProtectedRoute allowedRoles={['vendor']}>
            <VendorDashboard subTab="vendor-orders" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/vendor-rewards" element={
          <ProtectedRoute allowedRoles={['vendor']}>
            <VendorDashboard subTab="vendor-rewards" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/vendor-reports" element={
          <ProtectedRoute allowedRoles={['vendor']}>
            <VendorDashboard subTab="vendor-reports" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/vendor-profile" element={
          <ProtectedRoute allowedRoles={['vendor']}>
            <VendorDashboard subTab="vendor-profile" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />

        {/* Admin Protected Routes */}
        <Route path="/admin-dashboard" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-dashboard" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/admin-users" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-users" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/admin-commissions" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-commissions" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/admin-fraud" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-fraud" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/admin-complaints" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-complaints" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />
        <Route path="/admin-reports" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard subTab="admin-reports" setSubTab={handleSetSubTab} />
          </ProtectedRoute>
        } />

        {/* Catch-all Fallback */}
        <Route path="*" element={<FallbackRedirect />} />
      </Routes>
    </div>
  );
};

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <AppContent />
      </AuthProvider>
    </BrowserRouter>
  );
}

export default App;
