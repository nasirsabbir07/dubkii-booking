<?php
add_action('wp_ajax_handle_booking_submission', 'handle_booking_submission');
add_action('wp_ajax_nopriv_handle_booking_submission', 'handle_booking_submission');
function handle_booking_submission() {
    require_once plugin_dir_path(__FILE__) . 'payment-intent.php';
    require_once plugin_dir_path(__FILE__) . 'booking-success-email.php';
    // Check nonce for security
    if ( ! isset($_POST['nonce']) || ! check_ajax_referer('custom_booking_form', 'nonce', false) ) {
        wp_send_json_error(['message' => 'Nonce verification failed.']);
    }
    error_log(print_r($_POST, true)); 
    global $wpdb;
    $personal_details = $wpdb->prefix . 'dubkii_personal_details';

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
    $duration = sanitize_text_field($_POST['duration']);
    $english_level = sanitize_text_field($_POST['english_level']);
    // Transport data
    $transport_option = sanitize_text_field($_POST['transport']);
    if ($transport_option !== 'yes' && $transport_option !== 'no') {
        wp_send_json_error(array('message' => 'Invalid transport option.'));
        return;
    }
    $has_transport = ($transport_option === 'yes') ? 1 : 0;
    $transport_cost = $has_transport ? 50.00 : 0.00; 

    $course_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id));
    if ($course_exists == 0) {
        wp_send_json_error(array('message' => 'Invalid course selected.'));
        return;
    }

    // Calculate total amount
    $course_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id));

    $course_name = $wpdb->get_var($wpdb->prepare("SELECT course_name FROM {$wpdb->prefix}dubkii_courses WHERE id = %d", $course_id));

    if ($course_price === null) {
        wp_send_json_error(['message' => 'Error retrieving course price.']);
    }
    
    
    // Recalculate registration fee
    $existing_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dubkii_personal_details WHERE email = %s", $email));
    $registration_fee = $existing_user ? 0.00 : 50.00; // Fee is 0 for existing users, $50 for new users

    $accommodation_fee = isset($_POST['accommodationFee']) ? floatval($_POST['accommodationFee']) : 0;

    $total_amount = ($course_price + $transport_cost + $registration_fee + $accommodation_fee) * 100; // Convert to cents

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
        'duration' => $duration,
        'english_level' => $english_level,
        'has_transport' => $has_transport,
        'transport_cost' => $transport_cost,
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
    // Success response
    // wp_send_json_success(['message' => 'Booking submitted successfully!']);
    exit;
}

?>
