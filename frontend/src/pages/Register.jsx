// washease-frontend/src/pages/Register.jsx
import { useState } from 'react';
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

  const DISTRICT_PRESETS = {
    'Colombo': { lat: 6.927079, lng: 79.861244 },
    'Kandy': { lat: 7.290572, lng: 80.633726 },
    'Galle': { lat: 6.053519, lng: 80.220978 },
    'Jaffna': { lat: 9.661498, lng: 80.025543 },
    'Gampaha': { lat: 7.084013, lng: 79.993427 },
    'Negombo': { lat: 7.200777, lng: 79.873672 },
    'Kurunegala': { lat: 7.486326, lng: 80.364741 },
    'Batticaloa': { lat: 7.730997, lng: 81.674681 },
    'Matara': { lat: 5.954920, lng: 80.554956 },
    'Anuradhapura': { lat: 8.311352, lng: 80.403651 },
    'Trincomalee': { lat: 8.587364, lng: 81.215212 },
    'Badulla': { lat: 6.993401, lng: 81.054980 },
    'Ratnapura': { lat: 6.682772, lng: 80.399166 },
    'Kalutara': { lat: 6.585394, lng: 79.959960 }
  };

  const handleDistrictChange = (val) => {
    setDistrict(val);
    if (DISTRICT_PRESETS[val] && (!latitude || !longitude || latitude === '0' || longitude === '0')) {
      setLatitude(DISTRICT_PRESETS[val].lat.toFixed(6));
      setLongitude(DISTRICT_PRESETS[val].lng.toFixed(6));
      setSuccess(`Applied coordinates for ${val} district.`);
    }
  };

  const fetchLocationFromAddress = async () => {
    const query = [shopAddress, district, 'Sri Lanka'].filter(Boolean).join(', ');
    if (!query || query === 'Sri Lanka') {
      setError('Please enter a Shop Address or District/Region first to search by address.');
      return;
    }
    setError('');
    setSuccess('Searching location coordinates from address...');

    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`);
      const data = await res.json();
      if (data && data.length > 0) {
        setLatitude(parseFloat(data[0].lat).toFixed(6));
        setLongitude(parseFloat(data[0].lon).toFixed(6));
        setSuccess(`📍 Location found: ${data[0].display_name.split(',').slice(0, 3).join(',')}`);
      } else if (district && DISTRICT_PRESETS[district]) {
        setLatitude(DISTRICT_PRESETS[district].lat.toFixed(6));
        setLongitude(DISTRICT_PRESETS[district].lng.toFixed(6));
        setSuccess(`Applied default coordinates for ${district}.`);
      } else {
        setError('Could not locate address automatically. Please pick a District or enter coordinates manually.');
      }
    } catch (err) {
      console.error('Address geocoding error:', err);
      if (district && DISTRICT_PRESETS[district]) {
        setLatitude(DISTRICT_PRESETS[district].lat.toFixed(6));
        setLongitude(DISTRICT_PRESETS[district].lng.toFixed(6));
        setSuccess(`Applied district default coordinates for ${district}.`);
      } else {
        setError('Geocoding service unavailable. Please enter coordinates manually.');
      }
    }
  };

  // Auto Grab Geolocation for Vendor
  const getGeolocation = () => {
    setError('');
    setSuccess('Detecting GPS location...');
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLatitude(position.coords.latitude.toFixed(6));
          setLongitude(position.coords.longitude.toFixed(6));
          setSuccess('📍 Shop GPS coordinates grabbed successfully!');
        },
        async (err) => {
          console.warn('Browser Geolocation error:', err);
          if (shopAddress || district) {
            setSuccess('Browser GPS unavailable. Searching coordinates from address...');
            await fetchLocationFromAddress();
          } else {
            setError('Browser GPS unavailable. Please type your Shop Address/District or click "Search from Address".');
          }
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
      );
    } else {
      if (shopAddress || district) {
        fetchLocationFromAddress();
      } else {
        setError('Geolocation not supported by browser. Please enter address or coordinates manually.');
      }
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
                    list="districts-list"
                    className="form-control"
                    placeholder="e.g. Colombo, Kandy, Jaffna"
                    value={district}
                    onChange={(e) => handleDistrictChange(e.target.value)}
                    required
                  />
                  <datalist id="districts-list">
                    {Object.keys(DISTRICT_PRESETS).map(d => (
                      <option key={d} value={d} />
                    ))}
                  </datalist>
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

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '0.75rem' }}>
                <button 
                  type="button" 
                  className="btn btn-secondary" 
                  onClick={getGeolocation}
                  style={{ width: '100%', padding: '0.65rem 0.5rem', fontSize: '0.85rem' }}
                >
                  📍 Detect My GPS
                </button>
                <button 
                  type="button" 
                  className="btn btn-secondary" 
                  onClick={fetchLocationFromAddress}
                  style={{ width: '100%', padding: '0.65rem 0.5rem', fontSize: '0.85rem' }}
                >
                  🔍 Search from Address
                </button>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                <div className="form-group">
                  <label className="form-label">Latitude</label>
                  <input
                    type="number"
                    step="0.000001"
                    className="form-control"
                    placeholder="e.g. 6.927079"
                    value={latitude}
                    onChange={(e) => setLatitude(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Longitude</label>
                  <input
                    type="number"
                    step="0.000001"
                    className="form-control"
                    placeholder="e.g. 79.861244"
                    value={longitude}
                    onChange={(e) => setLongitude(e.target.value)}
                    required
                  />
                </div>
              </div>
              <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', margin: '0.25rem 0 1rem 0' }}>📍 Auto-fetches coordinates via GPS, address search, or District presets for calculating nearby search distance.</p>

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
