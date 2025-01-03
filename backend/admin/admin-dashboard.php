<?php
// Register an admin menu for the plugin
function dubkii_booking_admin_menu()
{
    add_menu_page(
        'Dubkii Booking',           // Page title
        'Dubkii Booking',           // Menu title
        'manage_options',           // Capability
        'dubkii-booking',           // Menu slug
        'dubkii_booking_admin_page', // Callback function
        'dashicons-welcome-learn-more', // Icon
        6                           // Position
    );
}
add_action('admin_menu', 'dubkii_booking_admin_menu');


//Admin page content
function dubkii_booking_admin_page()
{
    $active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'courses';
    // Generate the base URL with the default active_tab if missing
    $base_url = admin_url('admin.php?page=dubkii-booking');

    // Ensure active_tab is included in the base URL
    if (!isset($_GET['active_tab'])) {
        $base_url = add_query_arg('active_tab', 'courses', $base_url);
    }
?>
    <div class="wrap">
        <h1>Dubkii Booking Management</h1>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'courses'), $base_url); ?>"
                class="nav-tab <?php echo ($active_tab === 'courses') ? 'nav-tab-active' : ''; ?>">
                Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'booked-courses'), $base_url); ?>"
                class="nav-tab <?php echo ($active_tab === 'booked-courses') ? 'nav-tab-active' : ''; ?>">
                Booked Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'transport-accommodation'), $base_url); ?>"
                class="nav-tab <?php echo ($active_tab === 'transport-accommodation') ? 'nav-tab-active' : ''; ?>">
                Transport and Accommodation
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'coupons'), $base_url); ?>"
                class="nav-tab <?php echo ($active_tab === 'coupons') ? 'nav-tab-active' : ''; ?>">
                Coupons
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'razorpay-settings'), $base_url); ?>"
                class="nav-tab <?php echo ($active_tab === 'razorpay-settings') ? 'nav-tab-active' : ''; ?>">
                Razorpay Settings
            </a>
        </h2>

        <!-- Tabs Content -->
        <div class="tab-content">
            <?php
            // Include content based on the active tab
            switch ($active_tab) {
                case 'courses':
                    include_once plugin_dir_path(__FILE__) . 'tabs/courses-tab.php';
                    break;
                case 'booked-courses':
                    include_once plugin_dir_path(__FILE__) . 'tabs/booked-courses-tab.php';
                    break;
                case 'transport-accommodation':
                    include_once plugin_dir_path(__FILE__) . 'tabs/transport-accommodation-tab.php';
                    break;
                case 'coupons':
                    include_once plugin_dir_path(__FILE__) . 'tabs/coupons-tab.php';
                    break;
                case 'razorpay-settings':
                    include_once plugin_dir_path(__FILE__) . 'tabs/razorpay-settings-tab.php';
                    break;
                default:
                    include_once plugin_dir_path(__FILE__) . 'tabs/courses-tab.php';
                    break;
            }
            ?>
        </div>
    </div>
<?php
}
