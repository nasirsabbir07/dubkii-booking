<?php
function send_booking_confirmation_email($email, $name, $course_name, $start_date, $duration, $has_transport, $total_amount){
    // Send booking confirmation email
    $subject = "Booking Confirmation for Your Course";
    $message = "Hello $name,\n\nThank you for booking your course with us. Here are your booking details:\n\n" .
               "Course: $course_name\n" .
               "Start Date: $start_date\n" .
               "Duration: $duration weeks\n" .
               "Transport: " . ($has_transport ? "Yes" : "No") . "\n" .
               "Total Amount: $" . ($total_amount / 100) . "\n\n" .
               "If you have any questions, feel free to contact us.\n\nBest regards,\nThe Team";

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if (!wp_mail($email, $subject, $message, $headers)) {
        error_log("Failed to send booking confirmation email to $email");
        wp_send_json_error(['message' => 'Failed to send confirmation email, but booking was successful.']);
        return false;
    }
    return true;
}
?>