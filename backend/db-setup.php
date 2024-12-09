<?php
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

$table_name_courses = $wpdb->prefix . 'dubkii_courses';
// Drop the existing table if it exists

// 1. Create Courses Table
$sql_courses = "CREATE TABLE $table_name_courses (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    course_name varchar(100) NOT NULL,
    PRIMARY KEY (id)
) $charset_collate;";


// 2. Create Course Durations Table
$table_name_durations = $wpdb->prefix . 'dubkii_course_durations';
$sql_durations = "CREATE TABLE $table_name_durations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    course_id mediumint(9) NOT NULL,
    duration_weeks int NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (course_id) REFERENCES $table_name_courses(id) ON DELETE CASCADE
) $charset_collate;";

// 3. Create Course Start Dates Table
$table_name_start_dates = $wpdb->prefix . 'dubkii_course_start_dates';
$sql_start_dates = "CREATE TABLE $table_name_start_dates (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    course_id mediumint(9) NOT NULL,
    start_date date NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (course_id) REFERENCES $table_name_courses(id) ON DELETE CASCADE
) $charset_collate;";


// 4. Create Personal Details Table
$table_name = $wpdb->prefix . 'dubkii_personal_details';
$sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    contact_no varchar(15) NOT NULL,
    course_id mediumint(9) NOT NULL,
    dob date NOT NULL,
    address varchar(235) NOT NULL,
    city varchar(100) NOT NULL,
    post_code varchar(100) NOT NULL,
    nationality varchar(50) NOT NULL,
    country varchar(50) NOT NULL,
    start_date date NOT NULL,
    duration INT NOT NULL,
    english_level varchar(50) NOT NULL,
    has_transport tinyint(1) NOT NULL DEFAULT 0,
    transport_cost decimal(10, 2) DEFAULT 0.00,
    total_amount decimal(10, 2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    FOREIGN KEY (course_id) REFERENCES $table_name_courses(id) ON DELETE CASCADE
) $charset_collate;";

$table_name_prices = $wpdb->prefix . 'dubkii_courses_prices';

$sql_prices = "CREATE TABLE $table_name_prices (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    course_id mediumint(9) NOT NULL,
    duration_id mediumint(9) NOT NULL,
    price float NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY (course_id) REFERENCES $table_name_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (duration_id) REFERENCES $table_name_durations(id) ON DELETE CASCADE
) $charset_collate;";

$table_name_transportation_accommodation_fees = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';
$sql_transportation_accommodation_fees = "CREATE TABLE $table_name_transportation_accommodation_fees (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    administration_fee float NOT NULL,
    transportation_cost float NOT NULL,
    accommodation_cost float NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
) $charset_collate;";

$table_name_coupons = $wpdb->prefix . 'dubkii_coupons';
$sql_coupons = "CREATE TABLE $table_name_coupons(
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    code varchar(50) NOT NULL UNIQUE,
    discount_type enum('percentage', 'fixed') NOT NULL,
    discount_value decimal(10,2) DEFAULT NULL,
    max_price_range decimal(10,2) DEFAULT NULL,
    min_price_range decimal(10,2) DEFAULT NULL,
    max_discount_percentage decimal(5,2) DEFAULT NULL,
    min_discount_percentage decimal (5,2) DEFAULT NULL,
    max_redemptions int NOT NULL, 
    current_redemptions int DEFAULT 0,
    expiry_date DATETIME NOT NULL,
    is_active tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_active_non_expired (is_active, expiry_date)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
// Run the SQL queries to create the tables
dbDelta($sql_courses);
dbDelta($sql_durations);
dbDelta($sql_start_dates);
dbDelta($sql);
dbDelta($sql_prices);
$result = dbDelta($sql_transportation_accommodation_fees);
dbDelta($sql_coupons);
error_log(print_r($result, true));
