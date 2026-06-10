// washease-frontend/src/pages/ForgotPassword.jsx
import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';

export const ForgotPassword = ({ setCurrentTab }) => {
  const { forgotPassword, resetPassword } = useAuth();
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const [step, setStep] = useState(1); // 1 = Enter Email, 2 = Enter OTP & New Password
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleRequestOtp = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await forgotPassword(email);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => {
          setStep(2);
          setSuccess('');
        }, 1200);
      } else {
        setError(res.message || 'Failed to send recovery OTP.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    if (newPassword !== confirmPassword) {
      setError('Passwords do not match.');
      setLoading(false);
      return;
    }

    try {
      const res = await resetPassword(email, otp, newPassword, confirmPassword);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => {
          setCurrentTab('login');
        }, 2000);
      } else {
        setError(res.message || 'Password reset failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred during reset. Please try again.');
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
        }}>Recover Password</h2>
        <p style={{
          textAlign: 'center',
          color: 'var(--text-muted)',
          fontSize: '0.95rem',
          marginBottom: '2rem'
        }}>
          {step === 1 
            ? 'Enter your email to receive a recovery code.' 
            : 'Enter the recovery OTP and your new password.'}
        </p>

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

        {step === 1 ? (
          <form onSubmit={handleRequestOtp}>
            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label className="form-label">Registered Email Address</label>
              <input
                type="email"
                className="form-control"
                placeholder="email@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>

            <button 
              type="submit" 
              className="btn btn-primary" 
              style={{ width: '100%', padding: '0.8rem' }}
              disabled={loading}
            >
              {loading ? 'Sending OTP...' : 'Send Recovery OTP'}
            </button>
          </form>
        ) : (
          <form onSubmit={handleResetSubmit}>
            <div className="form-group">
              <label className="form-label">Enter 6-Digit OTP</label>
              <input
                type="text"
                maxLength="6"
                className="form-control"
                placeholder="e.g. 123456"
                style={{ textAlign: 'center', fontWeight: 'bold' }}
                value={otp}
                onChange={(e) => setOtp(e.target.value)}
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">New Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Min 6 characters"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                required
              />
            </div>

            <div className="form-group" style={{ marginBottom: '1.5rem' }}>
              <label className="form-label">Confirm New Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Repeat new password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                required
              />
            </div>

            <button 
              type="submit" 
              className="btn btn-primary" 
              style={{ width: '100%', padding: '0.8rem' }}
              disabled={loading}
            >
              {loading ? 'Resetting password...' : 'Update Password'}
            </button>
          </form>
        )}

        <div style={{
          marginTop: '1.5rem',
          textAlign: 'center',
          fontSize: '0.9rem',
          color: 'var(--text-muted)'
        }}>
          Remember your password?{' '}
          <span 
            style={{ color: 'var(--primary-light)', cursor: 'pointer', fontWeight: 600 }}
            onClick={() => setCurrentTab('login')}
          >
            Sign In
          </span>
        </div>
      </div>
    </div>
  );
};
