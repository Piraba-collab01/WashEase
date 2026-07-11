// washease-frontend/src/pages/VendorDashboard.jsx
import React, { useState, useEffect } from 'react';
import { useAuth, API_URL } from '../context/AuthContext';

export const VendorDashboard = ({ subTab, setSubTab }) => {
  const { user, fetchWithAuth, checkAuth } = useAuth();
  const [stats, setStats] = useState({ total: 0, today: 0, revenue: 0, level: 'Bronze', commission: 15.00 });
  const [orders, setOrders] = useState([]);
  const [rewardStats, setRewardStats] = useState({ completed_orders: 0, current_level: 'Bronze', current_commission: 15.00, next_level: 'Silver', target_orders: 51, progress: 0 });
  
  // Billing form state
  const [billingOrder, setBillingOrder] = useState(null);
  const [serviceCharge, setServiceCharge] = useState('5.00');
  const [additionalCharge, setAdditionalCharge] = useState('0.00');
  const [actualWeight, setActualWeight] = useState('');

  // Reports state
  const [reportPeriod, setReportPeriod] = useState('daily');
  const [reportData, setReportData] = useState(null);

  // Profile Form State
  const [profileEmail, setProfileEmail] = useState(user?.email || '');
  const [profilePhone, setProfilePhone] = useState(user?.details?.contact_number || '');
  const [profileAddress, setProfileAddress] = useState(user?.details?.shop_address || '');
  const [profileShopName, setProfileShopName] = useState(user?.details?.shop_name || '');
  const [profileOwnerName, setProfileOwnerName] = useState(user?.details?.owner_name || '');
  const [profileOpenTime, setProfileOpenTime] = useState(user?.details?.opening_time || '08:00');
  const [profileCloseTime, setProfileCloseTime] = useState(user?.details?.closing_time || '20:00');
  const [profilePassword, setProfilePassword] = useState('');

  // Commission Payment Modal State
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [paymentAmount, setPaymentAmount] = useState('');
  const [paymentRef, setPaymentRef] = useState('');

  // UI status feedback
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadVendorData();
  }, [subTab]);

  const loadVendorData = async () => {
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

      // 2. Fetch Rewards Stats
      const resRew = await fetchWithAuth(`http://localhost:8000/index.php?action=vendor-rewards`);
      const dataRew = await resRew.json();
      if (dataRew.success) {
        setRewardStats(dataRew.data);
      }

      // 3. Compute Home Stats
      // Today's orders count
      const todayDate = new Date().toISOString().substring(0, 10);
      const todayOrders = ordersList.filter(o => o.created_at.substring(0, 10) === todayDate).length;
      
      // Calculate revenue from details (Delivered + Paid orders)
      let revenueSum = 0;
      for (const ord of ordersList) {
        if (ord.payment_status === 'Paid') {
          // Fetch details to get total_amount or calculate estimated
          // Since we might not want to fetch details for all orders immediately, we can query it or show a sum.
          // For simplicity we can fetch the reports summary or mock revenue.
          // Let's call the reports controller daily/weekly/monthly for overall summary.
        }
      }

      // Instead of manual summing, fetch reports summary for monthly period
      const resRep = await fetchWithAuth(`http://localhost:8000/index.php?action=vendor-reports&period=monthly`);
      const dataRep = await resRep.json();
      let rev = 0;
      if (dataRep.success) {
        rev = dataRep.data.summary.total_revenue;
        if (subTab === 'vendor-reports') {
          setReportData(dataRep.data);
        }
      }

      setStats({
        total: ordersList.length,
        today: todayOrders,
        revenue: rev,
        level: dataRew.data?.current_level || 'Bronze',
        commission: dataRew.data?.current_commission || 15.00
      });

    } catch (err) {
      console.error(err);
    }
  };

  const handlePaymentSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=vendor-submit-commission-payment`, {
        method: 'POST',
        body: JSON.stringify({
          amount: parseFloat(paymentAmount),
          transaction_ref: paymentRef
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        setShowPaymentModal(false);
        setPaymentAmount('');
        setPaymentRef('');
        loadVendorData();
      } else {
        setError(data.message || 'Failed to submit payment.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateStatus = async (orderId, newStatus) => {
    setError('');
    setSuccess('');
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=update-order-status&order_id=${orderId}`, {
        method: 'POST',
        body: JSON.stringify({ status: newStatus })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadVendorData();
      } else {
        setError(data.message);
      }
    } catch (err) {
      console.error(err);
      setError('Failed to update status.');
    }
  };

  const handleGenerateInvoice = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=create-bill&order_id=${billingOrder.order_id}`, {
        method: 'POST',
        body: JSON.stringify({
          service_charge: serviceCharge,
          additional_charge: additionalCharge,
          actual_weight: actualWeight
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        setBillingOrder(null);
        setServiceCharge('5.00');
        setAdditionalCharge('0.00');
        setActualWeight('');
        loadVendorData();
      } else {
        setError(data.message || 'Invoice generation failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleQueryReport = async () => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=vendor-reports&period=${reportPeriod}`);
      const data = await res.json();
      if (data.success) {
        setReportData(data.data);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleExportPDF = () => {
    // Open a new tab pointing directly to the export action API
    window.open(`http://localhost:8000/index.php?action=vendor-reports&period=${reportPeriod}&export=pdf`, '_blank');
  };

  const handleUpdateVendorProfile = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=vendor-profile`, {
        method: 'POST',
        body: JSON.stringify({
          email: profileEmail,
          contact_number: profilePhone,
          shop_name: profileShopName,
          owner_name: profileOwnerName,
          shop_address: profileAddress,
          opening_time: profileOpenTime,
          closing_time: profileCloseTime,
          password: profilePassword
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess('Profile updated successfully.');
        setProfilePassword('');
        checkAuth();
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

  return (
    <div className="main-content">
      {/* 1. Dashboard Summary Cards */}
      {subTab === 'vendor-dashboard' && (
        <div style={{ animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span>Welcome, {user?.details?.shop_name}! 🏪</span>
            <span style={{ fontSize: '1rem', color: 'var(--text-muted)' }}>Vendor ID: V-{user?.id}</span>
          </h2>

          {parseFloat(rewardStats.unpaid_commission || 0) >= 1000 && (
            <div style={{
              background: 'rgba(231, 29, 54, 0.15)',
              color: 'var(--danger)',
              border: '2px dashed var(--danger)',
              padding: '1.25rem',
              borderRadius: '12px',
              marginBottom: '2rem',
              fontWeight: 'bold',
              display: 'flex',
              flexDirection: 'column',
              gap: '0.5rem',
              boxShadow: '0 0 20px rgba(231, 29, 54, 0.15)',
              animation: 'pulse 2s infinite'
            }}>
              <div style={{ fontSize: '1.2rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>🚫 Account Blocked!</div>
              <div style={{ fontWeight: 'normal', fontSize: '0.95rem' }}>
                Your laundry shop account has been <b>BLOCKED</b> because your unpaid commission has reached <b>Rs {parseFloat(rewardStats.unpaid_commission).toFixed(2)}</b> (Limit: Rs 1000.00). 
                You are temporarily disabled from managing new order statuses or generating new bills. Please contact administration and clear your dues to reactivate your account.
              </div>
            </div>
          )}

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
                <h4>Today's Bookings</h4>
                <p>{stats.today}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--warning)', background: 'rgba(255, 159, 28, 0.1)' }}>⏰</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Monthly Revenue</h4>
                <p>${parseFloat(stats.revenue).toFixed(2)}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--success)', background: 'rgba(46, 196, 182, 0.1)' }}>💵</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Reward Tier</h4>
                <p style={{ fontSize: '1.45rem', fontWeight: 800 }}>{stats.level} ({stats.commission}%)</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--primary-light)', background: 'rgba(138, 43, 226, 0.1)' }}>🏆</div>
            </div>
            <div className="glass-panel stat-card" style={parseFloat(rewardStats.unpaid_commission || 0) >= 1000 ? { border: '2px solid var(--danger)', boxShadow: '0 0 15px rgba(231, 29, 54, 0.3)' } : {}}>
              <div className="stat-info" style={{ width: '100%' }}>
                <h4>Unpaid Commission</h4>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '0.5rem' }}>
                  <p style={{ color: parseFloat(rewardStats.unpaid_commission || 0) >= 1000 ? 'var(--danger)' : 'var(--text-main)', fontWeight: 800, margin: 0 }}>
                    Rs {parseFloat(rewardStats.unpaid_commission || 0).toFixed(2)}
                  </p>
                  {parseFloat(rewardStats.unpaid_commission || 0) > 0 && (
                    <button 
                      className="btn btn-primary" 
                      style={{ padding: '0.3rem 0.6rem', fontSize: '0.7rem', fontWeight: 'bold' }}
                      onClick={() => setShowPaymentModal(true)}
                    >
                      Clear Dues
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Quick Orders List */}
          <div className="glass-panel" style={{ padding: '2rem', marginTop: '2rem' }}>
            <h3>Recent Booking Requests</h3>
            {orders.length === 0 ? (
              <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No orders currently booked at your shop.</p>
            ) : (
              <div className="table-container">
                <table className="custom-table">
                  <thead>
                    <tr>
                      <th>Tracking Number</th>
                      <th>Customer Name</th>
                      <th>Pickup Date</th>
                      <th>Weight (kg)</th>
                      <th>Service Type</th>
                      <th>Status</th>
                      <th>Payment Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {orders.slice(0, 5).map(order => (
                      <tr key={order.order_id}>
                        <td style={{ fontFamily: 'monospace', fontWeight: 'bold' }}>{order.tracking_number}</td>
                        <td>{order.customer_name}</td>
                        <td>{order.pickup_date}</td>
                        <td>{order.clothes_weight} kg</td>
                        <td>{order.service_type}</td>
                        <td>
                          <select 
                            className="form-control" 
                            style={{ padding: '0.25rem 0.5rem', width: '150px', fontSize: '0.85rem' }} 
                            value={order.status}
                            disabled={parseFloat(rewardStats.unpaid_commission || 0) >= 1000}
                            onChange={(e) => handleUpdateStatus(order.order_id, e.target.value)}
                          >
                            <option value="Pending">Pending</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Pickup Scheduled">Pickup Scheduled</option>
                            <option value="Picked Up">Picked Up</option>
                            <option value="Washing">Washing</option>
                            <option value="Ironing">Ironing</option>
                            <option value="Ready">Ready</option>
                            <option value="Delivered">Delivered</option>
                          </select>
                        </td>
                        <td>
                          <span className={`badge ${order.payment_status === 'Paid' ? 'badge-active' : order.payment_status === 'Pending Confirmation' ? 'badge-pending' : 'badge-danger'}`}>
                            {order.payment_status}
                          </span>
                        </td>
                        <td>
                          {order.payment_status === 'Unpaid' && (
                            <button 
                              className="btn btn-primary" 
                              style={{ padding: '0.35rem 0.6rem', fontSize: '0.8rem' }}
                              disabled={parseFloat(rewardStats.unpaid_commission || 0) >= 1000}
                              onClick={() => { setBillingOrder(order); setActualWeight(order.clothes_weight); }}
                            >
                              Generate Bill
                            </button>
                          )}
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

      {/* 2. Orders List Page */}
      {subTab === 'vendor-orders' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <h2>All Orders Management</h2>
          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          {orders.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No orders found.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table">
                <thead>
                  <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Pickup Date</th>
                    <th>Weight</th>
                    <th>Service Type</th>
                    <th>Status Option</th>
                    <th>Payment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {orders.map(order => (
                    <tr key={order.order_id}>
                      <td>#{order.order_id}</td>
                      <td>{order.customer_name}</td>
                      <td>{order.pickup_date} {order.pickup_time.substring(0, 5)}</td>
                      <td>{order.clothes_weight} kg</td>
                      <td>{order.service_type}</td>
                      <td>
                        <select 
                          className="form-control" 
                          style={{ padding: '0.25rem 0.5rem', width: '160px', fontSize: '0.85rem' }} 
                          value={order.status}
                          disabled={parseFloat(rewardStats.unpaid_commission || 0) >= 1000}
                          onChange={(e) => handleUpdateStatus(order.order_id, e.target.value)}
                        >
                          <option value="Pending">Pending</option>
                          <option value="Accepted">Accepted</option>
                          <option value="Pickup Scheduled">Pickup Scheduled</option>
                          <option value="Picked Up">Picked Up</option>
                          <option value="Washing">Washing</option>
                          <option value="Ironing">Ironing</option>
                          <option value="Ready">Ready</option>
                          <option value="Delivered">Delivered</option>
                        </select>
                      </td>
                      <td>
                        <span className={`badge ${order.payment_status === 'Paid' ? 'badge-active' : order.payment_status === 'Pending Confirmation' ? 'badge-pending' : 'badge-danger'}`}>
                          {order.payment_status}
                        </span>
                      </td>
                      <td>
                        {order.payment_status === 'Unpaid' && (
                          <button 
                            className="btn btn-primary" 
                            style={{ padding: '0.35rem 0.6rem', fontSize: '0.8rem' }}
                            disabled={parseFloat(rewardStats.unpaid_commission || 0) >= 1000}
                            onClick={() => { setBillingOrder(order); setActualWeight(order.clothes_weight); }}
                          >
                            Generate Bill
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* 3. Reward Levels Information */}
      {subTab === 'vendor-rewards' && (
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 3fr', gap: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          {/* Current Level Status */}
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <h3 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Your Commission Tiers</h3>
            
            <div style={{ marginBottom: '2rem', textAlign: 'center' }}>
              <div style={{ fontSize: '3rem' }}>🏆</div>
              <h2 style={{ fontSize: '2.25rem', color: 'var(--primary-light)', fontWeight: 800 }}>{rewardStats.current_level}</h2>
              <p style={{ color: 'var(--text-muted)' }}>Current Reward Level</p>
              <h4 style={{ color: 'var(--success)', fontSize: '1.25rem', marginTop: '0.5rem' }}>Commission Rate: {rewardStats.current_commission}%</h4>
            </div>

            <div style={{ marginBottom: '1rem' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.9rem', marginBottom: '0.5rem' }}>
                <span>Completed Orders: <b>{rewardStats.completed_orders}</b></span>
                <span>Target for {rewardStats.next_level}: <b>{rewardStats.target_orders}</b></span>
              </div>
              
              {/* Progress bar */}
              <div style={{ width: '100%', height: '12px', background: 'var(--card-border)', borderRadius: '50px', overflow: 'hidden' }}>
                <div style={{ width: `${rewardStats.progress}%`, height: '100%', background: 'linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%)', borderRadius: '50px' }}></div>
              </div>
              <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '0.5rem', textAlign: 'center' }}>
                Completed orders automatically upgrade your reward level, reducing WashEase commission rates!
              </p>
            </div>
          </div>

          {/* Reward Levels details */}
          <div className="glass-panel" style={{ padding: '2rem' }}>
            <h3 style={{ marginBottom: '1.5rem' }}>Reward System Rules</h3>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
              <div className="glass-panel" style={{ padding: '1rem', borderLeft: '5px solid #cd7f32', background: 'rgba(205, 127, 50, 0.05)' }}>
                <h4>Bronze Tier</h4>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>Completed Orders: <b>0 - 50 Orders</b></p>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-main)', marginTop: '0.25rem' }}>Standard commission rate: <b>15.00%</b></p>
              </div>

              <div className="glass-panel" style={{ padding: '1rem', borderLeft: '5px solid #c0c0c0', background: 'rgba(192, 192, 192, 0.05)' }}>
                <h4>Silver Tier</h4>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>Completed Orders: <b>51 - 100 Orders</b></p>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-main)', marginTop: '0.25rem' }}>Reduced commission rate: <b>10.00%</b></p>
              </div>

              <div className="glass-panel" style={{ padding: '1rem', borderLeft: '5px solid #ffd700', background: 'rgba(255, 215, 0, 0.05)' }}>
                <h4>Gold Tier</h4>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-muted)' }}>Completed Orders: <b>101+ Orders</b></p>
                <p style={{ fontSize: '0.9rem', color: 'var(--text-main)', marginTop: '0.25rem' }}>Premium commission rate: <b>5.00%</b></p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* 4. Reports Tab */}
      {subTab === 'vendor-reports' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', marginBottom: '2rem' }}>
            <h2>Vendor Sales Reports</h2>
            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
              <select className="form-control" style={{ width: '150px' }} value={reportPeriod} onChange={(e) => setReportPeriod(e.target.value)}>
                <option value="daily">Daily Report</option>
                <option value="weekly">Weekly Report</option>
                <option value="monthly">Monthly Report</option>
              </select>
              <button className="btn btn-primary" onClick={handleQueryReport}>Query</button>
              <button className="btn btn-secondary" onClick={handleExportPDF}>Export PDF</button>
            </div>
          </div>

          {reportData ? (
            <div>
              {/* Summary Cards */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1.5rem', marginBottom: '2rem' }}>
                <div className="glass-panel" style={{ padding: '1.5rem', textAlign: 'center' }}>
                  <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>TOTAL REVENUE</span>
                  <h3>${parseFloat(reportData.summary.total_revenue).toFixed(2)}</h3>
                </div>
                <div className="glass-panel" style={{ padding: '1.5rem', textAlign: 'center' }}>
                  <span style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>TOTAL ORDERS</span>
                  <h3>{reportData.summary.total_orders} Orders</h3>
                </div>
              </div>

              {/* Data Table */}
              <div className="table-container">
                <table className="custom-table">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Tracking Number</th>
                      <th>Customer Name</th>
                      <th>Service Type</th>
                      <th>Clothes Weight</th>
                      <th>Status</th>
                      <th>Invoice Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reportData.orders.map(o => (
                      <tr key={o.order_id}>
                        <td>#{o.order_id}</td>
                        <td style={{ fontFamily: 'monospace' }}>{o.tracking_number}</td>
                        <td>{o.customer_name || 'N/A'}</td>
                        <td>{o.service_type}</td>
                        <td>{o.clothes_weight} kg</td>
                        <td>{o.status}</td>
                        <td>{o.total_amount ? `$${parseFloat(o.total_amount).toFixed(2)}` : 'N/A'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ) : (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>Please select a period and click query to fetch reports.</p>
          )}
        </div>
      )}

      {/* 5. Profile Management */}
      {subTab === 'vendor-profile' && (
        <div className="glass-panel" style={{ padding: '2.5rem', maxWidth: '600px', margin: '0 auto', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Vendor Shop Profile</h2>

          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          <form onSubmit={handleUpdateVendorProfile}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div className="form-group">
                <label className="form-label">Vendor ID</label>
                <input type="text" className="form-control" value={`V-${user?.id}`} disabled />
              </div>
              <div className="form-group">
                <label className="form-label">Username</label>
                <input type="text" className="form-control" value={user?.username} disabled />
              </div>
            </div>

            <div className="form-group">
              <label className="form-label">Shop Name</label>
              <input type="text" className="form-control" value={profileShopName} onChange={(e) => setProfileShopName(e.target.value)} required />
            </div>

            <div className="form-group">
              <label className="form-label">Owner Full Name</label>
              <input type="text" className="form-control" value={profileOwnerName} onChange={(e) => setProfileOwnerName(e.target.value)} required />
            </div>

            <div className="form-group">
              <label className="form-label">Email Address</label>
              <input type="email" className="form-control" value={profileEmail} onChange={(e) => setProfileEmail(e.target.value)} required />
            </div>

            <div className="form-group">
              <label className="form-label">Contact Phone</label>
              <input type="text" className="form-control" value={profilePhone} onChange={(e) => setProfilePhone(e.target.value)} required />
            </div>

            <div className="form-group">
              <label className="form-label">Shop Location Address</label>
              <textarea className="form-control" rows="2" value={profileAddress} onChange={(e) => setProfileAddress(e.target.value)} required />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
              <div className="form-group">
                <label className="form-label">Opening Time</label>
                <input type="time" className="form-control" value={profileOpenTime} onChange={(e) => setProfileOpenTime(e.target.value)} required />
              </div>
              <div className="form-group">
                <label className="form-label">Closing Time</label>
                <input type="time" className="form-control" value={profileCloseTime} onChange={(e) => setProfileCloseTime(e.target.value)} required />
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '2rem' }}>
              <label className="form-label">Change Password (Leave blank to keep current)</label>
              <input type="password" className="form-control" placeholder="New Password" value={profilePassword} onChange={(e) => setProfilePassword(e.target.value)} />
            </div>

            <button type="submit" className="btn btn-primary" style={{ width: '100%' }}>Update Shop Details</button>
          </form>
        </div>
      )}

      {/* Generate Bill Modal dialog */}
      {billingOrder && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '500px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.25rem' }}>Order Invoice Billing</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              Order: <b>#{billingOrder.order_id}</b> | Tracking: <b>{billingOrder.tracking_number}</b>
            </p>

            <div style={{
              background: 'rgba(255, 255, 255, 0.05)',
              padding: '1rem',
              borderRadius: '8px',
              border: '1px solid var(--card-border)',
              marginBottom: '1.5rem',
              fontSize: '0.9rem'
            }}>
              <p style={{ display: 'flex', justify: 'space-between', marginBottom: '0.3rem' }}>
                <span>Service Type:</span>
                <b>{billingOrder.service_type}</b>
              </p>
              <p style={{ display: 'flex', justify: 'space-between', marginBottom: '0.3rem' }}>
                <span>Clothes Weight:</span>
                <b>{billingOrder.clothes_weight} kg</b>
              </p>
              <p style={{ display: 'flex', justify: 'space-between', marginBottom: '0.3rem' }}>
                <span>Estimated Rate per kg:</span>
                <b>$10.00 / kg</b>
              </p>
              <hr style={{ margin: '0.5rem 0', borderColor: 'var(--card-border)' }} />
              <h4 style={{ display: 'flex', justify: 'space-between', color: 'var(--success)' }}>
                <span>Base Laundry Charges:</span>
                <b>${((parseFloat(actualWeight) || 0) * 10).toFixed(2)}</b>
              </h4>
            </div>

            <form onSubmit={handleGenerateInvoice}>
              <div className="form-group">
                <label className="form-label">Actual Measured Weight (kg)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="form-control" 
                  value={actualWeight} 
                  onChange={(e) => setActualWeight(e.target.value)} 
                  placeholder={`Customer estimate: ${billingOrder.clothes_weight} kg`}
                  required
                />
              </div>

              <div className="form-group">
                <label className="form-label">Service Fee ($)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="form-control" 
                  value={serviceCharge} 
                  onChange={(e) => setServiceCharge(e.target.value)} 
                  required
                />
              </div>

              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label">Additional Surcharge Fee ($)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="form-control" 
                  value={additionalCharge} 
                  onChange={(e) => setAdditionalCharge(e.target.value)} 
                  required
                />
              </div>

              <div style={{ display: 'flex', gap: '1rem' }}>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={loading}>
                  {loading ? 'Creating Bill...' : 'Generate Invoice'}
                </button>
                <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => setBillingOrder(null)}>
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Commission Payment Submission Modal */}
      {showPaymentModal && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '500px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.25rem' }}>Submit Commission Payment</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              Transfer the outstanding dues to our central bank account and provide the reference.
            </p>

            <div style={{
              background: 'rgba(255, 255, 255, 0.05)',
              padding: '1rem',
              borderRadius: '8px',
              border: '1px solid var(--card-border)',
              marginBottom: '1.5rem',
              fontSize: '0.85rem'
            }}>
              <p><b>Bank Name:</b> WashEase Central Trust Bank</p>
              <p><b>Account Number:</b> 1230-5847-9201-4827</p>
              <p><b>Branch:</b> Colombo Head Office</p>
              <p style={{ marginTop: '0.5rem', color: 'var(--warning)', fontWeight: 'bold' }}>
                Please transfer exactly Rs {parseFloat(rewardStats.unpaid_commission || 0).toFixed(2)}
              </p>
            </div>

            <form onSubmit={handlePaymentSubmit}>
              <div className="form-group" style={{ marginBottom: '1rem' }}>
                <label className="form-label">Payment Amount (Rs)</label>
                <input 
                  type="number" 
                  step="0.01" 
                  className="form-control" 
                  value={paymentAmount} 
                  onChange={(e) => setPaymentAmount(e.target.value)} 
                  placeholder={`e.g. ${parseFloat(rewardStats.unpaid_commission || 0).toFixed(2)}`}
                  required
                />
              </div>

              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label">Transaction Reference ID / Bank Slip Number</label>
                <input 
                  type="text" 
                  className="form-control" 
                  value={paymentRef} 
                  onChange={(e) => setPaymentRef(e.target.value)} 
                  placeholder="e.g. TXN9876543210"
                  required
                />
              </div>

              <div style={{ display: 'flex', gap: '1rem' }}>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={loading}>
                  {loading ? 'Submitting...' : 'Submit Confirmation'}
                </button>
                <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => setShowPaymentModal(false)}>
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
