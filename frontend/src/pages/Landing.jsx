import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import laundryBg from '../assets/laundry-bg.png';
import laundryHeroPurple from '../assets/laundry-hero-purple.png';

export const Landing = ({ setCurrentTab, setRegisterRole }) => {
  const { user } = useAuth();
  const [copiedCode, setCopiedCode] = useState('');

  const handleCopyCode = (code) => {
    navigator.clipboard.writeText(code);
    setCopiedCode(code);
    setTimeout(() => {
      setCopiedCode('');
    }, 1500);
  };

  const handleCustomerCTA = () => {
    if (user) {
      if (user.role === 'customer') setCurrentTab('dashboard');
      else if (user.role === 'vendor') setCurrentTab('vendor-dashboard');
      else setCurrentTab('admin-dashboard');
    } else {
      if (setRegisterRole) setRegisterRole('customer');
      setCurrentTab('register');
    }
  };

  const handleVendorCTA = () => {
    if (user) {
      if (user.role === 'vendor') setCurrentTab('vendor-dashboard');
      else if (user.role === 'customer') setCurrentTab('dashboard');
      else setCurrentTab('admin-dashboard');
    } else {
      if (setRegisterRole) setRegisterRole('vendor');
      setCurrentTab('register');
    }
  };

  return (
    <div className="landing-page">
      {/* Hero Section */}
      <header className="landing-hero" style={{ backgroundImage: `url(${laundryBg})` }}>
        <div className="hero-overlay"></div>
        <div className="hero-content-wrapper main-content" style={{ display: 'flex', gap: '3rem', alignItems: 'center', justifyContent: 'space-between', zIndex: 2, position: 'relative', width: '100%', flexWrap: 'wrap' }}>
          <div className="hero-card glass-panel" style={{ flex: '1 1 500px', maxWidth: '650px', padding: '3rem' }}>
            <span className="hero-badge">🧼 Smart Laundry Platform</span>
            <h1 className="hero-title">
              Fresh Clothes,<br />
              <span className="gradient-text">Zero Effort.</span>
            </h1>
            <p className="hero-description">
              WashEase is the ultimate marketplace connecting you with the finest local laundry professionals. 
              Schedule pickups, track your wash cycle, and pay transparently—all from the comfort of your home.
            </p>

            <div className="hero-ctas">
              <button className="btn btn-primary btn-lg" onClick={handleCustomerCTA}>
                {user ? 'Go to Dashboard' : 'Find Nearby Shops 🔍'}
              </button>
              {!user && (
                <button className="btn btn-secondary btn-lg" onClick={handleVendorCTA}>
                  Join as Vendor Partner 💼
                </button>
              )}
            </div>

            <div className="hero-stats">
              <div className="stat-item">
                <span className="stat-num">50+</span>
                <span className="stat-label">Verified Shops</span>
              </div>
              <div className="stat-item">
                <span className="stat-num">10k+</span>
                <span className="stat-label">Happy Customers</span>
              </div>
              <div className="stat-item">
                <span className="stat-num">99.8%</span>
                <span className="stat-label">On-time Delivery</span>
              </div>
            </div>
          </div>
          <div className="hero-image-container animate-float" style={{ flex: '1 1 400px', display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
            <img 
              src={laundryHeroPurple} 
              alt="WashEase Smart Laundry" 
              style={{ maxWidth: '100%', maxHeight: '480px', objectFit: 'contain', borderRadius: '24px', boxShadow: '0 20px 40px rgba(138, 43, 226, 0.35)', border: '1px solid rgba(138, 43, 226, 0.2)' }}
            />
          </div>
        </div>
      </header>

      {/* Features Section */}
      <section className="landing-section features-section main-content">
        <div className="section-header">
          <h2 className="section-title">Why Choose WashEase?</h2>
          <p className="section-subtitle">We make laundry day the easiest day of your week.</p>
        </div>

        <div className="features-grid">
          <div className="feature-card glass-panel">
            <div className="feature-icon-wrapper">
              <span className="feature-icon">📍</span>
            </div>
            <h3>Smart Location Search</h3>
            <p>Find the best laundry service providers closest to you using GPS-based distance sorting.</p>
          </div>

          <div className="feature-card glass-panel">
            <div className="feature-icon-wrapper">
              <span className="feature-icon">📅</span>
            </div>
            <h3>Scheduled Pickups</h3>
            <p>Select flexible pickup and delivery slots that perfectly match your daily schedule.</p>
          </div>

          <div className="feature-card glass-panel">
            <div className="feature-icon-wrapper">
              <span className="feature-icon">🧾</span>
            </div>
            <h3>Digital Billing</h3>
            <p>Receive clear digital invoice breakdowns. No hidden costs or surprise surcharges.</p>
          </div>

          <div className="feature-card glass-panel">
            <div className="feature-icon-wrapper">
              <span className="feature-icon">🛡️</span>
            </div>
            <h3>Secure Tracking</h3>
            <p>Rest easy with automated mismatch payment warnings and an active dispute resolution board.</p>
          </div>
        </div>
      </section>

      {/* How it Works Section */}
      <section className="landing-section process-section">
        <div className="main-content">
          <div className="section-header">
            <h2 className="section-title">How It Works</h2>
            <p className="section-subtitle">Get fresh clothes in three simple steps</p>
          </div>

          <div className="process-timeline">
            <div className="process-step">
              <div className="step-number-glow">1</div>
              <h3>Browse & Select</h3>
              <p>Compare local shops by distance, ratings, prices, and services offered.</p>
            </div>
            <div className="process-step">
              <div className="step-number-glow">2</div>
              <h3>Schedule Pick-Up</h3>
              <p>Pick a convenient time for the laundry vendor to collect your clothes.</p>
            </div>
            <div className="process-step">
              <div className="step-number-glow">3</div>
              <h3>Delivery & Wear</h3>
              <p>Monitor status updates, complete payment, and receive clean clothes at your door.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Seasonal Offers & Customer Rewards Section */}
      <section className="landing-section offers-section main-content">
        <div className="section-header">
          <h2 className="section-title">Exclusive Offers & Customer Rewards</h2>
          <p className="section-subtitle">
            Get rewarded every time you choose clean. Save with our seasonal promos and unlock loyalty rewards.
          </p>
        </div>

        <div className="offers-container">
          {/* Active Promo Codes */}
          <div className="offers-column">
            <h3 className="column-title">🔥 Live Seasonal Offers</h3>
            <div className="offers-grid-vertical">
              
              {/* Offer 1 */}
              <div className="offer-promo-card glass-panel">
                <span className="discount-badge badge-monsoon">20% OFF</span>
                <div className="offer-details">
                  <h4>Monsoon Dry Cleaning Special</h4>
                  <p>Keep your heavy winter coats and delicate silks dry and clean during wet weeks.</p>
                  <div className="promo-code-box" onClick={() => handleCopyCode('MONSOON20')}>
                    <code>MONSOON20</code>
                    <button className="copy-btn">
                      {copiedCode === 'MONSOON20' ? 'Copied! ✅' : 'Copy Code 📋'}
                    </button>
                  </div>
                </div>
              </div>

              {/* Offer 2 */}
              <div className="offer-promo-card glass-panel">
                <span className="discount-badge badge-first">FLAT Rs 500 OFF</span>
                <div className="offer-details">
                  <h4>First Wash Welcome Deal</h4>
                  <p>New to WashEase? Enjoy a discount on your very first laundry pick-up service.</p>
                  <div className="promo-code-box" onClick={() => handleCopyCode('FIRSTWASH')}>
                    <code>FIRSTWASH</code>
                    <button className="copy-btn">
                      {copiedCode === 'FIRSTWASH' ? 'Copied! ✅' : 'Copy Code 📋'}
                    </button>
                  </div>
                </div>
              </div>

              {/* Offer 3 */}
              <div className="offer-promo-card glass-panel">
                <span className="discount-badge badge-weekend">10% OFF</span>
                <div className="offer-details">
                  <h4>Weekend SuperSaver Bonus</h4>
                  <p>Book bulk orders over 5kg on Saturday or Sunday to claim extra savings.</p>
                  <div className="promo-code-box" onClick={() => handleCopyCode('WEEKEND10')}>
                    <code>WEEKEND10</code>
                    <button className="copy-btn">
                      {copiedCode === 'WEEKEND10' ? 'Copied! ✅' : 'Copy Code 📋'}
                    </button>
                  </div>
                </div>
              </div>

            </div>
          </div>

          {/* Loyalty Program */}
          <div className="offers-column">
            <h3 className="column-title">⭐ WashEase Loyalty Program</h3>
            <div className="rewards-program-card glass-panel">
              <div className="rewards-intro">
                <h4>Earn Points, Redeem Free Washes!</h4>
                <p>Every rupee you spend on bookings translates to loyalty rewards to make wash day even sweeter.</p>
              </div>

              <div className="rewards-steps">
                <div className="reward-step-item">
                  <div className="reward-step-icon">🧼</div>
                  <div className="reward-step-text">
                    <h5>1. Spend & Earn</h5>
                    <p>Get 1 Loyalty Point for every Rs 100 spent on any wash, dry, or ironing service.</p>
                  </div>
                </div>

                <div className="reward-step-item">
                  <div className="reward-step-icon">📈</div>
                  <div className="reward-step-text">
                    <h5>2. Watch Balance Grow</h5>
                    <p>Track your verified loyalty status directly in your secure Customer Dashboard.</p>
                  </div>
                </div>

                <div className="reward-step-item">
                  <div className="reward-step-icon">🎁</div>
                  <div className="reward-step-text">
                    <h5>3. Claim Rewards</h5>
                    <p>Redeem your points for free pick-up deliveries, fabric softeners, or cash discounts!</p>
                  </div>
                </div>
              </div>

              <div className="rewards-tier-indicator">
                <div className="tier-pill tier-bronze">🥉 Bronze Tier</div>
                <div className="tier-pill tier-silver">🥈 Silver Tier (1.2x Pts)</div>
                <div className="tier-pill tier-gold">🥇 Gold Tier (1.5x Pts)</div>
              </div>

              <div className="rewards-action-cta">
                <button className="btn btn-primary" onClick={handleCustomerCTA}>
                  {user ? 'View My Loyalty Status' : 'Sign Up to Start Earning'}
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Vendor Partnership CTA */}
      {!user && (
        <section className="landing-section partner-section main-content">
          <div className="partner-card glass-panel">
            <div className="partner-content">
              <h2>Run a Laundry Business?</h2>
              <p>
                Grow your sales and reach new customers by joining WashEase as a laundry vendor. 
                Gain access to order management dashboards, client reports, automated invoicing, 
                and scale up through our exclusive Vendor Rewards Level Commission tiers.
              </p>
              <button className="btn btn-primary btn-lg" onClick={handleVendorCTA}>
                Register as Vendor Partner
              </button>
            </div>
            <div className="partner-visual">
              <div className="floating-bubble bubble-1">💼</div>
              <div className="floating-bubble bubble-2">📈</div>
              <div className="floating-bubble bubble-3">👚</div>
              <div className="washing-machine-glow">
                <div className="drum-rotate">
                  <div className="drum-water"></div>
                </div>
              </div>
            </div>
          </div>
        </section>
      )}

      {/* Footer */}
      <footer className="landing-footer">
        <div className="main-content footer-content">
          <div className="footer-brand">
            <h3>WashEase<span className="logo-dot"></span></h3>
            <p>Smart Laundry, Effortless Living.</p>
          </div>
          <div className="footer-links">
            <div className="footer-col">
              <h4>For Customers</h4>
              <span className="footer-link" onClick={() => handleCustomerCTA()}>Find Shops</span>
              <span className="footer-link" onClick={() => setCurrentTab('login')}>Sign In</span>
            </div>
            <div className="footer-col">
              <h4>For Vendors</h4>
              <span className="footer-link" onClick={() => handleVendorCTA()}>Become a Partner</span>
              <span className="footer-link" onClick={() => setCurrentTab('login')}>Vendor Login</span>
            </div>
            <div className="footer-col">
              <h4>Platform</h4>
              <span className="footer-link" onClick={() => setCurrentTab('login')}>Admin Dashboard</span>
              <span className="footer-link">Support</span>
            </div>
          </div>
        </div>
        <div className="footer-bottom">
          <p>&copy; {new Date().getFullYear()} WashEase. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
};
