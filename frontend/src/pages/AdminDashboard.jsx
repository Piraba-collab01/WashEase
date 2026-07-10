// washease-frontend/src/pages/AdminDashboard.jsx
import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';

export const AdminDashboard = ({ subTab, setSubTab }) => {
  const { fetchWithAuth } = useAuth();
  //xfdgfhghghjgj
  // Stats
  const [stats, setStats] = useState({
    total_users: 0,
    total_vendors: 0,
    total_customers: 0,
    total_orders: 0,
    completed_orders: 0,
    pending_orders: 0,
    revenue: 0,
    active_complaints: 0
  });

  // Pending Approvals
  const [pendingUsers, setPendingUsers] = useState([]);

  // All Vendors
  const [vendors, setVendors] = useState([]);

  // Warning Vendor state
  const [warningVendor, setWarningVendor] = useState(null);
  const [warningMessage, setWarningMessage] = useState('');

  // All Orders & Filters
  const [orders, setOrders] = useState([]);
  const [filterDate, setFilterDate] = useState('');
  const [filterStatus, setFilterStatus] = useState('');

  // Complaints
  const [complaints, setComplaints] = useState([]);
  const [resolvingComplaint, setResolvingComplaint] = useState(null);
  const [adminResponse, setAdminResponse] = useState('');

  // Commission Rules
  const [bronzePct, setBronzePct] = useState('15.00');
  const [silverPct, setSilverPct] = useState('10.00');
  const [goldPct, setGoldPct] = useState('5.00');

  // Fraud Alerts
  const [fraudAlerts, setFraudAlerts] = useState([]);

  // Reports
  const [reportType, setReportType] = useState('revenue');
  const [reportData, setReportData] = useState(null);

  // Status indicators
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadAdminData();
  }, [subTab]);

  const loadAdminData = async () => {
    setError('');
    try {
      // 1. Fetch Stats
      const resStats = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-stats`);
      const dataStats = await resStats.json();
      if (dataStats.success) {
        setStats(dataStats.data);
      }

      // 2. Fetch Pending Approvals
      const resPending = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-pending-users`);
      const dataPending = await resPending.json();
      if (dataPending.success) {
        setPendingUsers(dataPending.data || []);
      }

      // Fetch All Vendors
      const resVendors = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-list-vendors`);
      const dataVendors = await resVendors.json();
      if (dataVendors.success) {
        setVendors(dataVendors.data || []);
      }

      // 3. Fetch All Orders
      handleQueryOrders();

      // 4. Fetch Complaints
      const resComplaints = await fetchWithAuth(`http://localhost:8000/index.php?action=list-complaints`);
      const dataComplaints = await resComplaints.json();
      if (dataComplaints.success) {
        setComplaints(dataComplaints.data || []);
      }

      // 5. Fetch Commission Rules
      const resRules = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-commission-rules`);
      const dataRules = await resRules.json();
      if (dataRules.success) {
        setBronzePct(dataRules.data.bronze_pct);
        setSilverPct(dataRules.data.silver_pct);
        setGoldPct(dataRules.data.gold_pct);
      }

      // 6. Fetch Fraud Alerts
      const resFraud = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-fraud-alerts`);
      const dataFraud = await resFraud.json();
      if (dataFraud.success) {
        setFraudAlerts(dataFraud.data || []);
      }

      // 7. Load Reports if on report tab
      if (subTab === 'admin-reports') {
        handleQueryReport();
      }

    } catch (err) {
      console.error('Error loading admin dashboard:', err);
    }
  };

  const handleApproveUser = async (userId) => {
    setError('');
    setSuccess('');
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-approve-user`, {
        method: 'POST',
        body: JSON.stringify({ user_id: userId })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      } else {
        setError(data.message);
      }
    } catch (err) {
      console.error(err);
      setError('Approve command failed.');
    }
  };

  const handleRejectUser = async (userId) => {
    setError('');
    setSuccess('');
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-reject-user`, {
        method: 'POST',
        body: JSON.stringify({ user_id: userId })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      } else {
        setError(data.message);
      }
    } catch (err) {
      console.error(err);
      setError('Reject command failed.');
    }
  };

  const handleUpdateVendorStatus = async (vendorId, status, resetCommission = false) => {
    setError('');
    setSuccess('');
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-update-vendor-status`, {
        method: 'POST',
        body: JSON.stringify({
          vendor_id: vendorId,
          status: status,
          reset_commission: resetCommission
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      } else {
        setError(data.message);
      }
    } catch (err) {
      console.error(err);
      setError('Failed to update vendor status.');
    }
  };

  const handleSendWarningSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-send-warning`, {
        method: 'POST',
        body: JSON.stringify({
          vendor_id: warningVendor.vendor_id,
          message: warningMessage
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        setWarningVendor(null);
        setWarningMessage('');
      } else {
        setError(data.message || 'Failed to send warning.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleQueryOrders = async () => {
    try {
      const query = `date=${filterDate}&status=${filterStatus}`;
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=list-orders&${query}`);
      const data = await res.json();
      if (data.success) {
        setOrders(data.data || []);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleUpdateCommission = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-commission-rules`, {
        method: 'POST',
        body: JSON.stringify({
          bronze_pct: bronzePct,
          silver_pct: silverPct,
          gold_pct: goldPct
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      } else {
        setError(data.message || 'Update failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleAssignComplaint = async (complaintId) => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=assign-complaint`, {
        method: 'POST',
        body: JSON.stringify({ complaint_id: complaintId })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleResolveComplaintSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=resolve-complaint`, {
        method: 'POST',
        body: JSON.stringify({
          complaint_id: resolvingComplaint.id,
          admin_response: adminResponse
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        setResolvingComplaint(null);
        setAdminResponse('');
        loadAdminData();
      } else {
        setError(data.message || 'Resolution failed.');
      }
    } catch (err) {
      console.error(err);
      setError('An error occurred.');
    } finally {
      setLoading(false);
    }
  };

  const handleCloseComplaint = async (complaintId) => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=close-complaint`, {
        method: 'POST',
        body: JSON.stringify({ complaint_id: complaintId })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleResolveFraud = async (alertId) => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-resolve-fraud`, {
        method: 'POST',
        body: JSON.stringify({ alert_id: alertId })
      });
      const data = await res.json();
      if (data.success) {
        setSuccess(data.message);
        loadAdminData();
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleQueryReport = async () => {
    try {
      const res = await fetchWithAuth(`http://localhost:8000/index.php?action=admin-reports&type=${reportType}`);
      const data = await res.json();
      if (data.success) {
        setReportData(data.data);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleExportAdminReport = () => {
    window.open(`http://localhost:8000/index.php?action=admin-reports&type=${reportType}&export=pdf`, '_blank');
  };

  return (
    <div className="main-content">
      {/* 1. Admin Home Stats */}
      {subTab === 'admin-dashboard' && (
        <div style={{ animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem' }}>Admin Control Analytics Panel</h2>

          <div className="dashboard-grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))' }}>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Total Users</h4>
                <p>{stats.total_users}</p>
              </div>
              <div className="stat-icon">👥</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Registered Vendors</h4>
                <p>{stats.total_vendors}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--primary-light)' }}>🏪</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Total Customers</h4>
                <p>{stats.total_customers}</p>
              </div>
              <div className="stat-icon">👤</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Total Orders</h4>
                <p>{stats.total_orders}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--warning)' }}>📦</div>
            </div>
          </div>

          <div className="dashboard-grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', marginTop: '1.5rem' }}>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Completed Orders</h4>
                <p>{stats.completed_orders}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--success)' }}>✅</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Pending Orders</h4>
                <p>{stats.pending_orders}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--warning)' }}>⏳</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Total Revenue</h4>
                <p>${parseFloat(stats.revenue).toFixed(2)}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--success)' }}>💵</div>
            </div>
            <div className="glass-panel stat-card">
              <div className="stat-info">
                <h4>Complaints Filed</h4>
                <p>{stats.active_complaints}</p>
              </div>
              <div className="stat-icon" style={{ color: 'var(--danger)' }}>⚠️</div>
            </div>
          </div>
        </div>
      )}

      {/* 2. User/Vendor Verification Approvals */}
      {subTab === 'admin-users' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <h2>Pending Shopkeeper verifications</h2>
          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          {pendingUsers.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '1rem' }}>No shop registrations pending verification.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table">
                <thead>
                  <tr>
                    <th>User ID</th>
                    <th>Owner Name</th>
                    <th>Shop Name</th>
                    <th>Email</th>
                    <th>Contact Phone</th>
                    <th>Role Type</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {pendingUsers.map(u => (
                    <tr key={u.user_id}>
                      <td>#{u.user_id}</td>
                      <td><b>{u.name}</b></td>
                      <td>{u.shop_name || 'N/A'}</td>
                      <td>{u.email}</td>
                      <td>{u.phone}</td>
                      <td>
                        <span className="badge badge-info">{u.role}</span>
                      </td>
                      <td>
                        <div style={{ display: 'flex', gap: '0.5rem' }}>
                          <button className="btn btn-primary" style={{ padding: '0.4rem 0.8rem', fontSize: '0.8rem' }} onClick={() => handleApproveUser(u.user_id)}>Approve</button>
                          <button className="btn btn-danger" style={{ padding: '0.4rem 0.8rem', fontSize: '0.8rem' }} onClick={() => handleRejectUser(u.user_id)}>Reject</button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <h3 style={{ marginTop: '2.5rem', marginBottom: '1rem', color: 'var(--primary)' }}>Registered Vendors Control Panel</h3>
          {vendors.length === 0 ? (
            <p style={{ color: 'var(--text-muted)' }}>No registered vendors found.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table">
                <thead>
                  <tr>
                    <th>Vendor ID</th>
                    <th>Shop Name</th>
                    <th>Owner Name</th>
                    <th>Email</th>
                    <th>Contact Phone</th>
                    <th>Reward Tier</th>
                    <th>Commission Owed</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {vendors.map(v => {
                    const isBlocked = v.status === 'blocked' || parseFloat(v.unpaid_commission || 0) >= 1000;
                    return (
                      <tr key={v.vendor_id} style={isBlocked ? { background: 'rgba(231, 29, 54, 0.05)' } : {}}>
                        <td>V-{v.vendor_id}</td>
                        <td><b>{v.shop_name}</b></td>
                        <td>{v.owner_name}</td>
                        <td>{v.email}</td>
                        <td>{v.contact_number}</td>
                        <td>
                          <span className="badge badge-info" style={{ fontSize: '0.75rem' }}>
                            {v.reward_level} ({parseFloat(v.commission_pct).toFixed(0)}%)
                          </span>
                        </td>
                        <td style={{ color: parseFloat(v.unpaid_commission || 0) >= 1000 ? 'var(--danger)' : 'var(--text-main)', fontWeight: 'bold' }}>
                          Rs {parseFloat(v.unpaid_commission || 0).toFixed(2)}
                        </td>
                        <td>
                          <span className={`badge ${v.status === 'active' ? 'badge-active' : v.status === 'blocked' ? 'badge-danger' : 'badge-pending'}`}>
                            {v.status}
                          </span>
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap' }}>
                            {v.status === 'active' ? (
                              <>
                                <button 
                                  className="btn btn-danger" 
                                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem' }} 
                                  onClick={() => handleUpdateVendorStatus(v.vendor_id, 'inactive')}
                                >
                                  Deactivate
                                </button>
                                <button 
                                  className="btn btn-danger" 
                                  style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem', background: '#d90429' }} 
                                  onClick={() => handleUpdateVendorStatus(v.vendor_id, 'blocked')}
                                >
                                  Block
                                </button>
                              </>
                            ) : (
                              <button 
                                className="btn btn-primary" 
                                style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem' }} 
                                onClick={() => handleUpdateVendorStatus(v.vendor_id, 'active')}
                              >
                                Activate / Unblock
                              </button>
                            )}

                            <button 
                              className="btn btn-secondary" 
                              style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem', border: '1px solid var(--warning)', color: 'var(--warning)' }} 
                              onClick={() => { setWarningVendor(v); setWarningMessage(`Warning: You have outstanding commission of Rs ${parseFloat(v.unpaid_commission || 0).toFixed(2)}. Please clear it to avoid account blocking.`); }}
                            >
                              ⚠️ Warning
                            </button>

                            {parseFloat(v.unpaid_commission || 0) > 0 && (
                              <button 
                                className="btn btn-secondary" 
                                style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem', border: '1px solid var(--success)', color: 'var(--success)' }} 
                                onClick={() => handleUpdateVendorStatus(v.vendor_id, 'active', true)}
                                title="Clear commission dues and set active"
                              >
                                Clear Dues
                              </button>
                            )}
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {/* 3. Commission Settings Management */}
      {subTab === 'admin-commissions' && (
        <div className="glass-panel" style={{ padding: '2.5rem', maxWidth: '600px', margin: '0 auto', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Commission Rate Rules</h2>
          <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem', marginBottom: '2rem' }}>
            Set percentage commissions collected by the platform for each reward level. Level updates automatically reduce vendor commissions based on these rules.
          </p>

          {error && <div style={{ background: 'rgba(231, 29, 54, 0.12)', color: 'var(--danger)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>⚠️ {error}</div>}
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          <form onSubmit={handleUpdateCommission}>
            <div className="form-group">
              <label className="form-label">Bronze Commission Level % (0 - 50 Orders)</label>
              <input type="number" step="0.01" min="0" max="100" className="form-control" value={bronzePct} onChange={(e) => setBronzePct(e.target.value)} required />
            </div>

            <div className="form-group">
              <label className="form-label">Silver Commission Level % (51 - 100 Orders)</label>
              <input type="number" step="0.01" min="0" max="100" className="form-control" value={silverPct} onChange={(e) => setSilverPct(e.target.value)} required />
            </div>

            <div className="form-group" style={{ marginBottom: '2rem' }}>
              <label className="form-label">Gold Commission Level % (101+ Orders)</label>
              <input type="number" step="0.01" min="0" max="100" className="form-control" value={goldPct} onChange={(e) => setGoldPct(e.target.value)} required />
            </div>

            <button type="submit" className="btn btn-primary" style={{ width: '100%', padding: '0.8rem' }} disabled={loading}>
              {loading ? 'Saving rules...' : 'Update Commission Rules'}
            </button>
          </form>
        </div>
      )}

      {/* 4. Fraud Detection Alert Board */}
      {subTab === 'admin-fraud' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <h2 style={{ marginBottom: '1.5rem', color: 'var(--primary)' }}>Fraud Mismatch Alerts Logs</h2>
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          {fraudAlerts.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No payment anomalies or fraud alerts recorded.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table" style={{ fontSize: '0.9rem' }}>
                <thead>
                  <tr>
                    <th>Alert ID</th>
                    <th>Tracking #</th>
                    <th>Vendor Shop</th>
                    <th>Customer Name</th>
                    <th>Vendor Invoice Amount</th>
                    <th>Customer Paid Amount</th>
                    <th>Difference Amount</th>
                    <th>Date Logged</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  {fraudAlerts.map(alert => (
                    <tr key={alert.id} style={{ background: alert.status === 'Pending' ? 'rgba(231, 29, 54, 0.04)' : 'transparent' }}>
                      <td>#{alert.id}</td>
                      <td style={{ fontFamily: 'monospace', fontWeight: 'bold' }}>{alert.tracking_number}</td>
                      <td>{alert.shop_name}</td>
                      <td>{alert.customer_name}</td>
                      <td>${parseFloat(alert.vendor_amount).toFixed(2)}</td>
                      <td style={{ color: 'var(--danger)', fontWeight: 'bold' }}>${parseFloat(alert.customer_amount).toFixed(2)}</td>
                      <td style={{ color: 'var(--accent)', fontWeight: 'bold' }}>${parseFloat(alert.difference).toFixed(2)}</td>
                      <td style={{ fontSize: '0.8rem' }}>{alert.created_at}</td>
                      <td>
                        <span className={`badge ${alert.status === 'Resolved' ? 'badge-active' : 'badge-danger'}`}>
                          {alert.status}
                        </span>
                      </td>
                      <td>
                        {alert.status === 'Pending' && (
                          <button 
                            className="btn btn-secondary" 
                            style={{ padding: '0.35rem 0.6rem', fontSize: '0.75rem' }}
                            onClick={() => handleResolveFraud(alert.id)}
                          >
                            Resolve Alert
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

      {/* 5. Complaints board management */}
      {subTab === 'admin-complaints' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <h2>Complaints Resolution Board</h2>
          {success && <div style={{ background: 'rgba(46, 196, 182, 0.12)', color: 'var(--success)', padding: '0.8rem', borderRadius: '8px', marginBottom: '1.25rem' }}>✅ {success}</div>}

          {complaints.length === 0 ? (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>No complaints filed.</p>
          ) : (
            <div className="table-container">
              <table className="custom-table" style={{ fontSize: '0.9rem' }}>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tracking #</th>
                    <th>Customer Name</th>
                    <th>Shop Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {complaints.map(cmp => (
                    <tr key={cmp.id}>
                      <td>#{cmp.id}</td>
                      <td style={{ fontFamily: 'monospace' }}>{cmp.tracking_number}</td>
                      <td>{cmp.customer_name}</td>
                      <td>{cmp.vendor_name}</td>
                      <td>{cmp.category}</td>
                      <td><p style={{ maxWidth: '200px', fontSize: '0.8rem', color: 'var(--text-muted)' }}>{cmp.description}</p></td>
                      <td>
                        <span className={`badge ${cmp.status === 'Resolved' || cmp.status === 'Closed' ? 'badge-active' : 'badge-pending'}`}>
                          {cmp.status}
                        </span>
                      </td>
                      <td>
                        <div style={{ display: 'flex', gap: '0.25rem' }}>
                          {cmp.status === 'Pending' && (
                            <button className="btn btn-secondary" style={{ padding: '0.3rem 0.5rem', fontSize: '0.75rem' }} onClick={() => handleAssignComplaint(cmp.id)}>Assign</button>
                          )}
                          {(cmp.status === 'Pending' || cmp.status === 'Assigned') && (
                            <button className="btn btn-primary" style={{ padding: '0.3rem 0.5rem', fontSize: '0.75rem' }} onClick={() => setResolvingComplaint(cmp)}>Resolve</button>
                          )}
                          {cmp.status === 'Resolved' && (
                            <button className="btn btn-danger" style={{ padding: '0.3rem 0.5rem', fontSize: '0.75rem' }} onClick={() => handleCloseComplaint(cmp.id)}>Close</button>
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

      {/* 6. Reports Module */}
      {subTab === 'admin-reports' && (
        <div className="glass-panel" style={{ padding: '2rem', animation: 'fadeIn 0.4s ease-out' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '1rem', marginBottom: '2rem' }}>
            <h2>System Analytical Reports</h2>
            <div style={{ display: 'flex', gap: '0.5rem' }}>
              <select className="form-control" style={{ width: '220px' }} value={reportType} onChange={(e) => setReportType(e.target.value)}>
                <option value="revenue">Revenue Report (Payments)</option>
                <option value="vendor">Vendor Performance Report</option>
                <option value="complaints">Complaints Summary Report</option>
                <option value="customer">Customer Activity Report</option>
              </select>
              <button className="btn btn-primary" onClick={handleQueryReport}>Query</button>
              <button className="btn btn-secondary" onClick={handleExportAdminReport}>Export PDF</button>
            </div>
          </div>

          {reportData ? (
            <div>
              <h3>{reportData.title}</h3>
              <div className="table-container">
                <table className="custom-table" style={{ fontSize: '0.88rem' }}>
                  <thead>
                    <tr>
                      {reportData.headers.map((h, idx) => <th key={idx}>{h}</th>)}
                    </tr>
                  </thead>
                  <tbody>
                    {reportData.rows.map((row, rIdx) => (
                      <tr key={rIdx}>
                        {row.map((val, cIdx) => <td key={cIdx}>{val}</td>)}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          ) : (
            <p style={{ textAlign: 'center', color: 'var(--text-muted)', padding: '2rem' }}>Select a report type and click query to view data.</p>
          )}
        </div>
      )}

      {/* Resolving Complaint Dialog Modal */}
      {resolvingComplaint && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '500px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.25rem' }}>Resolve Customer Complaint</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              Complaint ID: <b>#{resolvingComplaint.id}</b> | Customer: <b>{resolvingComplaint.customer_name}</b>
            </p>

            <div style={{
              background: 'rgba(255, 255, 255, 0.05)',
              padding: '1rem',
              borderRadius: '8px',
              border: '1px solid var(--card-border)',
              marginBottom: '1.5rem',
              fontSize: '0.88rem'
            }}>
              <p><b>Category:</b> {resolvingComplaint.category}</p>
              <p style={{ marginTop: '0.5rem' }}><b>Description:</b> <i>{resolvingComplaint.description}</i></p>
            </div>

            <form onSubmit={handleResolveComplaintSubmit}>
              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label">Admin Resolution Response Description</label>
                <textarea 
                  className="form-control" 
                  rows="4" 
                  placeholder="Enter details of resolution. This will notify the customer." 
                  value={adminResponse} 
                  onChange={(e) => setAdminResponse(e.target.value)} 
                  required
                />
              </div>

              <div style={{ display: 'flex', gap: '1rem' }}>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={loading}>
                  {loading ? 'Resolving...' : 'Submit Resolution'}
                </button>
                <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => setResolvingComplaint(null)}>
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Send Warning Message Dialog Modal */}
      {warningVendor && (
        <div className="modal-overlay">
          <div className="modal-content glass-panel" style={{ maxWidth: '500px', animation: 'fadeIn 0.3s ease-out' }}>
            <h3 style={{ color: 'var(--primary)', marginBottom: '0.25rem' }}>Send Warning to Vendor</h3>
            <p style={{ color: 'var(--text-muted)', fontSize: '0.85rem', marginBottom: '1.5rem' }}>
              Shop Name: <b>{warningVendor.shop_name}</b> | Owner: <b>{warningVendor.owner_name}</b>
            </p>

            <form onSubmit={handleSendWarningSubmit}>
              <div className="form-group" style={{ marginBottom: '1.5rem' }}>
                <label className="form-label">Warning Message Description</label>
                <textarea 
                  className="form-control" 
                  rows="4" 
                  placeholder="Enter details of warning message. This will notify the vendor." 
                  value={warningMessage} 
                  onChange={(e) => setWarningMessage(e.target.value)} 
                  required
                />
              </div>

              <div style={{ display: 'flex', gap: '1rem' }}>
                <button type="submit" className="btn btn-primary" style={{ flex: 1 }} disabled={loading}>
                  {loading ? 'Sending...' : 'Send Warning'}
                </button>
                <button type="button" className="btn btn-secondary" style={{ flex: 1 }} onClick={() => setWarningVendor(null)}>
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
