<?php
/**
 * line_notification_advanced.php
 * Advanced LINE Notification Sender with Comprehensive Error Handling
 */

// Disable error reporting for production (comment out in development)
// error_reporting(0);

// Configuration
$default_configurations = [
    'line_api_push_url' => 'https://api.line.me/v2/bot/message/push',
    'line_api_validate_url' => 'https://api.line.me/v2/bot/message/validate',
    'line_api_quota_url' => 'https://api.line.me/v2/bot/message/quota'
];

/**
 * Validate LINE Channel Access Token
 * 
 * @param string $access_token LINE Channel Access Token
 * @return array Validation result
 */
function validateLineAccessToken($access_token) {
    global $default_configurations;
    
    // Validate token format
    if (empty($access_token) || strlen($access_token) < 100) {
        return [
            'valid' => false,
            'error' => 'Invalid access token format'
        ];
    }
    
    // Check quota to validate token
    $ch = curl_init($default_configurations['line_api_quota_url']);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Check response
    if ($httpCode === 200) {
        $quota = json_decode($response, true);
        return [
            'valid' => true,
            'quota' => $quota
        ];
    }
    
    return [
        'valid' => false,
        'error' => $curlError ?: "HTTP Error: $httpCode",
        'response' => $response
    ];
}

/**
 * Send LINE notification with advanced error handling
 * 
 * @param string $line_id LINE User ID
 * @param array $messages Messages to send
 * @param string $access_token LINE Channel Access Token
 * @param string $api_url LINE API URL
 * @return array Sending result
 */
function sendLineNotification($line_id, $messages, $access_token, $api_url) {
    // Extensive input validation
    $validation_errors = [];
    
    if (empty($line_id)) {
        $validation_errors[] = 'LINE User ID is empty';
    }
    
    if (!preg_match('/^U[0-9a-f]{32}$/i', $line_id)) {
        $validation_errors[] = 'Invalid LINE User ID format';
    }
    
    if (empty($access_token)) {
        $validation_errors[] = 'LINE Channel Access Token is empty';
    }
    
    if (empty($messages)) {
        $validation_errors[] = 'No messages to send';
    }
    
    // If there are validation errors, return immediately
    if (!empty($validation_errors)) {
        return [
            'success' => false,
            'error_type' => 'validation',
            'error_messages' => $validation_errors
        ];
    }
    
    // Validate access token before sending
    $token_validation = validateLineAccessToken($access_token);
    if (!$token_validation['valid']) {
        return [
            'success' => false,
            'error_type' => 'token_validation',
            'error_messages' => [$token_validation['error']]
        ];
    }
    
    // Prepare data for sending
    $payload = [
        'to' => $line_id,
        'messages' => $messages
    ];
    
    // Initialize cURL
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    
    // Capture detailed error information
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL
    curl_close($ch);
    
    // Process response
    if ($curl_errno) {
        return [
            'success' => false,
            'error_type' => 'curl_error',
            'error_messages' => [$curl_error],
            'curl_errno' => $curl_errno
        ];
    }
    
    // Decode response
    $response_data = json_decode($response, true);
    
    // Determine success based on HTTP code and response
    $success = $http_code === 200;
    
    return [
        'success' => $success,
        'http_code' => $http_code,
        'response' => $response,
        'response_data' => $response_data,
        'error_type' => $success ? null : 'line_api_error',
        'error_messages' => $success ? [] : [$response_data['message'] ?? 'Unknown LINE API error']
    ];
}

// Handle form submission
$send_result = null;
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $line_id = trim($_POST['line_id'] ?? '');
    $access_token = trim($_POST['access_token'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $include_chart = isset($_POST['include_chart']);
    $include_link = isset($_POST['include_link']);
    
    // Prepare messages
    $messages = [
        [
            'type' => 'text',
            'text' => $message_text
        ]
    ];
    
    // Add chart if requested
    if ($include_chart) {
        $messages[] = [
            'type' => 'image',
            'originalContentUrl' => 'https://example.com/attendance-chart.png',
            'previewImageUrl' => 'https://example.com/attendance-chart-preview.png'
        ];
    }
    
    // Add detail link if requested
    if ($include_link) {
        $messages[] = [
            'type' => 'template',
            'altText' => 'ดูรายละเอียดเพิ่มเติม',
            'template' => [
                'type' => 'buttons',
                'text' => 'คลิกเพื่อดูรายละเอียดเพิ่มเติม',
                'actions' => [
                    [
                        'type' => 'uri',
                        'label' => 'ดูรายละเอียด',
                        'uri' => 'https://student-prasat.example.com/notification-details'
                    ]
                ]
            ]
        ];
    }
    
    // Send notification
    $send_result = sendLineNotification(
        $line_id, 
        $messages, 
        $access_token, 
        $default_configurations['line_api_push_url']
    );
    
    // Attach additional context
    $send_result['message_count'] = count($messages);
    $send_result['message_text'] = $message_text;
}
?>
<!-- Rest of the HTML remains the same as in the previous version -->