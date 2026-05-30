<?php
/**
 * Shop Backend Handler
 * Handles order processing, payment verification, and email notifications
 */

header('Content-Type: application/json');
date_default_timezone_set('Africa/Lagos');

// Your Configuration
$PAYSTACK_SECRET_KEY = 'sk_live_YOUR_SECRET_KEY_HERE'; // Replace with your Paystack Secret Key
$SITE_EMAIL = 'mbayomitpointglobal@gmail.com';
$ADMIN_EMAIL = 'mbayomitpointglobal@gmail.com';

// Database Configuration (Optional - if you want to store orders)
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'shop_db';

// Get the request type
$request_type = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($request_type) {
    case 'verify_payment':
        verifyPayment();
        break;
    case 'save_order':
        saveOrder();
        break;
    case 'send_confirmation':
        sendConfirmationEmail();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

/**
 * Verify payment with Paystack
 */
function verifyPayment() {
    global $PAYSTACK_SECRET_KEY;
    
    $reference = isset($_GET['reference']) ? sanitizeInput($_GET['reference']) : '';
    
    if (!$reference) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No reference provided']);
        return;
    }

    // Call Paystack API to verify
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$reference}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer {$PAYSTACK_SECRET_KEY}",
            "Cache-Control: no-cache",
        ),
    ));

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Verification error: ' . $err]);
        return;
    }

    $result = json_decode($response, true);
    
    if ($result['status'] && $result['data']['status'] == 'success') {
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified',
            'data' => $result['data']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
    }
}

/**
 * Save order to database
 */
function saveOrder() {
    $customer_name = sanitizeInput($_POST['customer_name'] ?? '');
    $customer_email = sanitizeInput($_POST['customer_email'] ?? '');
    $customer_phone = sanitizeInput($_POST['customer_phone'] ?? '');
    $customer_address = sanitizeInput($_POST['customer_address'] ?? '');
    $reference = sanitizeInput($_POST['reference'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    $total_amount = floatval($_POST['total_amount'] ?? 0);

    // Validate input
    if (!$customer_name || !$customer_email || !$customer_phone || !$customer_address || !$reference) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    // Here you would save to database
    // For now, we'll just create a log file as example
    
    $order_data = [
        'order_id' => 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999),
        'customer' => [
            'name' => $customer_name,
            'email' => $customer_email,
            'phone' => $customer_phone,
            'address' => $customer_address
        ],
        'reference' => $reference,
        'items' => $items,
        'total_amount' => $total_amount,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'completed'
    ];

    // Log the order
    $log_file = __DIR__ . '/../orders_' . date('Ym') . '.log';
    $log_content = json_encode($order_data, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n";
    file_put_contents($log_file, $log_content, FILE_APPEND);

    // Send confirmation email
    sendOrderConfirmation($order_data);

    echo json_encode([
        'success' => true,
        'message' => 'Order saved successfully',
        'order_id' => $order_data['order_id']
    ]);
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmation($order_data) {
    global $SITE_EMAIL, $ADMIN_EMAIL;

    $customer_name = $order_data['customer']['name'];
    $customer_email = $order_data['customer']['email'];
    $order_id = $order_data['order_id'];
    $reference = $order_data['reference'];
    $total_amount = $order_data['total_amount'];
    $items = $order_data['items'];

    // Build email content
    $items_html = '<table style="width:100%; border-collapse: collapse; margin: 20px 0;">';
    $items_html .= '<tr style="background-color: #f8f9fa; border-bottom: 2px solid #ddd;">';
    $items_html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Product</th>';
    $items_html .= '<th style="padding: 10px; text-align: center; border: 1px solid #ddd;">Quantity</th>';
    $items_html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Price</th>';
    $items_html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Total</th>';
    $items_html .= '</tr>';

    foreach ($items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $items_html .= '<tr style="border-bottom: 1px solid #ddd;">';
        $items_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($item['name']) . '</td>';
        $items_html .= '<td style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . $item['quantity'] . '</td>';
        $items_html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">₦' . number_format($item['price']) . '</td>';
        $items_html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">₦' . number_format($item_total) . '</td>';
        $items_html .= '</tr>';
    }
    $items_html .= '</table>';

    // Customer email
    $customer_subject = "Order Confirmation - Order ID: {$order_id}";
    $customer_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #0d6efd; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .footer { background-color: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
            .content { padding: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Thank You for Your Order!</h2>
            </div>
            <div class='content'>
                <p>Hi <strong>{$customer_name}</strong>,</p>
                <p>Thank you for shopping with Mbayom IT-Point. Your order has been received and confirmed.</p>
                
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> {$order_id}</p>
                <p><strong>Payment Reference:</strong> {$reference}</p>
                <p><strong>Order Date:</strong> " . date('F d, Y H:i:s') . "</p>
                
                <h3>Items Ordered</h3>
                {$items_html}
                
                <p style='margin-top: 20px;'><strong>Total Amount:</strong> ₦" . number_format($total_amount, 2) . "</p>
                
                <h3>Delivery Address</h3>
                <p>" . nl2br(htmlspecialchars($order_data['customer']['address'])) . "</p>
                
                <p style='margin-top: 20px; color: #666;'>
                    We will process your order and contact you at <strong>" . htmlspecialchars($order_data['customer']['phone']) . "</strong> 
                    with delivery details within 24 hours.
                </p>
                
                <p>If you have any questions, please contact us at:</p>
                <p>
                    Email: {$SITE_EMAIL}<br>
                    Phone: +234 815 811 5339
                </p>
            </div>
            <div class='footer'>
                <p style='text-align: center; color: #666; margin: 0;'>© 2026 Mbayom IT-Point. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Admin notification email
    $admin_subject = "New Order Received - Order ID: {$order_id}";
    $admin_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #198754; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .footer { background-color: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
            .content { padding: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Order Alert!</h2>
            </div>
            <div class='content'>
                <h3>Order Details</h3>
                <p><strong>Order ID:</strong> {$order_id}</p>
                <p><strong>Payment Reference:</strong> {$reference}</p>
                <p><strong>Order Amount:</strong> ₦" . number_format($total_amount, 2) . "</p>
                
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> {$customer_name}</p>
                <p><strong>Email:</strong> {$customer_email}</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($order_data['customer']['phone']) . "</p>
                <p><strong>Address:</strong> " . nl2br(htmlspecialchars($order_data['customer']['address'])) . "</p>
                
                <h3>Items</h3>
                {$items_html}
                
                <p style='margin-top: 20px; color: #666;'>
                    Please process this order and arrange delivery.
                </p>
            </div>
            <div class='footer'>
                <p style='text-align: center; color: #666; margin: 0;'>Mbayom IT-Point Admin</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Send emails
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";

    // Send to customer
    mail($customer_email, $customer_subject, $customer_message, $headers);

    // Send to admin
    mail($ADMIN_EMAIL, $admin_subject, $admin_message, $headers);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(stripslashes(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Send confirmation email (direct call)
 */
function sendConfirmationEmail() {
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? 'Order Confirmation');
    $message = sanitizeInput($_POST['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email']);
        return;
    }

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";

    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
}

?>
