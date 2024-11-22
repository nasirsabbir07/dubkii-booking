<?php
// Register an admin menu for the plugin
function dubkii_booking_admin_menu() {
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
function dubkii_booking_admin_page(){
    global $wpdb;
    $active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'courses';

    $courses_table = $wpdb->prefix . "dubkii_courses";
    $durations_table = $wpdb->prefix . "dubkii_course_durations";
    $start_dates_table = $wpdb->prefix . "dubkii_course_start_dates";
    $personal_details_table = $wpdb->prefix . 'dubkii_personal_details';

    //Handle form submission for adding new courses
    if(isset($_POST['submit_course'])){
        $course_name = sanitize_text_field($_POST['course_name']);
        $price = floatval($_POST['price']);

        $wpdb->insert($courses_table, array(
            'course_name' => $course_name,
            'price' => $price
        ));
        $course_id = $wpdb->insert_id;

        // Insert multiple durations
        $durations = $_POST['durations'];
        foreach ($durations as $duration_weeks) {
            if (!empty($duration_weeks)) {
                $wpdb->insert($durations_table, array(
                    'course_id' => $course_id,
                    'duration_weeks' => intval($duration_weeks)
                ));
            }
        }

        // Insert multiple start dates
        $start_dates = $_POST['start_dates'];
        foreach ($start_dates as $start_date) {
            if (!empty($start_date)) {
                $wpdb->insert($start_dates_table, array(
                    'course_id' => $course_id,
                    'start_date' => sanitize_text_field($start_date)
                ));
            }
        }

    }


    // Pagination variables
    $courses_per_page = 10;  // Number of courses to show per page
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $courses_per_page;

    // Fetch all courses with durations and start dates
    $courses = $wpdb->get_results("
        SELECT c.id, c.course_name, c.price, 
        GROUP_CONCAT(DISTINCT d.duration_weeks ORDER BY d.duration_weeks) AS durations,
        GROUP_CONCAT(DISTINCT s.start_date ORDER BY s.start_date) AS start_dates
        FROM $courses_table c
        LEFT JOIN $durations_table d ON c.id = d.course_id
        LEFT JOIN $start_dates_table s ON c.id = s.course_id
        GROUP BY c.id
        LIMIT $courses_per_page OFFSET $offset
    ");

    // Get the total number of courses for pagination
    $total_courses = $wpdb->get_var("
        SELECT COUNT(DISTINCT c.id)
        FROM $courses_table c
        LEFT JOIN $durations_table d ON c.id = d.course_id
        LEFT JOIN $start_dates_table s ON c.id = s.course_id
    ");

    // Calculate total pages
    $total_pages = ceil($total_courses / $courses_per_page);

    // Pagination for booked courses
    $booked_courses_per_page = 10;  // Number of booked courses to show per page
    $booked_courses_paged = isset($_GET['booked_courses_paged']) ? max(1, intval($_GET['booked_courses_paged'])) : 1;
    $booked_courses_offset = ($booked_courses_paged - 1) * $booked_courses_per_page;

    // Fetch all booked courses from personal details table
    $booked_courses = $wpdb->get_results("
        SELECT pd.id, pd.name, pd.course_id, c.course_name, pd.start_date, pd.duration
        FROM $personal_details_table pd
        LEFT JOIN $courses_table c ON pd.course_id = c.id
        LIMIT $booked_courses_per_page OFFSET $booked_courses_offset
    ");


    // Get the total number of booked courses
    $total_booked_courses = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $personal_details_table pd
        LEFT JOIN $courses_table c ON pd.course_id = c.id
    ");

    // Calculate total pages for booked courses
    $total_booked_pages = ceil($total_booked_courses / $booked_courses_per_page);
    ?>
    <div class="wrap">
        <h1>Dubkii Booking - Manage Courses</h1>
        <!-- Tab Navigation -->
         <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=dubkii-booking&active_tab=courses'); ?>" 
           class="nav-tab <?php echo ($active_tab === 'courses') ? 'nav-tab-active' : ''; ?>">
           Courses
        </a>
        <a href="<?php echo admin_url('admin.php?page=dubkii-booking&active_tab=booked-courses'); ?>" 
           class="nav-tab <?php echo ($active_tab === 'booked-courses') ? 'nav-tab-active' : ''; ?>">
           Booked Courses
        </a>
         </h2>

        <div id="courses" class="tab-content" style="display:<?php echo ($active_tab === 'courses') ? 'block' : 'none'; ?>">
            <h2>Add New Course</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="course_name">Course Name</label></th>
                        <td><input type="text" name="course_name" id="course_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="price">Price</label></th>
                        <td><input type="number" step="0.01" name="price" id="price" required></td>
                    </tr>
                    <tr>
                        <th><label for="durations">Durations (weeks)</label></th>
                        <td>
                            <div id="duration-wrapper">
                                <input type="number" name="durations[]" placeholder="Duration in weeks">
                            </div>
                            <button type="button" onclick="addDurationField()">Add Another Duration</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_dates">Start Dates</label></th>
                        <td>
                            <div id="start-date-wrapper">
                                <input type="date" name="start_dates[]">
                            </div>
                            <button type="button" onclick="addStartDateField()">Add Another Start Date</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Add Course', 'primary', 'submit_course'); ?>
            </form>
            <h2>All Courses</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course Name</th>
                        <th>Start Date</th>
                        <th>Duration (weeks)</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo $course->id; ?></td>
                            <td><?php echo $course->course_name; ?></td>
                            <td><?php echo $course->start_dates; ?></td>
                            <td><?php echo $course->durations; ?></td>
                            <td><?php echo $course->price; ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=dubkii-booking&delete_course=' . $course->id . '&active_tab=courses'); ?>" class="button">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for added courses -->
            <div class="pagination">
                <?php
                echo paginate_links(array(
                    'total' => $total_pages,
                    'current' => $paged,
                    'format' => '?courses_paged=%#%',
                    'add_args' => array('active_tab' => 'courses'),
                    'show_all' => false,
                    'prev_next' => true,
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                ));
                ?>
            </div>
        </div>
        <div id="booked-courses" class="tab-content" style="display: <?php echo ($active_tab === 'booked-courses') ? 'block' : 'none'; ?>;">
            <h3>Booked Courses</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Name</th>
                        <th>Course Name</th>
                        <th>Start Date</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booked_courses as $booking): ?>
                        <tr>
                            <td><?php echo $booking->id; ?></td>
                            <td><?php echo $booking->name; ?></td>
                            <td><?php echo $booking->course_name; ?></td>
                            <td><?php echo $booking->start_date; ?></td>
                            <td><?php echo $booking->duration; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination for booked courses -->
            <div class="pagination">
                <?php
                echo paginate_links(array(
                    'total' => $total_booked_pages,
                    'current' => $booked_courses_paged,
                    'format' => '?booked_courses_paged=%#%', // Format to include only paged parameter
                    'add_args' => array('active_tab' => 'booked-courses'), // Append 'active_tab' param correctly
                    'show_all' => false,
                    'prev_next' => true,
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                ));
                ?>
            </div>
        </div>
    </div>
    <script>
        // Show Tab Content
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    window.location.href = url; // Redirect to the clicked tab
                });
            });
        });
        function addDurationField() {
            const wrapper = document.getElementById('duration-wrapper');
            const input = document.createElement('input');
            input.type = 'number';
            input.name = 'durations[]';
            input.placeholder = 'Duration in weeks';
            wrapper.appendChild(input);
        }
        function addStartDateField() {
            const wrapper = document.getElementById('start-date-wrapper');
            const input = document.createElement('input');
            input.type = 'date';
            input.name = 'start_dates[]';
            wrapper.appendChild(input);
        }

        
    </script>
    <?php
}

// Handle course deletion
function dubkii_handle_delete_course() {
    if (isset($_GET['delete_course'])) {
        global $wpdb;
        $course_id = intval($_GET['delete_course']);
        
        // Delete from all related tables
        $wpdb->delete($wpdb->prefix . 'dubkii_courses', array('id' => $course_id));
        $wpdb->delete($wpdb->prefix . 'dubkii_course_durations', array('course_id' => $course_id));
        $wpdb->delete($wpdb->prefix . 'dubkii_course_start_dates', array('course_id' => $course_id));

        wp_redirect(admin_url('admin.php?page=dubkii-booking'));
        exit;
    }
}
add_action('admin_init', 'dubkii_handle_delete_course');
?>