<?php
// ** api created using rest api **//
add_action('rest_api_init', function () {
    // Route: Fetch Courses
    register_rest_route('dubkii/v1', '/courses', [
        'methods' => 'GET',
        'callback' => 'rest_get_course_data',
        'args' => [
            'course_id' => [
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param); // Validate that course_id is numeric
                },
            ],
        ],
        'permission_callback' => '__return_true',
    ]);
    // Route: Fetch course details
    register_rest_route('dubkii/v1', '/course-details', [
        'methods' => 'GET',
        'callback' => 'rest_get_course_details',
        'permission_callback' => '__return_true',
    ]);

    // Route: Check email existence
    register_rest_route('dubkii/v1', '/check-email', [
        'methods' => 'POST',
        'callback' => 'rest_check_email_exists',
        'permission_callback' => '__return_true',
    ]);

    // Route: Get course price
    register_rest_route('dubkii/v1', '/get-course-price', [
        'methods' => 'GET',
        'callback' => 'rest_get_course_price',
        'permission_callback' => '__return_true',
    ]);

    // Route: Get fees
    register_rest_route('dubkii/v1', '/fees', [
        'methods' => 'GET',
        'callback' => 'rest_get_fees',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('dubkii/v1', '/create-order', [
        'methods' => 'POST',
        'callback' => 'handle_create_order',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('dubkii/v1', '/verify-payment', [
        'methods' => 'POST',
        'callback' => 'handle_payment_verification',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('dubkii/v1', '/active-coupons', [
        'methods' => 'GET', // Allow GET requests
        'callback' => 'fetch_active_coupons_rest',
        'permission_callback' => '__return_true', // Public access; adjust as needed
    ]);
});

function rest_get_course_data(WP_REST_Request $request)
{
    global $wpdb;
    $table_name_courses = $wpdb->prefix . 'dubkii_courses';

    // Get the course_id parameter from the API request
    $course_id = $request->get_param('course_id');

    // Fetch course data based on course_id
    if (!empty($course_id)) {
        $course = $wpdb->get_row(
            $wpdb->prepare("SELECT id, course_name FROM $table_name_courses WHERE id = %d", $course_id),
            ARRAY_A
        );

        if ($course) {
            return rest_ensure_response([
                'success' => true,
                'course' => ['id' => $course['id'], 'name' => $course['course_name']],
            ]);
        } else {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Course not found',
            ]);
        }
    }

    // Handle cases where course_id is not provided
    return rest_ensure_response([
        'success' => false,
        'message' => 'Course ID is required',
    ]);
}


function rest_get_course_details(WP_REST_Request $request)
{
    global $wpdb;
    $course_id = intval($request->get_param('course_id'));

    if (!$course_id) {
        return new WP_Error('invalid_course_id', 'Invalid course ID provided.', ['status' => 400]);
    }

    $table_name_start_dates = $wpdb->prefix . 'dubkii_course_start_dates';
    $table_name_durations = $wpdb->prefix . 'dubkii_course_durations';

    $start_dates = $wpdb->get_col($wpdb->prepare("SELECT start_date FROM $table_name_start_dates WHERE course_id = %d", $course_id));
    $durations_results = $wpdb->get_results($wpdb->prepare("SELECT id, duration_weeks FROM $table_name_durations WHERE course_id = %d", $course_id), ARRAY_A);
    $durations = array_map(fn($row) => ['id' => $row['id'], 'duration_weeks' => $row['duration_weeks']], $durations_results);

    return rest_ensure_response(['success' => true, 'start_dates' => $start_dates, 'durations' => $durations]);
}

function rest_check_email_exists(WP_REST_Request $request)
{
    global $wpdb;
    $email = sanitize_email($request->get_param('email'));

    if (empty($email)) {
        return new WP_Error('no_email', 'No email provided.', ['status' => 400]);
    }

    $personal_details_table = $wpdb->prefix . 'dubkii_personal_details';
    $transportation_accommodation_fees = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $personal_details_table WHERE email = %s", $email));

    if ($user) {
        $registration_fee = 0.00;
    } else {
        $result = $wpdb->get_row("SELECT administration_fee FROM $transportation_accommodation_fees ORDER BY id DESC LIMIT 1", ARRAY_A);
        $registration_fee = $result ? (float) $result['administration_fee'] : 0.00;
    }

    return rest_ensure_response(['success' => true, 'registrationFee' => number_format($registration_fee, 2)]);
}

function rest_get_course_price(WP_REST_Request $request)
{
    global $wpdb;
    $course_id = intval($request->get_param('course_id'));
    $duration_id = intval($request->get_param('duration_id'));

    if (!$course_id || !$duration_id) {
        return new WP_Error('invalid_data', 'Invalid course or duration ID.', ['status' => 400]);
    }

    $price = $wpdb->get_var($wpdb->prepare(
        "SELECT price FROM {$wpdb->prefix}dubkii_courses_prices WHERE course_id = %d AND duration_id = %d",
        $course_id,
        $duration_id
    ));

    if ($price === null) {
        return new WP_Error('price_not_found', 'Price not found for the selected course and duration.', ['status' => 404]);
    }

    return rest_ensure_response(['success' => true, 'price' => $price]);
}

function rest_get_fees()
{
    global $wpdb;
    $transportation_accommodation_fees = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    $fees = $wpdb->get_row("SELECT transportation_cost, accommodation_cost, administration_fee FROM $transportation_accommodation_fees ORDER BY id DESC LIMIT 1", ARRAY_A);

    if (!$fees) {
        return new WP_Error('fees_not_found', 'Failed to retrieve fees.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'fees' => $fees]);
}
function handle_create_order(WP_REST_Request $request)
{
    global $wpdb;

    $params = $request->get_json_params();
    $totalAmount = isset($params['totalAmount']) ? intval($params['totalAmount']) : 0;

    if ($totalAmount <= 0) {
        return new WP_REST_Response(['message' => 'Invalid amount.'], 400);
    }

    // Razorpay API credentials
    $key_id = get_option('razorpay_key_id');
    $key_secret = get_option('razorpay_key_secret');
    if (!$key_id || !$key_secret) {
        error_log("Razorpay API credentials missing.");
    }

    try {
        $api = new Razorpay\Api\Api($key_id, $key_secret);
        error_log("Razorpay API initialized successfully.");
    } catch (Exception $e) {
        error_log("Error initializing Razorpay API: " . $e->getMessage());
        return new WP_REST_Response(['message' => 'Failed to initialize Razorpay API.'], 500);
    }

    // Save form data temporarily
    $temp_table = $wpdb->prefix . 'dubkii_temp_bookings';

    if ($wpdb->get_var("SHOW TABLES LIKE '$temp_table'") != $temp_table) {
        error_log("Temporary table `$temp_table` does not exist.");
        return new WP_REST_Response(['message' => 'Temporary bookings table is missing.'], 500);
    }

    $form_data = json_encode($params);
    $result = $wpdb->insert($temp_table, [
        'form_data' => $form_data,
        'created_at' => current_time('mysql'),
    ]);

    if ($result === false) {
        error_log("Database Insert Error: " . $wpdb->last_error);
        return new WP_REST_Response(['message' => 'Failed to save temporary data.'], 500);
    }

    $temp_id = $wpdb->insert_id;
    error_log("Temporary data saved with ID: $temp_id.");

    try {
        // Create Razorpay order
        $order = $api->order->create([
            'amount' => $totalAmount, // Amount in paise
            'currency' => 'USD',
            'receipt' => 'rcpt_' . $temp_id,
        ]);
        error_log("Razorpay order created: " . json_encode($order));
        return new WP_REST_Response([
            'success' => true,
            'orderId' => $order['id'],
            'tempId' => $temp_id, // Link temporary data
        ], 200);
    } catch (Exception $e) {
        error_log("Unexpected Error: " . $e->getMessage());
        return new WP_REST_Response(['message' => $e->getMessage()], 500);
    }
}
function handle_payment_verification(WP_REST_Request $request)
{
    global $wpdb;

    $params = $request->get_json_params();
    $razorpayPaymentId = sanitize_text_field($params['razorpayPaymentId']);
    $razorpayOrderId = sanitize_text_field($params['razorpayOrderId']);
    $razorpaySignature = sanitize_text_field($params['razorpaySignature']);
    $tempId = intval($params['tempId']);

    // Razorpay API credentials
    $key_id = get_option('razorpay_key_id');
    $key_secret = get_option('razorpay_key_secret');

    // Verify Razorpay signature
    $generatedSignature = hash_hmac('sha256', $razorpayOrderId . "|" . $razorpayPaymentId, $key_secret);

    if ($generatedSignature !== $razorpaySignature) {
        return new WP_REST_Response(['message' => 'Payment verification failed.'], 400);
    }

    // Retrieve temporary data
    $temp_table = $wpdb->prefix . 'dubkii_temp_bookings';
    $temp_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $temp_table WHERE id = %d", $tempId));

    if (!$temp_data) {
        return new WP_REST_Response(['message' => 'Temporary booking data not found.'], 404);
    }

    // Decode form data
    $form_data = json_decode($temp_data->form_data, true);
    $coupon_code = isset($form_data['couponCode']) ? sanitize_text_field($form_data['couponCode']) : null;

    if ($coupon_code) {
        $coupons_table = $wpdb->prefix . 'dubkii_coupons';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $coupons_table 
                 SET current_redemptions = current_redemptions + 1, 
                     is_active = CASE WHEN current_redemptions + 1 >= max_redemptions THEN 0 ELSE is_active END 
                 WHERE code = %s AND is_active = 1",
                $coupon_code
            )
        );
        if ($wpdb->rows_affected === 0) {
            // Handle case where coupon is inactive or max redemptions reached
            error_log("Coupon code `$coupon_code` was not updated. It might already be inactive.");
        }
    }

    // Populate booking details for response
    $course_name =
        $wpdb->get_var($wpdb->prepare(
            "SELECT course_name FROM {$wpdb->prefix}dubkii_courses WHERE id = %d",
            $form_data['course']
        )); // Fetch actual course name from the database if needed
    $registration_fee = floatval($form_data['registrationFee'] ?? 0);
    $accommodation_fee = floatval($form_data['accommodationFee'] ?? 0);
    $total_amount = floatval($form_data['totalAmount'] ?? 0);
    $email = sanitize_email($form_data['email']);

    // Save data permanently
    $personal_details = $wpdb->prefix . 'dubkii_personal_details';
    $inserted = $wpdb->insert($personal_details, [
        'name' => sanitize_text_field($form_data['name']),
        'email' => sanitize_email($form_data['email']),
        'contact_no' => sanitize_text_field($form_data['contact_no']),
        'dob' => sanitize_text_field($form_data['dob']),
        'address' => sanitize_text_field($form_data['address']),
        'city' => sanitize_text_field($form_data['city']),
        'post_code' => sanitize_text_field($form_data['post_code']),
        'nationality' => sanitize_text_field($form_data['nationality']),
        'country' => sanitize_text_field($form_data['country']),
        'course_id' => intval($form_data['course']),
        'start_date' => sanitize_text_field($form_data['start_date']),
        'duration' => intval($form_data['duration']),
        'english_level' => sanitize_text_field($form_data['english_level']),
        'has_transport' => sanitize_text_field($form_data['transport']) === 'yes' ? 1 : 0,
        'transport_cost' => floatval($form_data['transportationFee']),
        'total_amount' => floatval($form_data['totalAmount']),
    ]);

    if (!$inserted) {
        return new WP_REST_Response(['message' => 'Failed to save booking.'], 500);
    }

    try {
        $api = new Razorpay\Api\Api($key_id, $key_secret);

        $invoice = $api->invoice->create([
            'type' => 'link',
            'description' => 'Course Booking Invoice',
            'customer' => [
                'name' => $form_data['name'],
                'email' => $form_data['email'],
                'contact' => $form_data['contact_no'],
            ],
            'line_items' => [
                [
                    'name' => $course_name,
                    'amount' => intval($total_amount * 100), // Amount in paise
                    'currency' => 'INR',
                    'quantity' => 1,
                ]
            ],
            'sms_notify' => 1,
            'email_notify' => 1,
            'receipt' => 'rcpt_' . $tempId,
        ]);

        error_log("Invoice created successfully: " . json_encode($invoice));
    } catch (Exception $e) {
        error_log("Failed to create Razorpay invoice: " . $e->getMessage());
    }

    // Delete temporary data
    $wpdb->delete($temp_table, ['id' => $tempId]);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Payment verified and booking saved successfully!',
        'bookingDetails' => [
            'courseName' => $course_name,
            'registrationFee' => $registration_fee,
            'accommodationFee' => $accommodation_fee,
            'amount' => $total_amount / 100, // Convert cents to dollars
            'email' => $email,
            'bookingId' => $wpdb->insert_id,
            'invoiceUrl' => isset($invoice['short_url']) ? $invoice['short_url'] : null
        ],
    ], 200);
}
function fetch_active_coupons_rest(WP_REST_Request $request)
{
    global $wpdb;
    $table_name_coupons = $wpdb->prefix . 'dubkii_coupons';

    $current_date = current_time('Y-m-d H:i:s');
    $coupons = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM $table_name_coupons 
             WHERE is_active = 1 AND expiry_date >= %s",
            $current_date
        ),
        ARRAY_A
    );

    if (!empty($coupons)) {
        return new WP_REST_Response([
            'success' => true,
            'data' => $coupons
        ]);
    } else {
        return new WP_REST_Response(['message' => 'No active coupons found.'], 404);
    }
}
