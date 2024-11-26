<?php
function get_course_data() {
    global $wpdb;

    // Table names for start dates and durations
	$table_name_courses = $wpdb->prefix . 'dubkii_courses';

	//Fetching all courses
	$courses_results = $wpdb->get_results("SELECT id, course_name FROM $table_name_courses", ARRAY_A);
    
    // Prepare arrays for dropdown options
	$courses = [];
    
	// Extracting course details
    foreach ($courses_results as $course) {
        $courses[] = [
            'id' => $course['id'],
            'name' => $course['course_name'],
        ];
    }

    // Send a JSON response back to the frontend
    wp_send_json_success(['courses' => $courses]);
}

add_action('wp_ajax_get_course_data', 'get_course_data');
add_action('wp_ajax_nopriv_get_course_data', 'get_course_data');

function get_course_details(){
	global $wpdb;
	$table_name_start_dates = $wpdb->prefix . 'dubkii_course_start_dates';
    $table_name_durations = $wpdb->prefix . 'dubkii_course_durations';
	//Get course ID from AJAX request
	$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    // Fetch start dates for the selected course
    $start_dates_results = $wpdb->get_results(
        $wpdb->prepare("SELECT start_date FROM $table_name_start_dates WHERE course_id = %d", $course_id),
        ARRAY_A
    );

    // Fetch durations for the selected course
    $durations_results = $wpdb->get_results(
        $wpdb->prepare("SELECT id, duration_weeks FROM $table_name_durations WHERE course_id = %d", $course_id),
        ARRAY_A
    );

	$start_dates = [];
    $durations = [];

	// Extracting start dates
    foreach ($start_dates_results as $row) {
        $start_dates[] = $row['start_date'];
    }

    // Extracting durations
    foreach ($durations_results as $row) {
        $durations[] = [
            'id' => $row['id'],
            'duration_weeks' => $row['duration_weeks'] 
        ];
    }

	// Send a JSON response back to the frontend
    wp_send_json_success(['start_dates' => $start_dates, 'durations' => $durations]);
}
add_action('wp_ajax_get_course_details', 'get_course_details');
add_action('wp_ajax_nopriv_get_course_details', 'get_course_details');

// Hook to handle the AJAX request for email check
add_action('wp_ajax_check_email_exists', 'check_email_exists');
add_action('wp_ajax_nopriv_check_email_exists', 'check_email_exists');

function check_email_exists() {

    global $wpdb;
    // Ensure the email parameter is passed
    if (empty($_POST['email'])) {
        wp_send_json_error(array('message' => 'No email provided.'));
        exit;
    }

    $email = sanitize_email($_POST['email']);
    $personal_details_table = $wpdb->prefix . 'dubkii_personal_details';
    // Query the database to check if the email exists
    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $personal_details_table WHERE email = %s", $email));

    if ($user) {
        $registration_fee = 0.00;
    } else {
        $registration_fee = 50.00;
       
    }

    wp_send_json_success(array(
        'registrationFee' => number_format($registration_fee, 2),
    ));
}

add_action('wp_ajax_get_course_price', 'get_course_price');
add_action('wp_ajax_nopriv_get_course_price', 'get_course_price');

function get_course_price() {
    // Check nonce for security
    // if (!isset($_POST['nonce']) || !check_ajax_referer('custom_booking_form', 'nonce', false)) {
    //     wp_send_json_error(['message' => 'Nonce verification failed.']);
    // }

    global $wpdb;

    // Validate inputs
    $course_id = intval($_POST['course_id']);
    $duration_id = intval($_POST['duration_id']);

    if (!$course_id || !$duration_id) {
        wp_send_json_error(['message' => 'Invalid course or duration ID.']);
    }

    // Fetch price from the database
    $price = $wpdb->get_var($wpdb->prepare(
        "SELECT price FROM {$wpdb->prefix}dubkii_courses_prices WHERE course_id = %d AND duration_id = %d",
        $course_id,
        $duration_id
    ));

    if ($price === null) {
        wp_send_json_error(['message' => 'Price not found for the selected course and duration.']);
    }

    wp_send_json_success(['price' => $price]);
}


?>
