<?php
// washease-api/index.php

// Session settings for cross-origin credentials
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'True');
session_start();

// CORS Configuration
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Autoload/require controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CustomerController.php';
require_once __DIR__ . '/controllers/VendorController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/ComplaintController.php';

// Helper to get JSON input body
function getJsonInput() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// Helper to check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
        exit;
    }
}

// Helper to check user role
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Forbidden. Access denied."]);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$response = ["success" => false, "message" => "Endpoint not found."];
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    // === AUTH ROUTING ===
    case 'register':
        if ($method === 'POST') {
            $auth = new AuthController();
            $response = $auth->register(getJsonInput());
        }
        break;

    case 'login':
        if ($method === 'POST') {
            $auth = new AuthController();
            $response = $auth->login(getJsonInput());
        }
        break;

    case 'logout':
        $auth = new AuthController();
        $response = $auth->logout();
        break;

    case 'verify-otp':
        if ($method === 'POST') {
            $auth = new AuthController();
            $response = $auth->verifyOTP(getJsonInput());
        }
        break;

    case 'forgot-password':
        if ($method === 'POST') {
            $auth = new AuthController();
            $response = $auth->forgotPassword(getJsonInput());
        }
        break;

    case 'reset-password':
        if ($method === 'POST') {
            $auth = new AuthController();
            $response = $auth->resetPassword(getJsonInput());
        }
        break;

    case 'check-auth':
        if (isset($_SESSION['user_id'])) {
            // Re-fetch details
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username, email, role, status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && $user['status'] === 'active') {
                $details = [];
                if ($user['role'] === 'customer') {
                    $stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    $details = $stmt->fetch();
                } else if ($user['role'] === 'vendor') {
                    $stmt = $db->prepare("SELECT * FROM vendors WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    $details = $stmt->fetch();
                }

                $response = [
                    "success" => true,
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username'],
                        "email" => $user['email'],
                        "role" => $user['role'],
                        "details" => $details
                    ]
                ];
            } else {
                session_destroy();
                $response = ["success" => false, "message" => "Session invalid."];
            }
        } else {
            $response = ["success" => false, "message" => "Not logged in."];
        }
        break;

    // === CUSTOMER ROUTING ===
    case 'search-shops':
        requireRole(['customer']);
        $cust = new CustomerController();
        $response = $cust->searchVendors($_GET);
        break;

    case 'customer-profile':
        requireRole(['customer']);
        $cust = new CustomerController();
        if ($method === 'GET') {
            $response = $cust->getProfile($_SESSION['user_id']);
        } else if ($method === 'POST') {
            $response = $cust->updateProfile($_SESSION['user_id'], getJsonInput());
        }
        break;

    case 'book-pickup':
        requireRole(['customer']);
        if ($method === 'POST') {
            $order = new OrderController();
            $response = $order->createOrder($_SESSION['user_id'], getJsonInput());
        }
        break;

    // === ORDER ROUTING ===
    case 'list-orders':
        requireLogin();
        $order = new OrderController();
        $response = $order->listOrders($_SESSION['user_id'], $_SESSION['role'], $_GET);
        break;

    case 'order-details':
        requireLogin();
        $order = new OrderController();
        $orderId = intval($_GET['order_id'] ?? 0);
        $response = $order->getOrderDetails($orderId, $_SESSION['user_id'], $_SESSION['role']);
        break;

    case 'update-order-status':
        requireRole(['vendor']);
        if ($method === 'POST') {
            $order = new OrderController();
            $orderId = intval($_GET['order_id'] ?? 0);
            $response = $order->updateStatus($_SESSION['user_id'], $orderId, getJsonInput());
        }
        break;

    case 'create-bill':
        requireRole(['vendor']);
        if ($method === 'POST') {
            $order = new OrderController();
            $orderId = intval($_GET['order_id'] ?? 0);
            $response = $order->createBill($_SESSION['user_id'], $orderId, getJsonInput());
        }
        break;

    case 'confirm-payment':
        requireRole(['customer']);
        if ($method === 'POST') {
            $order = new OrderController();
            $orderId = intval($_GET['order_id'] ?? 0);
            $response = $order->confirmPayment($_SESSION['user_id'], $orderId, getJsonInput());
        }
        break;

    // === VENDOR ROUTING ===
    case 'vendor-profile':
        requireRole(['vendor']);
        $vendor = new VendorController();
        if ($method === 'GET') {
            $response = $vendor->getProfile($_SESSION['user_id']);
        } else if ($method === 'POST') {
            $response = $vendor->updateProfile($_SESSION['user_id'], getJsonInput());
        }
        break;

    case 'vendor-rewards':
        requireRole(['vendor']);
        $vendor = new VendorController();
        $response = $vendor->getRewardStats($_SESSION['user_id']);
        break;

    case 'vendor-reports':
        requireRole(['vendor']);
        $vendor = new VendorController();
        $response = $vendor->getReports($_SESSION['user_id'], $_GET);
        break;

    // === ADMIN ROUTING ===
    case 'admin-stats':
        requireRole(['admin']);
        $admin = new AdminController();
        $response = $admin->getStats();
        break;

    case 'admin-pending-users':
        requireRole(['admin']);
        $admin = new AdminController();
        $response = $admin->getPendingUsers();
        break;

    case 'admin-list-vendors':
        requireRole(['admin']);
        $admin = new AdminController();
        $response = $admin->getAllVendors();
        break;

    case 'admin-update-vendor-status':
        requireRole(['admin']);
        if ($method === 'POST') {
            $admin = new AdminController();
            $response = $admin->updateVendorStatus(getJsonInput());
        }
        break;

    case 'admin-approve-user':
        requireRole(['admin']);
        if ($method === 'POST') {
            $admin = new AdminController();
            $input = getJsonInput();
            $response = $admin->approveUser($input['user_id'] ?? 0);
        }
        break;

    case 'admin-reject-user':
        requireRole(['admin']);
        if ($method === 'POST') {
            $admin = new AdminController();
            $input = getJsonInput();
            $response = $admin->rejectUser($input['user_id'] ?? 0);
        }
        break;

    case 'admin-fraud-alerts':
        requireRole(['admin']);
        $admin = new AdminController();
        $response = $admin->getFraudAlerts();
        break;

    case 'admin-resolve-fraud':
        requireRole(['admin']);
        if ($method === 'POST') {
            $admin = new AdminController();
            $input = getJsonInput();
            $response = $admin->resolveFraudAlert($input['alert_id'] ?? 0);
        }
        break;

    case 'admin-commission-rules':
        requireRole(['admin']);
        $admin = new AdminController();
        if ($method === 'GET') {
            $response = $admin->getCommissionRules();
        } else if ($method === 'POST') {
            $response = $admin->updateCommissionRules(getJsonInput());
        }
        break;

    case 'admin-reports':
        requireRole(['admin']);
        $admin = new AdminController();
        $response = $admin->getAdminReports($_GET);
        break;

    // === COMPLAINT ROUTING ===
    case 'submit-complaint':
        requireRole(['customer']);
        if ($method === 'POST') {
            $comp = new ComplaintController();
            $response = $comp->submitComplaint($_SESSION['user_id'], getJsonInput());
        }
        break;

    case 'list-complaints':
        requireLogin();
        $comp = new ComplaintController();
        $response = $comp->listComplaints($_SESSION['user_id'], $_SESSION['role']);
        break;

    case 'assign-complaint':
        requireRole(['admin']);
        if ($method === 'POST') {
            $comp = new ComplaintController();
            $input = getJsonInput();
            $response = $comp->assignComplaint($input['complaint_id'] ?? 0);
        }
        break;

    case 'resolve-complaint':
        requireRole(['admin']);
        if ($method === 'POST') {
            $comp = new ComplaintController();
            $input = getJsonInput();
            $response = $comp->resolveComplaint($input['complaint_id'] ?? 0, $input);
        }
        break;

    case 'close-complaint':
        requireRole(['admin']);
        if ($method === 'POST') {
            $comp = new ComplaintController();
            $input = getJsonInput();
            $response = $comp->closeComplaint($input['complaint_id'] ?? 0);
        }
        break;

    // === NOTIFICATIONS ===
    case 'get-notifications':
        requireLogin();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$_SESSION['user_id']]);
        $response = ["success" => true, "data" => $stmt->fetchAll()];
        break;

    case 'mark-notifications-read':
        requireLogin();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $response = ["success" => true, "message" => "Notifications marked as read."];
        break;
}

echo json_encode($response);
