// washease-frontend/src/pages/Login.jsx
import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';

export const Login = ({ setCurrentTab, setEmailForOTP }) => {
  const { login, checkAuth } = useAuth();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await login(username, password);
      if (res.success) {
        setSuccess(res.message);
        // Direct to appropriate dashboard depending on role
        const role = res.user.role;
        setTimeout(() => {
          if (role === 'admin') setCurrentTab('admin-dashboard');
          else if (role === 'vendor') setCurrentTab('vendor-dashboard');
          else setCurrentTab('dashboard');
        }, 1000);
      } else {
        setError(res.message || 'Login failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred during login. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      minHeight: '80vh',
      padding: '1rem'
    }}>
      <div className="glass-panel" style={{
        width: '100%',
        maxWidth: '450px',
        padding: '2.5rem',
        animation: 'fadeIn 0.5s ease-out'
      }}>
        <h2 style={{
          textAlign: 'center',
          color: 'var(--primary)',
          fontSize: '2rem',
          marginBottom: '0.5rem',
          fontWeight: 800
        }}>WashEase</h2>
        <p style={{
          textAlign: 'center',
          color: 'var(--text-muted)',
          fontSize: '0.95rem',
          marginBottom: '2rem'
        }}>Smart Laundry Marketplace Platform</p>

        {error && (
          <div style={{
            background: 'rgba(231, 29, 54, 0.12)',
            color: 'var(--danger)',
            border: '1px solid rgba(231, 29, 54, 0.2)',
            padding: '0.8rem',
            borderRadius: '8px',
            fontSize: '0.9rem',
            marginBottom: '1.25rem',
            textAlign: 'center'
          }}>
            ⚠️ {error}
          </div>
        )}

        {success && (
          <div style={{
            background: 'rgba(46, 196, 182, 0.12)',
            color: 'var(--success)',
            border: '1px solid rgba(46, 196, 182, 0.2)',
            padding: '0.8rem',
            borderRadius: '8px',
            fontSize: '0.9rem',
            marginBottom: '1.25rem',
            textAlign: 'center'
          }}>
            ✅ {success}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label className="form-label">Username or Email</label>
            <input
              type="text"
              className="form-control"
              placeholder="Enter your username or email"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>

          <div className="form-group" style={{ marginBottom: '1.75rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.4rem' }}>
              <label className="form-label" style={{ margin: 0 }}>Password</label>
              <span 
                style={{ fontSize: '0.8rem', color: 'var(--primary-light)', cursor: 'pointer', fontWeight: 600 }}
                onClick={() => setCurrentTab('forgot-password')}
              >
                Forgot Password?
              </span>
            </div>
            <input
              type="password"
              className="form-control"
              placeholder="Enter your password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          <button 
            type="submit" 
            className="btn btn-primary" 
            style={{ width: '100%', padding: '0.8rem' }}
            disabled={loading}
          >
            {loading ? 'Logging in...' : 'Sign In'}
          </button>
        </form>

        <div style={{
          marginTop: '1.5rem',
          textAlign: 'center',
          fontSize: '0.9rem',
          color: 'var(--text-muted)'
        }}>
          Don't have an account?{' '}
          <span 
            style={{ color: 'var(--primary-light)', cursor: 'pointer', fontWeight: 600 }}
            onClick={() => setCurrentTab('register')}
          >
            Register here
          </span>
        </div>
      </div>
    </div>
  );
};
