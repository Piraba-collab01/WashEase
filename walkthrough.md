# Feature Walkthrough: Vendor Commission and Account Block Management

We have implemented a complete end-to-end system for tracking vendor commissions, blocking accounts when they reach the Rs 1000 limit, and allowing administrators to manage vendor statuses.

## Changes Made

### 1. Database Schema Updates
* Modified the `users` table's `status` column enum to support `'blocked'` and `'inactive'` values.
* Added `unpaid_commission` (DECIMAL) to the `vendors` table to keep track of the outstanding commission amount.

### 2. Backend API Logic
* **[OrderController.php](file:///c:/xampp/htdocs/washease/api/controllers/OrderController.php)**: 
  * Updated `confirmPayment` to calculate order commission based on the vendor's tier percentage.
  * Adds commission to the vendor's `unpaid_commission` in the database.
  * If `unpaid_commission >= 1000`, sets the vendor's user status to `'blocked'` and triggers a critical notification.
* **[VendorController.php](file:///c:/xampp/htdocs/washease/api/controllers/VendorController.php)**:
  * Modified `getProfile` and `getRewardStats` to retrieve and return `unpaid_commission`.
* **[AdminController.php](file:///c:/xampp/htdocs/washease/api/controllers/AdminController.php)**:
  * Added `getAllVendors` to retrieve all vendors, their levels, and unpaid commissions.
  * Added `updateVendorStatus` to allow the administrator to activate/deactivate vendors, or clear commission dues (resets commission to `0.00` and activates the account).
* **[AuthController.php](file:///c:/xampp/htdocs/washease/api/controllers/AuthController.php)**:
  * Block login requests for users with `'blocked'` or `'inactive'` statuses, returning descriptive errors.
* **[index.php](file:///c:/xampp/htdocs/washease/api/index.php)**:
  * Registered routes `admin-list-vendors` and `admin-update-vendor-status`.

### 3. Frontend User Interface
* **[VendorDashboard.jsx](file:///c:/xampp/htdocs/washease/frontend/src/pages/VendorDashboard.jsx)**:
  * Added a **Red Account Blocked Warning Banner** at the top of the dashboard when commission reaches Rs 1000.
  * Added an **Unpaid Commission Card** in the stat grid (turns red when limit is reached).
  * Disabled all order status dropdown selects and the "Generate Bill" buttons if the account is blocked.
* **[AdminDashboard.jsx](file:///c:/xampp/htdocs/washease/frontend/src/pages/AdminDashboard.jsx)**:
  * Added a **Registered Vendors Control Panel** under the `Users` tab showing all vendors, their details, tiers, and unpaid commissions.
  * Added action buttons: **Activate**, **Deactivate**, and **Clear Dues** (which clears unpaid commission and sets status to active).
