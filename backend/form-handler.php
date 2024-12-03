<?php
// ** apis created using ajax.php **//
// add_action('wp_ajax_handle_booking_submission', 'handle_booking_submission');
// add_action('wp_ajax_nopriv_handle_booking_submission', 'handle_booking_submission');
// function handle_booking_submission() {
//     require_once plugin_dir_path(__FILE__) . 'payment-intent.php';
//     require_once plugin_dir_path(__FILE__) . 'booking-success-email.php';
//     // require_once plugin_dir_path(__FILE__) . 'helpers.php';
//     // Check nonce for security
//     if ( ! isset($_POST['nonce']) || ! check_ajax_referer('custom_booking_form', 'nonce', false) ) {
//         wp_send_json_error(['message' => 'Nonce verification failed.']);
//     }
//     error_log(print_r($_POST, true)); 
//     global $wpdb;
//     $personal_details = $wpdb->prefix . 'dubkii_personal_details';
//     $fees_table =  $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

//     // Check if required fields are set
//     if (!isset($_POST['name'], $_POST['email'], $_POST['contact_no'], $_POST['dob'], $_POST['address'], $_POST['city'], $_POST['post_code'], $_POST['nationality'], $_POST['country'], $_POST['course'], $_POST['start_date'], $_POST['duration'], $_POST['english_level'], $_POST['transport'])) {
//         wp_send_json_error(array('message' => 'Missing required fields.'));
//         return;
//     }

//     $name = sanitize_text_field($_POST['name']);
//     $email = sanitize_email($_POST['email']);
//     $contact_no = sanitize_text_field($_POST['contact_no']);
//     $dob = sanitize_text_field($_POST['dob']); // Ensure date format is correct (YYYY-MM-DD)
//     $address = sanitize_text_field($_POST['address']);
//     $city = sanitize_text_field($_POST['city']);
//     $post_code = sanitize_text_field($_POST['post_code']);
//     $nationality = sanitize_text_field($_POST['nationality']);
//     $country = sanitize_text_field($_POST['country']);
//     $course_id = intval($_POST['course']);
//     $start_date = sanitize_text_field($_POST['start_date']); // Ensure date format is correct (YYYY-MM-DD)
//     $duration_id = intval($_POST['duration']);
//     $english_level = sanitize_text_field($_POST['english_level']);
//     $transport_option = sanitize_text_field($_POST['transport']);
//     $accommodation_fee = isset($_POST['accommodationFee']) ? floatval($_POST['accommodationFee']) : 0.00;
//     $frontend_total = isset($_POST['totalAmount']) ? floatval($_POST['totalAmount']) : 0;

//     // Validate
//     if ($transport_option !== 'yes' && $transport_option !== 'no') {
//         wp_send_json_error(array('message' => 'Invalid transport option.'));
//         return;
//     }
//     $has_transport = ($transport_option === 'yes') ? 1 : 0;
//     // Retrieve dynamic fees
//     $fees = $wpdb->get_row("SELECT * FROM $fees_table LIMIT 1");
//     if (!$fees) {
//         wp_send_json_error(['message' => 'Error retrieving fees data.']);
//         return;
//     }
//     $transport_cost = $has_transport ? floatval($fees->transportation_cost) : 0;
//     $registration_fee = floatval($fees->administration_fee);

//     // Calculate total amount
//     // Validate duration ID
//     $duration_weeks = $wpdb->get_var($wpdb->prepare(
//         "SELECT duration_weeks FROM {$wpdb->prefix}dubkii_course_durations WHERE id = %d",$duration_id
//     ));

//     if ($duration_weeks == null) {
//         wp_send_json_error(['message' => 'Invalid duration selected.']);
//         return;
//     }
//     $course_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}dubkii_courses_prices WHERE course_id = %d AND duration_id = %d", $course_id, $duration_id));

//     if ($course_price === null) {
//         wp_send_json_error(['message' => 'Error retrieving course price.']);
//         return;
//     }

//     $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id));
//     if($course_name === null){
//         wp_send_json_error(array('message' => 'Invalid course selected.'));
//         return;
//     }
    
    
//     // Recalculate registration fee
//     $existing_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dubkii_personal_details WHERE email = %s", $email));
//     $registration_fee = $existing_user ? 0.00 : $registration_fee; 


//     $total_amount = ($course_price + $transport_cost + $registration_fee + $accommodation_fee) * 100; // Convert to cents

//     // Compare frontend and backend totals
//     if (abs($frontend_total - $total_amount ) > 0.01) { // Allow small float precision differences
//         wp_send_json_error([
//             'message' => 'Total amount mismatch. Please refresh the page and try again.',
//             'details' => [
//                 'frontend_total' => $frontend_total,
//                 'backend_total' => $total_amount,
//             ],
//         ]);
//         return;
//     }

//     // Validate dates
//     if (!DateTime::createFromFormat('Y-m-d', $dob) || !DateTime::createFromFormat('Y-m-d', $start_date)) {
//         wp_send_json_error(array('message' => 'Invalid date format.'));
//         return;
//     }

//     // Insert data into database
//     $inserted = $wpdb->insert($personal_details, [
//         'name' => $name,
//         'email' => $email,
//         'contact_no' => $contact_no,
//         'dob' => $dob,
//         'address' => $address,
//         'city' => $city,
//         'post_code'=> $post_code,
//         'nationality' => $nationality,
//         'country' => $country,
//         'course_id' => $course_id,
//         'start_date' => $start_date,
//         'duration' => $duration_weeks,
//         'english_level' => $english_level,
//         'has_transport' => $has_transport,
//         'transport_cost' => $transport_cost,
//         'total_amount' => $total_amount,
//     ]);

    

//     // Check for errors during the insert operation
//     if ($inserted === false) {
//         error_log('Database Error: ' . $wpdb->last_error);
//         wp_send_json_error(['message' => 'Error saving booking details!']);
//         exit;
//     }


//     $payment_intent = create_payment_intent($total_amount, $email);
//     if (!$payment_intent) {
//         wp_send_json_error(['message' => 'Failed to create payment intent.']);
//     }

    

//     wp_send_json_success([
//         'message' => 'Booking submitted successfully!',
//         'clientSecret' => $payment_intent->client_secret,
//         "bookingDetails" => [
//             "courseName" => $course_name,
//             "registrationFee" => $registration_fee,
//             "accommodationFee" => $accommodation_fee,
//             "amount" => $total_amount / 100, // Convert cents to dollars
//             "email" => $email,
//             "bookingId" => $wpdb->insert_id,
//         ],
//     ]);
//     // Send booking confirmation email
//     $email_sent = send_booking_confirmation_email($email, $name, $course_name, $start_date, $duration, $has_transport, $total_amount);

//     if (!$email_sent) {
//         wp_send_json_error(['message' => 'Failed to send confirmation email, but booking was successful.']);
//         return;
//     }

//     exit;
// }

// ** api created using rest api **//
add_action('rest_api_init', function () {
    register_rest_route('dubkii/v1', '/submit-booking', [
        'methods' => 'POST',
        'callback' => 'handle_booking_submission_rest',
        'permission_callback' => '__return_true', // Replace with authentication if needed
    ]);
});

function handle_booking_submission_rest(WP_REST_Request $request) {
    require_once plugin_dir_path(__FILE__) . 'payment-intent.php';
    require_once plugin_dir_path(__FILE__) . 'booking-success-email.php';

    // Extract data from the REST request
    $params = $request->get_json_params();
    // Get the nonce from the request
    // $nonce = $params['nonce'];
    // error_log('Nonce: ' . $nonce);
    // // Validate the nonce
    // if (!wp_verify_nonce($nonce, 'custom_booking_form')) {
    //     return new WP_REST_Response(['success' => false, 'message' => 'Invalid nonce'], 403);
    // }

    global $wpdb;

    $personal_details = $wpdb->prefix . 'dubkii_personal_details';
    $fees_table = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    // Check required fields
    $required_fields = [
        'name', 'email', 'contact_no', 'dob', 'address', 'city', 'post_code',
        'nationality', 'country', 'course', 'start_date', 'duration', 'english_level', 'transport'
    ];

    foreach ($required_fields as $field) {
        if (empty($params[$field])) {
            return new WP_REST_Response(['message' => "Missing required field: $field"], 400);
        }
    }

    // Sanitize inputs
    $name = sanitize_text_field($params['name']);
    $email = sanitize_email($params['email']);
    $contact_no = sanitize_text_field($params['contact_no']);
    $dob = sanitize_text_field($params['dob']); // Ensure date format is correct (YYYY-MM-DD)
    $address = sanitize_text_field($params['address']);
    $city = sanitize_text_field($params['city']);
    $post_code = sanitize_text_field($params['post_code']);
    $nationality = sanitize_text_field($params['nationality']);
    $country = sanitize_text_field($params['country']);
    $course_id = intval($params['course']);
    $start_date = sanitize_text_field($params['start_date']);
    $duration_id = intval($params['duration']);
    $english_level = sanitize_text_field($params['english_level']);
    $transport_option = sanitize_text_field($params['transport']);
    $accommodation_fee = isset($params['accommodationFee']) ? floatval($params['accommodationFee']) : 0.00;
    $frontend_total = isset($params['totalAmount']) ? floatval($params['totalAmount']) : 0;

    // Validate transport option
    if (!in_array($transport_option, ['yes', 'no'], true)) {
        return new WP_REST_Response(['message' => 'Invalid transport option.'], 400);
    }

    $has_transport = ($transport_option === 'yes') ? 1 : 0;

    // Retrieve dynamic fees
    $fees = $wpdb->get_row("SELECT * FROM $fees_table LIMIT 1");
    if (!$fees) {
        return new WP_REST_Response(['message' => 'Error retrieving fees data.'], 500);
    }

    $transport_cost = $has_transport ? floatval($fees->transportation_cost) : 0;
    $registration_fee = floatval($fees->administration_fee);

    // Validate duration ID
    $duration_weeks = $wpdb->get_var($wpdb->prepare(
        "SELECT duration_weeks FROM {$wpdb->prefix}dubkii_course_durations WHERE id = %d", $duration_id
    ));

    if ($duration_weeks === null) {
        return new WP_REST_Response(['message' => 'Invalid duration selected.'], 400);
    }

    // Validate course price
    $course_price = $wpdb->get_var($wpdb->prepare(
        "SELECT price FROM {$wpdb->prefix}dubkii_courses_prices WHERE course_id = %d AND duration_id = %d",
        $course_id,
        $duration_id
    ));

    if ($course_price === null) {
        return new WP_REST_Response(['message' => 'Error retrieving course price.'], 500);
    }

    // Validate course name
    $course_name = $wpdb->get_var($wpdb->prepare(
        "SELECT course_name FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id
    ));

    if ($course_name === null) {
        return new WP_REST_Response(['message' => 'Invalid course selected.'], 400);
    }

    // Adjust registration fee for existing users
    $existing_user = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}dubkii_personal_details WHERE email = %s", $email
    ));

    $registration_fee = $existing_user ? 0.00 : $registration_fee;

    // Calculate total amount
    $total_amount = ($course_price + $transport_cost + $registration_fee + $accommodation_fee) * 100; // Convert to cents

    // Compare frontend and backend totals
    if (abs($frontend_total - $total_amount) > 0.01) { // Allow small float precision differences
        return new WP_REST_Response([
            'message' => 'Total amount mismatch. Please refresh the page and try again.',
            'details' => [
                'frontend_total' => $frontend_total,
                'backend_total' => $total_amount,
            ],
        ], 400);
    }

    // Validate date formats
    if (!DateTime::createFromFormat('Y-m-d', $dob) || !DateTime::createFromFormat('Y-m-d', $start_date)) {
        return new WP_REST_Response(['message' => 'Invalid date format.'], 400);
    }

    // Insert data into database
    $inserted = $wpdb->insert($personal_details, [
        'name' => $name,
        'email' => $email,
        'contact_no' => $contact_no,
        'dob' => $dob,
        'address' => $address,
        'city' => $city,
        'post_code' => $post_code,
        'nationality' => $nationality,
        'country' => $country,
        'course_id' => $course_id,
        'start_date' => $start_date,
        'duration' => $duration_weeks,
        'english_level' => $english_level,
        'has_transport' => $has_transport,
        'transport_cost' => $transport_cost,
        'total_amount' => $total_amount,
    ]);

    if ($inserted === false) {
        error_log('Database Error: ' . $wpdb->last_error);
        return new WP_REST_Response(['message' => 'Error saving booking details.'], 500);
    }

    // Create payment intent
    $payment_intent = create_payment_intent($total_amount, $email);
    if (!$payment_intent) {
        return new WP_REST_Response(['message' => 'Failed to create payment intent.'], 500);
    }

    // Send confirmation email
    // $email_sent = send_booking_confirmation_email($email, $name, $course_name, $start_date, $duration_weeks, $has_transport, $total_amount);

    // if (!$email_sent) {
    //     return new WP_REST_Response(['message' => 'Failed to send confirmation email, but booking was successful.'], 500);
    // }

    return new WP_REST_Response([
        'success' => 'true',
        'message' => 'Booking submitted successfully!',
        'clientSecret' => $payment_intent->client_secret,
        'bookingDetails' => [
            'courseName' => $course_name,
            'registrationFee' => $registration_fee,
            'accommodationFee' => $accommodation_fee,
            'amount' => $total_amount / 100, // Convert cents to dollars
            'email' => $email,
            'bookingId' => $wpdb->insert_id,
        ],
    ], 200);
}

?>
