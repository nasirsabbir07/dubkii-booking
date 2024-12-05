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
?>
    <div class="wrap">
        <h1>Dubkii Booking Management</h1>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'courses'), admin_url('admin.php')); ?>"
                class="nav-tab <?php echo ($active_tab === 'courses') ? 'nav-tab-active' : ''; ?>">
                Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'booked-courses'), admin_url('admin.php')); ?>"
                class="nav-tab <?php echo ($active_tab === 'booked-courses') ? 'nav-tab-active' : ''; ?>">
                Booked Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'transport-accommodation'), admin_url('admin.php')); ?>"
                class="nav-tab <?php echo ($active_tab === 'transport-accommodation') ? 'nav-tab-active' : ''; ?>">
                Transport and Accommodation
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'coupons'), admin_url('admin.php')); ?>"
                class="nav-tab <?php echo ($active_tab === 'coupons') ? 'nav-tab-active' : ''; ?>">
                Coupons
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
                default:
                    include_once plugin_dir_path(__FILE__) . 'tabs/courses-tab.php';
                    break;
            }
            ?>
        </div>
    </div>
<?php
}
