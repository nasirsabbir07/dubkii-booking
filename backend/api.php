<?php
// ** api created using rest api **//

add_action('rest_api_init', function(){
    // Route: Fetch Courses
    register_rest_route('dubkii/v1', '/courses',[
        'methods' => 'GET',
        'callback' => 'rest_get_course_data',
        'args' => [
            'course_id' => [
                'required' => true,
                'validate_callback' => function($param, $request, $key) {
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
});

function rest_get_course_data(WP_REST_Request $request) {
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


function rest_get_course_details(WP_REST_Request $request) {
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

function rest_check_email_exists(WP_REST_Request $request) {
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

function rest_get_course_price(WP_REST_Request $request) {
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

function rest_get_fees() {
    global $wpdb;
    $transportation_accommodation_fees = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    $fees = $wpdb->get_row("SELECT transportation_cost, accommodation_cost, administration_fee FROM $transportation_accommodation_fees ORDER BY id DESC LIMIT 1", ARRAY_A);

    if (!$fees) {
        return new WP_Error('fees_not_found', 'Failed to retrieve fees.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'fees' => $fees]);
}

?>
