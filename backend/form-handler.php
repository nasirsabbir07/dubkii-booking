<?php
add_action('wp_ajax_handle_booking_submission', 'handle_booking_submission');
add_action('wp_ajax_nopriv_handle_booking_submission', 'handle_booking_submission');
function handle_booking_submission() {
    require_once plugin_dir_path(__FILE__) . 'payment-intent.php';
    require_once plugin_dir_path(__FILE__) . 'booking-success-email.php';
    // require_once plugin_dir_path(__FILE__) . 'helpers.php';
    // Check nonce for security
    if ( ! isset($_POST['nonce']) || ! check_ajax_referer('custom_booking_form', 'nonce', false) ) {
        wp_send_json_error(['message' => 'Nonce verification failed.']);
    }
    error_log(print_r($_POST, true)); 
    global $wpdb;
    $personal_details = $wpdb->prefix . 'dubkii_personal_details';
    $fees_table =  $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    // Check if required fields are set
    if (!isset($_POST['name'], $_POST['email'], $_POST['contact_no'], $_POST['dob'], $_POST['address'], $_POST['city'], $_POST['post_code'], $_POST['nationality'], $_POST['country'], $_POST['course'], $_POST['start_date'], $_POST['duration'], $_POST['english_level'], $_POST['transport'])) {
        wp_send_json_error(array('message' => 'Missing required fields.'));
        return;
    }

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $contact_no = sanitize_text_field($_POST['contact_no']);
    $dob = sanitize_text_field($_POST['dob']); // Ensure date format is correct (YYYY-MM-DD)
    $address = sanitize_text_field($_POST['address']);
    $city = sanitize_text_field($_POST['city']);
    $post_code = sanitize_text_field($_POST['post_code']);
    $nationality = sanitize_text_field($_POST['nationality']);
    $country = sanitize_text_field($_POST['country']);
    $course_id = intval($_POST['course']);
    $start_date = sanitize_text_field($_POST['start_date']); // Ensure date format is correct (YYYY-MM-DD)
    $duration_id = intval($_POST['duration']);
    $english_level = sanitize_text_field($_POST['english_level']);
    $transport_option = sanitize_text_field($_POST['transport']);
    $accommodation_fee = isset($_POST['accommodationFee']) ? floatval($_POST['accommodationFee']) : 0.00;
    $frontend_total = isset($_POST['totalAmount']) ? floatval($_POST['totalAmount']) : 0;

    // Validate
    if ($transport_option !== 'yes' && $transport_option !== 'no') {
        wp_send_json_error(array('message' => 'Invalid transport option.'));
        return;
    }
    $has_transport = ($transport_option === 'yes') ? 1 : 0;
    // Retrieve dynamic fees
    $fees = $wpdb->get_row("SELECT * FROM $fees_table LIMIT 1");
    if (!$fees) {
        wp_send_json_error(['message' => 'Error retrieving fees data.']);
        return;
    }
    $transport_cost = $has_transport ? floatval($fees->transportation_cost) : 0;
    $registration_fee = floatval($fees->administration_fee);

    // Calculate total amount
    // Validate duration ID
    $duration_weeks = $wpdb->get_var($wpdb->prepare(
        "SELECT duration_weeks FROM {$wpdb->prefix}dubkii_course_durations WHERE id = %d",$duration_id
    ));

    if ($duration_weeks == null) {
        wp_send_json_error(['message' => 'Invalid duration selected.']);
        return;
    }
    $course_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}dubkii_courses_prices WHERE course_id = %d AND duration_id = %d", $course_id, $duration_id));

    if ($course_price === null) {
        wp_send_json_error(['message' => 'Error retrieving course price.']);
        return;
    }

    $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id));
    if($course_name === null){
        wp_send_json_error(array('message' => 'Invalid course selected.'));
        return;
    }
    
    
    // Recalculate registration fee
    $existing_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dubkii_personal_details WHERE email = %s", $email));
    $registration_fee = $existing_user ? 0.00 : $registration_fee; 


    $total_amount = ($course_price + $transport_cost + $registration_fee + $accommodation_fee) * 100; // Convert to cents

    // Compare frontend and backend totals
    if (abs($frontend_total - $total_amount ) > 0.01) { // Allow small float precision differences
        wp_send_json_error([
            'message' => 'Total amount mismatch. Please refresh the page and try again.',
            'details' => [
                'frontend_total' => $frontend_total,
                'backend_total' => $total_amount,
            ],
        ]);
        return;
    }

    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d', $dob) || !DateTime::createFromFormat('Y-m-d', $start_date)) {
        wp_send_json_error(array('message' => 'Invalid date format.'));
        return;
    }

    // Insert data into database
    $inserted = $wpdb->insert($personal_details, [
        'name' => $name,
        'email' => $email,
        'contact_no' => $contact_no,
        'dob' => $dob,
        'address' => $address,
        'city' => $city,
        'post_code'=> $post_code,
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

    

    // Check for errors during the insert operation
    if ($inserted === false) {
        error_log('Database Error: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Error saving booking details!']);
        exit;
    }


    $payment_intent = create_payment_intent($total_amount, $email);
    if (!$payment_intent) {
        wp_send_json_error(['message' => 'Failed to create payment intent.']);
    }

    

    wp_send_json_success([
        'message' => 'Booking submitted successfully!',
        'clientSecret' => $payment_intent->client_secret,
        "bookingDetails" => [
            "courseName" => $course_name,
            "registrationFee" => $registration_fee,
            "accommodationFee" => $accommodation_fee,
            "amount" => $total_amount / 100, // Convert cents to dollars
            "email" => $email,
            "bookingId" => $wpdb->insert_id,
        ],
    ]);
    // Send booking confirmation email
    $email_sent = send_booking_confirmation_email($email, $name, $course_name, $start_date, $duration, $has_transport, $total_amount);

    if (!$email_sent) {
        wp_send_json_error(['message' => 'Failed to send confirmation email, but booking was successful.']);
        return;
    }

    exit;
}

?>
