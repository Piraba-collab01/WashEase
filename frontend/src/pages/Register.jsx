// washease-frontend/src/pages/Register.jsx
import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';

export const Register = ({ setCurrentTab, initialRole = 'customer' }) => {
  const { register, verifyOTP } = useAuth();
  const [role, setRole] = useState(initialRole); // customer or vendor

  // Common Fields
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [contactNumber, setContactNumber] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  // Customer Fields
  const [fullName, setFullName] = useState('');
  const [address, setAddress] = useState('');

  // Vendor Fields
  const [shopName, setShopName] = useState('');
  const [ownerName, setOwnerName] = useState('');
  const [shopAddress, setShopAddress] = useState('');
  const [district, setDistrict] = useState('');
  const [latitude, setLatitude] = useState('');
  const [longitude, setLongitude] = useState('');
  const [openingTime, setOpeningTime] = useState('08:00');
  const [closingTime, setClosingTime] = useState('20:00');

  // OTP Modal State
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [otp, setOtp] = useState('');
  const [otpEmail, setOtpEmail] = useState('');

  // Status State
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  // Auto Grab Geolocation for Vendor
  const getGeolocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLatitude(position.coords.latitude.toFixed(6));
          setLongitude(position.coords.longitude.toFixed(6));
          setSuccess('Shop GPS coordinates grabbed successfully!');
        },
        (err) => {
          setError('Failed to fetch location. Please enter manually.');
        }
      );
    } else {
      setError('Geolocation not supported by browser.');
    }
  };

  const handleRegisterSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    if (password !== confirmPassword) {
      setError('Passwords do not match.');
      setLoading(false);
      return;
    }

    const baseData = {
      role,
      username,
      email,
      contact_number: contactNumber,
      password,
      confirm_password: confirmPassword,
    };

    const regData = role === 'customer' 
      ? { ...baseData, full_name: fullName, address } 
      : { 
          ...baseData, 
          shop_name: shopName, 
          owner_name: ownerName, 
          shop_address: shopAddress, 
          district, 
          latitude: latitude || 0, 
          longitude: longitude || 0,
          opening_time: openingTime, 
          closing_time: closingTime 
        };

    try {
      const res = await register(regData);
      if (res.success) {
        setSuccess(res.message);
        setOtpEmail(res.email || email);
        // Open OTP Modal
        setTimeout(() => {
          setShowOtpModal(true);
        }, 1200);
      } else {
        setError(res.message || 'Registration failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred during registration. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleOtpVerify = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await verifyOTP(otpEmail, otp);
      if (res.success) {
        setSuccess(res.message);
        setTimeout(() => {
          setShowOtpModal(false);
          setCurrentTab('login');
        }, 2000);
      } else {
        setError(res.message || 'OTP verification failed.');
      }
    } catch (err) {
      console.error(err);
      setError('Verification failed. Try again.');
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
      padding: '2rem 1rem'
    }}>
      <div className="glass-panel" style={{
        width: '100%',
        maxWidth: '600px',
        padding: '2.5rem',
        animation: 'fadeIn 0.5s ease-out'
      }}>
        <h2 style={{
          textAlign: 'center',
          color: 'var(--primary)',
          fontSize: '2rem',
          marginBottom: '1rem',
          fontWeight: 800
        }}>WashEase Registration</h2>

        {/* Role Select Buttons */}
        <div style={{
          display: 'flex',
          gap: '1rem',
          marginBottom: '2rem',
          justifyContent: 'center'
        }}>
          <button 
            type="button"
            className={`btn ${role === 'customer' ? 'btn-primary' : 'btn-secondary'}`}
            style={{ flex: 1 }}
            onClick={() => { setRole('customer'); setError(''); setSuccess(''); }}
          >
            👤 Customer Sign Up
          </button>
          <button 
            type="button"
            className={`btn ${role === 'vendor' ? 'btn-primary' : 'btn-secondary'}`}
            style={{ flex: 1 }}
            onClick={() => { setRole('vendor'); setError(''); setSuccess(''); }}
          >
            🏪 Vendor Sign Up
          </button>
        </div>

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

        <form onSubmit={handleRegisterSubmit}>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label">Username</label>
              <input
                type="text"
                className="form-control"
                placeholder="Unique username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                required
              />
            </div>
            <div className="form-group">
              <label className="form-label">Email Address</label>
              <input
                type="email"
                className="form-control"
                placeholder="email@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
            </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
            <div className="form-group">
              <label className="form-label">Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Min 6 characters"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>
            <div className="form-group">
              <label className="form-label">Confirm Password</label>
              <input
                type="password"
                className="form-control"
                placeholder="Repeat password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                required
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">Contact Number</label>
            <input
              type="text"
              className="form-control"
              placeholder="Phone number"
              value={contactNumber}
              onChange={(e) => setContactNumber(e.target.value)}
              required
            />
          </div>

          {/* Customer Specific Fields */}
          {role === 'customer' && (
            <>
              <div className="form-group">
                <label className="form-label">Full Name</label>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Your full name"
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label">Residential Address</label>
                <textarea
                  className="form-control"
                  rows="3"
                  placeholder="Street, City, Zip"
                  value={address}
                  onChange={(e) => setAddress(e.target.value)}
                  required
                ></textarea>
              </div>
            </>
          )}

          {/* Vendor Specific Fields */}
          {role === 'vendor' && (
            <>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                  <label className="form-label">Shop Name</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Laundry Shop Name"
                    value={shopName}
                    onChange={(e) => setShopName(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Owner Name</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Full Name of Owner"
                    value={ownerName}
                    onChange={(e) => setOwnerName(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                  <label className="form-label">District / Region</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="e.g. Central, East"
                    value={district}
                    onChange={(e) => setDistrict(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Shop Address</label>
                  <input
                    type="text"
                    className="form-control"
                    placeholder="Shop location address"
                    value={shopAddress}
                    onChange={(e) => setShopAddress(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '1rem', alignItems: 'end' }}>
                <div className="form-group" style={{ margin: 0 }}>
                  <label className="form-label">Latitude</label>
                  <input
                    type="number"
                    step="0.000001"
                    className="form-control"
                    placeholder="e.g. 12.97"
                    value={latitude}
                    onChange={(e) => setLatitude(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group" style={{ margin: 0 }}>
                  <label className="form-label">Longitude</label>
                  <input
                    type="number"
                    step="0.000001"
                    className="form-control"
                    placeholder="e.g. 77.59"
                    value={longitude}
                    onChange={(e) => setLongitude(e.target.value)}
                    required
                  />
                </div>
                <button 
                  type="button" 
                  className="btn btn-secondary" 
                  onClick={getGeolocation}
                  style={{ width: '100%', padding: '0.75rem 0.5rem', height: '44px' }}
                >
                  📍 Grab GPS
                </button>
              </div>
              <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', margin: '0.25rem 0 1rem 0' }}>Grabs current coordinates for calculating nearby search distance.</p>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                  <label className="form-label">Opening Time</label>
                  <input
                    type="time"
                    className="form-control"
                    value={openingTime}
                    onChange={(e) => setOpeningTime(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Closing Time</label>
                  <input
                    type="time"
                    className="form-control"
                    value={closingTime}
                    onChange={(e) => setClosingTime(e.target.value)}
                    required
                  />
                </div>
              </div>
            </>
          )}

          <button 
            type="submit" 
            className="btn btn-primary" 
            style={{ width: '100%', padding: '0.8rem', marginTop: '1rem' }}
            disabled={loading}
          >
            {loading ? 'Creating account...' : 'Create Account'}
          </button>
        </form>

        <div style={{
          marginTop: '1.5rem',
          textAlign: 'center',
          fontSize: '0.9rem',
          color: 'var(--text-muted)'
        }}>
          Already have an account?{' '}
          <span 
            style={{ color: 'var(--primary-light)', cursor: 'pointer', fontWeight: 600 }}
            onClick={() => setCurrentTab('login')}
          >
            Sign In here
          </span>
        </div>
      </div>

      {/* OTP Verification Modal */}
      {showOtpModal && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.5rem', fontWeight: 700 }}>Email Verification OTP</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              We have sent a verification code to <b>{otpEmail}</b>. Please check your email (or review `otp.log` for development).
            </p>

            <form onSubmit={handleOtpVerify}>
              <div className="form-group">
                <label className="form-label">Enter 6-Digit OTP</label>
                <input
                  type="text"
                  maxLength="6"
                  className="form-control"
                  placeholder="e.g. 123456"
                  style={{ textAlign: 'center', letterSpacing: '0.5rem', fontSize: '1.5rem', fontWeight: 'bold' }}
                  value={otp}
                  onChange={(e) => setOtp(e.target.value)}
                  required
                />
              </div>

              <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}>
                <button 
                  type="submit" 
                  className="btn btn-primary" 
                  style={{ flex: 1 }}
                  disabled={loading}
                >
                  {loading ? 'Verifying...' : 'Verify OTP'}
                </button>
                <button 
                  type="button" 
                  className="btn btn-secondary" 
                  onClick={() => setShowOtpModal(false)}
                  style={{ flex: 1 }}
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
