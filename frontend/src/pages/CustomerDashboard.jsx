// washease-frontend/src/pages/CustomerDashboard.jsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';

export const CustomerDashboard = ({ subTab, setSubTab }) => {
  const { user, fetchWithAuth, checkAuth } = useAuth();
  const [stats, setStats] = useState({ total: 0, pending: 0, completed: 0, complaints: 0 });
  const [nearbyShops, setNearbyShops] = useState([]);
  const [searchDistrict, setSearchDistrict] = useState('');
  
  // Geolocation coords for customer
  const [coords, setCoords] = useState({ lat: 12.9716, lng: 77.5946 });

  // Booking Form State
  const [selectedVendor, setSelectedVendor] = useState(null);
  const [pickupAddress, setPickupAddress] = useState(user?.details?.address || '');
  const [pickupDate, setPickupDate] = useState('');
  const [pickupTime, setPickupTime] = useState('');
  const [clothesWeight, setClothesWeight] = useState('');
  const [serviceType, setServiceType] = useState('Wash Only');
  const [specialInstructions, setSpecialInstructions] = useState('');

  // Orders and Tracking State
  const [orders, setOrders] = useState([]);
  const [trackingOrder, setTrackingOrder] = useState(null); // order for timeline popup
  const [selectedInvoice, setSelectedInvoice] = useState(null); // order/invoice for payment confirmation
  const [actualPaidAmount, setActualPaidAmount] = useState('');

  // Complaints State
  const [complaints, setComplaints] = useState([]);
  const [complaintOrderId, setComplaintOrderId] = useState('');
  const [complaintCategory, setComplaintCategory] = useState('Late Delivery');
  const [complaintDesc, setComplaintDesc] = useState('');

  // Profile State
  const [profileEmail, setProfileEmail] = useState(user?.email || '');
  const [profilePhone, setProfilePhone] = useState(user?.details?.contact_number || '');
  const [profileAddress, setProfileAddress] = useState(user?.details?.address || '');
  const [profilePassword, setProfilePassword] = useState('');

  // Feedback State
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Get customer geolocation if possible
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((pos) => {
        setCoords({ lat: pos.coords.latitude, lng: pos.coords.longitude });
      });
    }
    loadDashboardData();
  }, [subTab]);

  const loadDashboardData = async () => {
    setError('');
    try {
      // 1. Fetch Orders
      const resOrders = await fetchWithAuth(`http://localhost:8000/index.php?action=list-orders`);
      const dataOrders = await resOrders.json();
      let ordersList = [];
      if (dataOrders.success) {
        ordersList = dataOrders.data || [];
        setOrders(ordersList);
      }

      // 2. Fetch Complaints
      const resComplaints = await fetchWithAuth(`http://localhost:8000/index.php?action=list-complaints`);
      const dataComplaints = await resComplaints.json();
      let complaintsList = [];
      if (dataComplaints.success) {
        complaintsList = dataComplaints.data || [];
        setComplaints(complaintsList);
      }

      // 3. Compute stats
      const pending = ordersList.filter(o => o.status !== 'Delivered').length;
      const completed = ordersList.filter(o => o.status === 'Delivered').length;
      setStats({
        total: ordersList.length,
        pending,
        completed,
        complaints: complaintsList.filter(c => c.status !== 'Closed').length
      });

      // 4. Fetch Nearby Shops
      handleSearchShops();

    } catch (err) {
      console.error('Error loading dashboard data:', err);
    }
  };

  const handleSearchShops = async () => {
    try {
      const query = `latitude=${coords.lat}&longitude=${coords.lng}&district=${searchDistrict}`;
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=search-shops&${query}`);
      const data = await res.json();
      if (data.success) {
        setNearbyShops(data.data || []);
      }
    } catch (err) {
      console.error('Failed to search shops:', err);
    }
  };

  const handleBookService = (shop) => {
    setSelectedVendor(shop);
    setSubTab('book');
  };

  const handlePlaceBooking = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    if (!selectedVendor) {
      setError('Please select a laundry shop first.');
      setLoading(false);
      return;
    }

    const bookingData = {
      vendor_id: selectedVendor.vendor_id,
      pickup_address: pickupAddress,
      pickup_date: pickupDate,
      pickup_time: pickupTime,
      clothes_weight: clothesWeight,
      service_type: serviceType,
      special_instructions: specialInstructions
    };

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=book-pickup`, {
        method: 'POST',
        body: JSON.stringify(bookingData)
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(`Booking placed! Tracking Number: ${data.data.tracking_number}`);
        // Reset form
        setPickupDate('');
        setPickupTime('');
        setClothesWeight('');
        setSpecialInstructions('');
        setSelectedVendor(null);
        setTimeout(() => {
          setSubTab('orders');
        }, 2000);
      } else {
        setError(data.message || 'Failed to place booking.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenTimeline = async (orderId) => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=order-details&order_id=${orderId}`);
      const data = await res.json();
      if (data.success) {
        setTrackingOrder(data.data);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleConfirmPaymentSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=confirm-payment&order_id=${selectedInvoice.order.id}`, {
        method: 'POST',
        body: JSON.stringify({ actual_paid_amount: actualPaidAmount })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        setTimeout(() => {
          setSelectedInvoice(null);
          loadDashboardData();
        }, 2000);
      } else {
        if (data.fraud) {
          setError(data.message);
          // Don't close modal immediately so they can see fraud alert warning
        } else {
          setError(data.message || 'Payment confirmation failed.');
        }
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitComplaint = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=submit-complaint`, {
        method: 'POST',
        body: JSON.stringify({
          order_id: complaintOrderId,
          category: complaintCategory,
          description: complaintDesc
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess('Complaint submitted successfully.');
        setComplaintOrderId('');
        setComplaintDesc('');
        loadDashboardData();
      } else {
        setError(data.message || 'Complaint submission failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=customer-profile`, {
        method: 'POST',
        body: JSON.stringify({
          email: profileEmail,
          contact_number: profilePhone,
          address: profileAddress,
          password: profilePassword
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess('Profile updated successfully.');
        setProfilePassword('');
        checkAuth(); // update AuthContext session user details
      } else {
        setError(data.message || 'Failed to update profile.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const getStatusStepIndex = (status) => {
    const statuses = ['Pending', 'Accepted', 'Pickup Scheduled', 'Picked Up', 'Washing', 'Ironing', 'Ready', 'Delivered'];
    return statuses.indexOf(status);
  };

  return (
    <div className="main-content">
      {/* 1. Header and Statistics Grid */}
      {subTab === 'dashboard' && (
        <div style={{ animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span>Welcome, {user?.details?.full_name}! 👋</span>
            <span style={{ fontSize: '1rem', color: 'var(--text-muted)' }}>Customer ID: C-{user?.id}</span>
          </h2>

          <div className="dashboard-grid">
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Total Orders</h4>
                <p>{stats.total}</p>
              </div>
              <div className="stat-icon">📦</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Pending Bookings</h4>
                <p>{stats.pending}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--warning)', background: 'rgba(255, 159, 28, 0.1)' }}>⏳</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Completed Orders</h4>
                <p>{stats.completed}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--success)', background: 'rgba(46, 196, 182, 0.1)' }}>✅</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Active Complaints</h4>
                <p>{stats.complaints}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--danger)', background: 'rgba(231, 29, 54, 0.1)' }}>⚠️</div>
            </div>
          </div>

          {/* Nearby Shops Section */}
          <div className="glass-panel" style={{ padding: '2rem', marginTop: '2rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', marginBottom: '1.5rem' }}>
              <h3>Nearby Laundry Service Providers</h3>
              <div style={{ display: 'flex', gap: '0.5rem' }}>
                <input 
                  type="text" 
                  className="form-control" 
                  placeholder="Filter by district/region" 
                  style={{ width: '200px', padding: '0.5rem 0.75rem', fontSize: '0.85rem' }} 
                  value={searchDistrict}
                  onChange={(e) => setSearchDistrict(e.target.value)}
                />
                <button className="btn btn-primary" style={{ padding: '0.5rem 1rem', fontSize: '0.85rem' }} onClick={handleSearchShops}>Search</button>
              </div>
            </div>

            {nearbyShops.length === 0 ? (
              <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No active laundry shops found in this region.</p>
            ) : (
              <div className="table-container">
                <table className="custom-table">
                  <thead>
                    <tr>
                      <th>Shop Name</th>
                      <th>District</th>
                      <th>Distance</th>
                      <th>Rating</th>
                      <th>Available Services</th>
                      <th>Hours</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {nearbyShops.map(shop => (
                      <tr key={shop.vendor_id}>
                        <td>
                          <b>{shop.shop_name}</b><br/>
                          <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Owner: {shop.owner_name}</span>
                        </td>
                        <td>{shop.district}</td>
                        <td>📍 {shop.distance} km</td>
                        <td style={{ color: 'var(--warning)', fontWeight: 'bold' }}>⭐ {shop.rating}</td>
                        <td>
                          <div style={{ display: 'flex', gap: '0.25rem', flexWrap: 'wrap' }}>
                            {shop.services.map((s, idx) => (
                              <span key={idx} className="badge badge-info" style={{ fontSize: '0.65rem' }}>{s}</span>
                            ))}
                          </div>
                        </td>
                        <td style={{ fontSize: '0.85rem' }}>{shop.opening_time.substring(0, 5)} - {shop.closing_time.substring(0, 5)}</td>
                        <td>
                          <button className="btn btn-primary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.8rem' }} onClick={() => handleBookService(shop)}>Book Service</button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}

      {/* 2. Book Pickup Page */}
      {subTab === 'book' && (
        <div className="glass-panel" style={{ padding: '2.5rem', maxWidth: '700px', margin: '0 auto', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Book Laundry Pickup</h2>

          {selectedVendor ? (
            <div className="glass-panel" style={{ padding: '1rem', background: 'rgba(138, 43, 226, 0.05)', marginBottom: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <span style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>SELECTED PROVIDER:</span>
                <h4>{selectedVendor.shop_name} ({selectedVendor.district})</h4>
                <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>Distance: {selectedVendor.distance} km | Rating: ⭐ {selectedVendor.rating}</p>
              </div>
              <button className="btn btn-secondary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.8rem' }} onClick={() => setSelectedVendor(null)}>Change Shop</button>
            </div>
          ) : (
            <div style={{
              background: 'rgba(255, 159, 28, 0.12)',
              color: 'var(--warning)',
              padding: '1rem',
              borderRadius: '8px',
              fontSize: '0.9rem',
              marginBottom: '1.5rem'
            }}>
              ⚠️ Please go to the <b>Search Shops</b> or <b>Dashboard</b> tab first and click <b>Book Service</b> next to your preferred shop.
            </div>
          )}

          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          <form onSubmit={handlePlaceBooking}>
            <div className="form-group">
              <label className="form-label">Pickup Address</label>
              <textarea 
                className="form-control" 
                rows="3" 
                value={pickupAddress} 
                onChange={(e) => setPickupAddress(e.target.value)} 
                required
              />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div className="form-group">
                <label className="form-label">Pickup Date</label>
                <input 
                  type="date" 
                  className="form-control" 
                  value={pickupDate} 
                  onChange={(e) => setPickupDate(e.target.value)} 
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label">Pickup Time</label>
                <input 
                  type="time" 
                  className="form-control" 
                  value={pickupTime} 
                  onChange={(e) => setPickupTime(e.target.value)} 
                  required
                />
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div className="form-group">
                <label className="form-label">Approx Clothes Weight (kg)</label>
                <input 
                  type="number" 
                  step="0.1" 
                  min="0.5" 
                  className="form-control" 
                  placeholder="e.g. 3.5"
                  value={clothesWeight} 
                  onChange={(e) => setClothesWeight(e.target.value)} 
                  required
                />
              </div>
              <div className="form-group">
                <label className="form-label">Service Type</label>
                <select className="form-control" value={serviceType} onChange={(e) => setServiceType(e.target.value)}>
                  <option value="Wash Only">Wash Only</option>
                  <option value="Wash & Iron">Wash & Iron</option>
                  <option value="Dry Cleaning">Dry Cleaning</option>
                  <option value="Ironing">Ironing</option>
                </select>
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '2rem' }}>
              <label className="form-label">Special Instructions (Optional)</label>
              <textarea 
                className="form-control" 
                rows="2" 
                placeholder="Fragile fabrics, color separation warnings, etc." 
                value={specialInstructions}
                onChange={(e) => setSpecialInstructions(e.target.value)}
              />
            </div>

            <button 
              type="submit" 
              className="btn btn-primary" 
              style={{ width: '100%', padding: '0.80rem' }}
              disabled={loading || !selectedVendor}
            >
              {loading ? 'Booking order...' : 'Place Booking Request'}
            </button>
          </form>
        </div>
      )}

      {/* 3. Search Shops Tab */}
      {subTab === 'search' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', marginBottom: '2rem' }}>
            <h2>Search Nearby Laundry Shops</h2>
            <div style={{ display: 'flex', gap: '0.5rem' }}>
              <input 
                type="text" 
                className="form-control" 
                placeholder="District or Area Name" 
                value={searchDistrict}
                onChange={(e) => setSearchDistrict(e.target.value)}
                style={{ width: '250px' }}
              />
              <button className="btn btn-primary" onClick={handleSearchShops}>Search Area</button>
            </div>
          </div>

          {nearbyShops.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No laundry providers found matching your query.</p>
          ) : (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '1.5rem' }}>
              {nearbyShops.map(shop => (
                <div key={shop.vendor_id} className="glass-panel" style={{ padding: '1.5rem', display: 'flex', flexDirection: 'column', justifyBetween: 'space-between' }}>
                  <div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '0.5rem' }}>
                      <h4 style={{ color: 'var(--primary)', fontSize: '1.15rem' }}>{shop.shop_name}</h4>
                      <span style={{ color: 'var(--warning)', fontWeight: 'bold' }}>⭐ {shop.rating}</span>
                    </div>
                    <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)', marginBottom: '0.75rem' }}>Owner: {shop.owner_name}</p>
                    <p style={{ fontSize: '0.85rem', marginBottom: '0.5rem' }}>📍 {shop.shop_address} ({shop.district})</p>
                    <p style={{ fontSize: '0.85rem', color: 'var(--primary-light)', fontWeight: 600, marginBottom: '1rem' }}>Distance: {shop.distance} km away</p>
                    
                    <div style={{ display: 'flex', gap: '0.25rem', flexWrap: 'wrap', marginBottom: '1.5rem' }}>
                      {shop.services.map((s, idx) => (
                        <span key={idx} className="badge badge-info" style={{ fontSize: '0.65rem' }}>{s}</span>
                      ))}
                    </div>
                  </div>

                  <div style={{ display: 'flex', gap: '0.5rem', marginTop: 'auto' }}>
                    <button 
                      className="btn btn-secondary" 
                      style={{ flex: 1, padding: '0.5rem', fontSize: '0.85rem' }}
                      onClick={() => alert(`Shop Hours: ${shop.opening_time} to ${shop.closing_time}\nContact: ${shop.contact_number}\nEmail: ${shop.email}`)}
                    >
                      View Details
                    </button>
                    <button 
                      className="btn btn-primary" 
                      style={{ flex: 1, padding: '0.5rem', fontSize: '0.85rem' }}
                      onClick={() => handleBookService(shop)}
                    >
                      Book Service
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* 4. Track Orders Page */}
      {subTab === 'orders' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem' }}>Order History and Tracking</h2>

          {orders.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No laundry bookings recorded.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table">
                <thead>
                  <tr>
                    <th>Tracking Number</th>
                    <th>Vendor Name</th>
                    <th>Pickup Date</th>
                    <th>Clothes Weight</th>
                    <th>Service Type</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {orders.map(order => (
                    <tr key={order.order_id}>
                      <td style={{ fontFamily: 'monospace', fontWeight: 'bold' }}>{order.tracking_number}</td>
                      <td>{order.vendor_name}</td>
                      <td>{order.pickup_date}</td>
                      <td>{order.clothes_weight} kg</td>
                      <td>{order.service_type}</td>
                      <td>
                        <span className={`badge ${order.status === 'Delivered' ? 'badge-active' : 'badge-pending'}`}>
                          {order.status}
                        </span>
                      </td>
                      <td>
                        <span className={`badge ${order.payment_status === 'Paid' ? 'badge-active' : order.payment_status === 'Pending Confirmation' ? 'badge-pending' : 'badge-danger'}`}>
                          {order.payment_status}
                        </span>
                      </td>
                      <td>
                        <div style={{ display: 'flex', gap: '0.5rem' }}>
                          <button 
                            className="btn btn-secondary" 
                            style={{ padding: '0.35rem 0.75rem', fontSize: '0.8rem' }}
                            onClick={() => handleOpenTimeline(order.order_id)}
                          >
                            Track Timeline
                          </button>
                          
                          {order.payment_status === 'Pending Confirmation' && (
                            <button 
                              className="btn btn-primary" 
                              style={{ padding: '0.35rem 0.75rem', fontSize: '0.8rem' }}
                              onClick={async () => {
                                // Fetch order invoice details
                                const res = await fetchWithAuth(`http://localhost:8000/index.php?action=order-details&order_id=${order.order_id}`);
                                const d = await res.json();
                                if (d.success) {
                                  setSelectedInvoice(d.data);
                                }
                              }}
                            >
                              Pay Invoice
                            </button>
                          )}
                          
                          {order.payment_status === 'Paid' && (
                            <button 
                              className="btn btn-secondary" 
                              style={{ padding: '0.35rem 0.75rem', fontSize: '0.8rem' }}
                              onClick={async () => {
                                const res = await fetchWithAuth(`http://localhost:8000/index.php?action=order-details&order_id=${order.order_id}`);
                                const d = await res.json();
                                if (d.success && d.data.invoice) {
                                  // Open raw invoice viewer html in new window
                                  const invoiceId = d.data.invoice.id;
                                  alert(`Total amount was: Rs ${d.data.invoice.total_amount}. Paid! Check notifications.`);
                                }
                              }}
                            >
                              View Invoice
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* 5. Complaints Module */}
      {subTab === 'complaints' && (
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 3fr', gap: '2rem', alignItems: 'start', animation: 'fadeIn 0.4s ease-out' }}>
          {/* File a complaint form */}
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <h3 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Submit Complaint</h3>
            
            {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
            {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

            <form onSubmit={handleSubmitComplaint}>
              <div className="form-group">
                <label className="form-label">Select Order</label>
                <select className="form-control" value={complaintOrderId} onChange={(e) => setComplaintOrderId(e.target.value)} required>
                  <option value="">-- Select Order by Tracking Number --</option>
                  {orders.map(o => (
                    <option key={o.order_id} value={o.order_id}>{o.tracking_number} ({o.service_type})</option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label className="form-label">Complaint Category</label>
                <select className="form-control" value={complaintCategory} onChange={(e) => setComplaintCategory(e.target.value)} required>
                  <option value="Late Delivery">Late Delivery</option>
                  <option value="Damaged Clothes">Damaged Clothes</option>
                  <option value="Missing Item">Missing Item</option>
                  <option value="Wrong Billing">Wrong Billing</option>
                  <option value="Other">Other</option>
                </select>
              </div>

              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label">Detailed Description</label>
                <textarea 
                  className="form-control" 
                  rows="4" 
                  placeholder="Describe your issue with clothes, billing, delivery schedules, etc." 
                  value={complaintDesc}
                  onChange={(e) => setComplaintDesc(e.target.value)}
                  required
                />
              </div>

              <button type="submit" className="btn btn-primary" style={{ width: '100%' }} disabled={loading}>
                {loading ? 'Submitting...' : 'Submit Complaint'}
              </button>
            </form>
          </div>

          {/* Complaints Table list */}
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <h3 style={{ marginBottom: '1.5rem' }}>Your Filed Complaints</h3>
            {complaints.length === 0 ? (
              <p style={{ color: 'var(--text-muted)', padding: '1rem', textAlign: 'center' }}>No complaints filed.</p>
            ) : (
              <div className="table-container">
                <table className="custom-table" style={{ fontSize: '0.9rem' }}>
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Tracking #</th>
                      <th>Category</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th>Admin Resolution</th>
                    </tr>
                  </thead>
                  <tbody>
                    {complaints.map(cmp => (
                      <tr key={cmp.id}>
                        <td>#{cmp.id}</td>
                        <td style={{ fontFamily: 'monospace' }}>{cmp.tracking_number}</td>
                        <td>{cmp.category}</td>
                        <td style={{ fontSize: '0.8rem' }}>{cmp.created_at.substring(0, 10)}</td>
                        <td>
                          <span className={`badge ${cmp.status === 'Resolved' || cmp.status === 'Closed' ? 'badge-active' : 'badge-pending'}`}>
                            {cmp.status}
                          </span>
                        </td>
                        <td style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
                          {cmp.admin_response ? <i>{cmp.admin_response}</i> : <span>Pending Review</span>}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      )}

      {/* 6. Profile Management */}
      {subTab === 'profile' && (
        <div className="glass-panel" style={{ padding: '2.5rem', maxWidth: '600px', margin: '0 auto', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Customer Profile</h2>

          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          <form onSubmit={handleUpdateProfile}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div className="form-group">
                <label className="form-label">Customer ID (Read Only)</label>
                <input type="text" className="form-control" value={`C-${user?.id}`} disabled />
              </div>
              <div className="form-group">
                <label className="form-label">Username (Read Only)</label>
                <input type="text" className="form-control" value={user?.username} disabled />
              </div>
            </div>

            <div className="form-group">
              <label className="form-label">Full Name</label>
              <input type="text" className="form-control" value={user?.details?.full_name} disabled />
            </div>

            <div className="form-group">
              <label className="form-label">Email Address</label>
              <input 
                type="email" 
                className="form-control" 
                value={profileEmail} 
                onChange={(e) => setProfileEmail(e.target.value)} 
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Phone Number</label>
              <input 
                type="text" 
                className="form-control" 
                value={profilePhone} 
                onChange={(e) => setProfilePhone(e.target.value)} 
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Delivery Address</label>
              <textarea 
                className="form-control" 
                rows="3" 
                value={profileAddress} 
                onChange={(e) => setProfileAddress(e.target.value)} 
                required
              />
            </div>

            <div className="form-group" style={{ marginBottom: '2rem' }}>
              <label className="form-label">Update Password (Leave blank to keep current)</label>
              <input 
                type="password" 
                className="form-control" 
                placeholder="Enter new password"
                value={profilePassword} 
                onChange={(e) => setProfilePassword(e.target.value)} 
              />
            </div>

            <button type="submit" className="btn btn-primary" style={{ width: '100%' }} disabled={loading}>
              {loading ? 'Saving details...' : 'Update Details'}
            </button>
          </form>
        </div>
      )}

      {/* Timeline Tracking Popup Modal */}
      {trackingOrder && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '600px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.25rem' }}>Track Order Timeline</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              Tracking #: <b>{trackingOrder.order.tracking_number}</b> | Shop: <b>{trackingOrder.order.shop_name}</b>
            </p>

            <div className="timeline">
              {[
                { label: 'Pending', desc: 'Order placed by customer, waiting shop approval.' },
                { label: 'Accepted', desc: 'Vendor accepted your order.' },
                { label: 'Pickup Scheduled', desc: 'Valet assigned for laundry pickup.' },
                { label: 'Picked Up', desc: 'Clothes successfully collected.' },
                { label: 'Washing', desc: 'Your laundry is in the washing queue.' },
                { label: 'Ironing', desc: 'Clothes are being pressed.' },
                { label: 'Ready', desc: 'Washing and ironing finished.' },
                { label: 'Delivered', desc: 'Clothes delivered to your address.' }
              ].map((step, idx) => {
                const curIdx = getStatusStepIndex(trackingOrder.order.status);
                const isActive = idx <= curIdx;
                return (
                  <div key={idx} className={`timeline-item ${isActive ? 'active' : ''}`}>
                    <div className="timeline-dot"></div>
                    <div className="timeline-content">
                      <h4 style={{ fontSize: '0.95rem', color: isActive ? 'var(--text-main)' : 'var(--text-muted)' }}>{step.label}</h4>
                      <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', margin: 0 }}>{step.desc}</p>
                    </div>
                  </div>
                );
              })}
            </div>

            <div style={{ textAlign: 'right', marginTop: '1.5rem' }}>
              <button className="btn btn-secondary" onClick={() => setTrackingOrder(null)}>Close Timeline</button>
            </div>
          </div>
        </div>
      )}

      {/* Payment and Invoice Confirmation Popup Modal */}
      {selectedInvoice && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '500px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.5rem' }}>Digital Invoice Payment</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1rem' }}>
              Invoice Number: <b>{selectedInvoice.invoice.invoice_number}</b> | Tracking #: <b>{selectedInvoice.order.tracking_number}</b>
            </p>

            {selectedInvoice.order.estimated_weight && parseFloat(selectedInvoice.order.estimated_weight) !== parseFloat(selectedInvoice.order.clothes_weight) && (
               <div style={{
                 background: 'rgba(255, 159, 28, 0.1)',
                 border: '1px solid var(--warning)',
                 color: 'var(--warning)',
                 padding: '0.85rem',
                 borderRadius: '8px',
                 fontSize: '0.85rem',
                 marginBottom: '1.25rem',
                 lineHeight: '1.4',
                 boxShadow: 'inset 0 0 10px rgba(255, 159, 28, 0.05)'
               }}>
                 ⚠️ <b>Notice:</b> The laundry shop measured the actual clothes weight as <b>{parseFloat(selectedInvoice.order.clothes_weight).toFixed(2)} kg</b>, which differed from your initial estimate of <b>{parseFloat(selectedInvoice.order.estimated_weight).toFixed(2)} kg</b>. The billing charges have been calculated based on the actual weight.
               </div>
            )}

            <div style={{
              background: 'rgba(255, 255, 255, 0.05)',
              padding: '1rem',
              borderRadius: '8px',
              border: '1px solid var(--card-border)',
              marginBottom: '1.5rem',
              fontSize: '0.9rem'
            }}>
              <p style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span>Laundry Charges ({selectedInvoice.order.clothes_weight} kg):</span>
                <b>Rs {parseFloat(selectedInvoice.invoice.laundry_charges).toFixed(2)}</b>
              </p>
              <p style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span>Service Charges:</span>
                <b>Rs {parseFloat(selectedInvoice.invoice.service_charges).toFixed(2)}</b>
              </p>
              <p style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '0.5rem' }}>
                <span>Taxes (5%):</span>
                <b>Rs {parseFloat(selectedInvoice.invoice.taxes).toFixed(2)}</b>
              </p>
              <hr style={{ margin: '0.5rem 0', borderColor: 'var(--card-border)' }} />
              <h4 style={{ display: 'flex', justifyContent: 'space-between', color: 'var(--primary-light)' }}>
                <span>Total Due Amount:</span>
                <b>Rs {parseFloat(selectedInvoice.invoice.total_amount).toFixed(2)}</b>
              </h4>
            </div>

            {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem', fontSize: '0.85rem' }}>⚠️ {error}</div>}
            {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem', fontSize: '0.85rem' }}>✅ {success}</div>}

            <form onSubmit={handleConfirmPaymentSubmit}>
              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label" style={{ color: 'var(--text-main)', fontWeight: 'bold' }}>Enter Actual Paid Amount (Rs)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="form-control" 
                  placeholder="Enter paid amount to confirm" 
                  value={actualPaidAmount} 
                  onChange={(e) => setActualPaidAmount(e.target.value)} 
                  required
                />
                <p style={{ fontSize: '0.72rem', color: 'var(--text-muted)', marginTop: '0.3rem' }}>
                  <i>Note: Mismatching the vendor invoice amount will trigger an Admin Fraud Alert.</i>
                </p>
              </div>

              <div style={{ display: 'flex', gap: '1rem' }}>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={loading}>
                  {loading ? 'Confirming...' : 'Confirm Payment'}
                </button>
                <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => { setSelectedInvoice(null); setError(''); }}>
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
