<?php
/*
Plugin Name: Dubkii Booking
Description: A plugin to manage course bookings.
Version: 1.0
Author: Barytech
*/
global $dubkii_booking_db_version;
$dubkii_booking_db_version = '1.0';

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'vendor/razorpay/razorpay/Razorpay.php';
// require_once plugin_dir_path(__FILE__) . 'class-alias.php';
include_once plugin_dir_path(__FILE__) . 'backend/db-setup.php';
// include_once plugin_dir_path(__FILE__) . 'backend/admin/admin-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'backend/form-handler.php';
require_once plugin_dir_path(__FILE__) . 'backend/api.php';



$admin_tabs_dir = plugin_dir_path(__FILE__) . 'backend/admin/admin-tabs/';

if (is_admin()) {
    include_once plugin_dir_path(__FILE__) . 'backend/admin/admin-dashboard.php';
    foreach (glob($admin_tabs_dir . '*.php') as $file) {
        require_once $file;
    }
}

// require_once plugin_dir_path(__FILE__) . 'backend/booking-success-email.php';


// Hook for plugin activation to set up database tables
function dubkii_booking_install()
{
    require_once plugin_dir_path(__FILE__) . 'backend/db-setup.php';
}
register_activation_hook(__FILE__, 'dubkii_booking_install');

// Add custom field to WooCommerce product edit page
function add_plugin_course_id_field()
{
    woocommerce_wp_text_input([
        'id' => 'plugin_course_id',
        'label' => __('Plugin Course ID', 'woocommerce'),
        'description' => __('Enter the course ID from the plugin database.', 'woocommerce'),
        'type' => 'number',
        'desc_tip' => true,
    ]);
}
add_action('woocommerce_product_options_general_product_data', 'add_plugin_course_id_field');

// Save custom field value
function save_plugin_course_id_field($post_id)
{
    $plugin_course_id = isset($_POST['plugin_course_id']) ? sanitize_text_field($_POST['plugin_course_id']) : '';
    if (!empty($plugin_course_id)) {
        update_post_meta($post_id, 'plugin_course_id', $plugin_course_id);
    }
}
add_action('woocommerce_process_product_meta', 'save_plugin_course_id_field');

// Enqueue frontend assets
function enqueue_booking_assets()
{
    // Enqueue Canvas Confetti Library
    wp_enqueue_script(
        'canvas-confetti',
        'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js',
        [],
        null,
        true // Load in the footer
    );

    wp_enqueue_script(
        'confetti-js',
        plugins_url('frontend/assets/js/confetti.js', __FILE__),
        ['canvas-confetti'],
        null,
        true
    );
    // Enqueue Razorpay Javascript SDK
    wp_enqueue_script(
        'razorpay-checkout',
        'https://checkout.razorpay.com/v1/checkout.js',
        [],
        null,
        true
    );
    // Enqueue the error helper script
    wp_enqueue_script(
        'error-helper',
        plugins_url('frontend/assets/js/error-helper.js', __FILE__),
        array(),
        null,
        true
    );
    // Register scripts and styles
    wp_register_script('countries-script', plugins_url('frontend/assets/js/countries.js', __FILE__), array(), null, true);
    wp_enqueue_script('countries-script'); // Make sure the countries script is enqueued

    wp_enqueue_style('booking-styles', plugins_url('frontend/assets/css/styles.css', __FILE__)); // Enqueue CSS

    wp_register_script('booking-js', plugins_url('frontend/assets/js/booking.js', __FILE__), array('jquery', 'razorpay-checkout', 'countries-script', 'canvas-confetti', 'confetti-js', 'error-helper'), null, true);
    wp_enqueue_script('booking-js'); // Enqueue booking.js script

    // Get the saved Razorpay API keys from your settings
    $razorpay_key_id = get_option('razorpay_key_id');
    $razorpay_key_secret = get_option('razorpay_key_secret');

    // Default localized data
    $localized_data = [
        'razorpayKey' => $razorpay_key_id,
        'razorpaySecret' => $razorpay_key_secret,
        'restApiUrl' => esc_url_raw(rest_url('dubkii/v1/')),
    ];

    // Inject `plugin_course_id` if on a WooCommerce product page
    if (is_product()) {
        global $post;
        $plugin_course_id = get_post_meta($post->ID, 'plugin_course_id', true);
        if ($plugin_course_id) {
            $localized_data['currentCourseId'] = $plugin_course_id; // Include plugin course ID
        }
    }
    // Localize the script
    wp_localize_script('booking-js', 'bookingData', $localized_data);
}
add_action('wp_enqueue_scripts', 'enqueue_booking_assets');

function enqueue_dubkii_admin_assets($hook)
{
    // Enqueue admin-specific styles
    if ($hook !== 'toplevel_page_dubkii-booking') {
        return; // Bail out if not on the desired page
    }
    wp_enqueue_style(
        'booking-admin-styles',
        plugins_url('frontend/assets/css/admin-styles.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'frontend/assets/css/admin-styles.css')
    );
}

add_action('admin_enqueue_scripts', 'enqueue_dubkii_admin_assets');


// Shortcode for booking form
function booking_form_shortcode()
{
    ob_start();
    include plugin_dir_path(__FILE__) . 'frontend/booking-form.php';
    return ob_get_clean();
}
add_shortcode('dubkii_booking_form', 'booking_form_shortcode');
