<?php
global $wpdb;
$courses_table = $wpdb->prefix . "dubkii_courses";
$durations_table = $wpdb->prefix . "dubkii_course_durations";
$start_dates_table = $wpdb->prefix . "dubkii_course_start_dates";
$personal_details_table = $wpdb->prefix . 'dubkii_personal_details';
$course_prices_table = $wpdb->prefix . 'dubkii_courses_prices';
// Set the serach query
$search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

// Initialize filter variable
$filter_course = isset($_GET['filter_course']) ? intval($_GET['filter_course']) : '';
$filter_duration = isset($_GET['filter_duration']) ? intval($_GET['filter_duration']) : '';
$has_transport = isset($_GET['has_transport']) && $_GET['has_transport'] !== '' ? intval($_GET['has_transport']) : null;

// Build WHERE conditions dynamically
$where_conditions = [];
if (!empty($search_query)) {
    $search_query = urldecode($search_query);
    $where_conditions[] = $wpdb->prepare(
        "(pd.name LIKE %s OR pd.email LIKE %s)",
        '%' . $search_query . '%',
        '%' . $search_query . '%'
    );
}

if (!empty($filter_course)) {
    $where_conditions[] = $wpdb->prepare("pd.course_id = %d", $filter_course);
}
if (!empty($filter_duration)) {
    $where_conditions[] = $wpdb->prepare("pd.duration = %d", $filter_duration);
}
// Only add has_transport condition if explicitly set
if ($has_transport !== null) {
    $where_conditions[] = $wpdb->prepare("pd.has_transport = %d", $has_transport);
}

// Combine WHERE conditions into one string
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
} else {
    $where_clause = '';
}

// Pagination for booked courses
$booked_courses_per_page = 10;  // Number of booked courses to show per page
$booked_courses_paged = isset($_GET['booked_courses_paged']) ? max(1, intval($_GET['booked_courses_paged'])) : 1;
$booked_courses_offset = ($booked_courses_paged - 1) * $booked_courses_per_page;

// Fetch all booked courses from personal details table
$booked_courses = $wpdb->get_results("
        SELECT pd.id, pd.name, pd.email, pd.course_id, c.course_name, pd.start_date, pd.duration, pd.total_amount, pd.transport_cost
        FROM $personal_details_table pd
        LEFT JOIN $courses_table c ON pd.course_id = c.id
        LEFT JOIN $durations_table d ON pd.course_id = d.course_id AND pd.duration = d.duration_weeks
        LEFT JOIN $course_prices_table cp ON c.id = cp.course_id AND d.id = cp.duration_id
        $where_clause
        ORDER BY pd.id DESC
        LIMIT $booked_courses_per_page OFFSET $booked_courses_offset
    ");

error_log(print_r($wpdb->last_query, true));


// Get the total number of booked courses
$total_booked_courses = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $personal_details_table pd
        LEFT JOIN $courses_table c ON pd.course_id = c.id
        LEFT JOIN $durations_table d ON pd.course_id = d.course_id AND pd.duration = d.duration_weeks
        LEFT JOIN $course_prices_table cp ON c.id = cp.course_id AND d.id = cp.duration_id
        $where_clause
    ");

// Calculate total pages for booked courses
$total_booked_pages = ceil($total_booked_courses / $booked_courses_per_page);
?>
<div id="booked-courses" class="tab-content" style="display: <?php echo ($active_tab === 'booked-courses') ? 'block' : 'none'; ?>;">
    <h3>Booked Courses</h3>
    <div class="filter-section" style="display:flex; gap:20px">
        <!-- Sidebar filters -->
        <div class="sidebar-filters" style="width: 25%; border-right: 1px solid #ccc; padding: 10px;">
            <form method="GET" action="">
                <input type="hidden" name="page" value="dubkii-booking"> <!-- Keep the page parameter -->
                <input type="hidden" name="active_tab" value="<?php echo isset($_GET['active_tab']) ? esc_attr($_GET['active_tab']) : 'courses'; ?>"> <!-- Keep active_tab -->


                <input type="hidden" name="search_query" value="<?php echo isset($_GET['search_query']) ? esc_attr($_GET['search_query']) : ''; ?>">
                <input type="hidden" name="filter_course" value="<?php echo isset($_GET['filter_course']) ? esc_attr($_GET['filter_course']) : ''; ?>">
                <input type="hidden" name="filter_duration" value="<?php echo isset($_GET['filter_duration']) ? esc_attr($_GET['filter_duration']) : ''; ?>">
                <input type="hidden" name="has_transport" value="<?php echo isset($_GET['has_transport']) ? esc_attr($_GET['has_transport']) : ''; ?>">

                <!-- Search by name or email -->
                <div class="filter-item">
                    <input type="text" id="search_query" name="search_query" placeholder="Search by name or email" value="<?php echo isset($_GET['search_query']) ? esc_attr($_GET['search_query']) : ''; ?>">
                </div>

                <!-- Checkbox Filter for Transport -->
                <div class="filter-item">
                    <label>Has Transport:</label>
                    <label for="has_transport_yes">
                        <input type="radio" id="has_transport_yes" name="has_transport" value="1" <?php checked(isset($_GET['has_transport']) && $_GET['has_transport'] == 1); ?>>
                        Yes
                    </label>
                    <label for="has_transport_no">
                        <input type="radio" id="has_transport_no" name="has_transport" value="0" <?php checked(isset($_GET['has_transport']) && $_GET['has_transport'] == 0); ?>>
                        No
                    </label>
                </div>

                <!-- Dropdown Filter for Courses -->
                <div class="filter-item">
                    <label for="filter_course">Course:</label>
                    <select id="filter_course" name="filter_course">
                        <option value="">All Courses</option>
                        <?php
                        // Fetch all course names for the dropdown
                        $courses = $wpdb->get_results("SELECT id, course_name FROM $courses_table");
                        foreach ($courses as $course):
                        ?>
                            <option value="<?php echo $course->id; ?>" <?php selected(isset($_GET['filter_course']) && $_GET['filter_course'] == $course->id); ?>>
                                <?php echo $course->course_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dropdown Filter for Duration -->
                <div class="filter-item">
                    <label for="filter_duration">Duration (weeks):</label>
                    <select id="filter_duration" name="filter_duration">
                        <option value="">All Durations</option>
                        <?php
                        // Fetch unique durations for the dropdown
                        $durations = $wpdb->get_results("SELECT DISTINCT duration_weeks FROM $durations_table ORDER BY duration_weeks");
                        foreach ($durations as $duration):
                        ?>
                            <option value="<?php echo $duration->duration_weeks; ?>" <?php selected(isset($_GET['filter_duration']) && $_GET['filter_duration'] == $duration->duration_weeks); ?>>
                                <?php echo $duration->duration_weeks . ' weeks'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="button button-primary">Apply Filters</button>
                <!-- Reset Button -->
                <a href="<?php echo admin_url('admin.php?page=dubkii-booking&active_tab=' . esc_attr($_GET['active_tab'])); ?>" class="button button-secondary">Reset Filters</a>
            </form>
        </div>
        <div class="booked-courses-table" style="width: 75%; padding:0px 10px;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course Name</th>
                        <th>Start Date</th>
                        <th>Duration</th>
                        <th>Transport Cost</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($booked_courses)): ?>
                        <?php foreach ($booked_courses as $booking): ?>
                            <tr>
                                <td><?php echo $booking->id; ?></td>
                                <td><?php echo $booking->name; ?></td>
                                <td><?php echo $booking->email; ?></td>
                                <td><?php echo $booking->course_name; ?></td>
                                <td><?php echo date('d-m-y', strtotime($booking->start_date)); ?></td>
                                <td><?php echo $booking->duration . ' weeks'; ?></td>
                                <td>
                                    <?php
                                    $formatted_transport_cost = number_format($booking->transport_cost, 2);
                                    echo '$' . $formatted_transport_cost;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $formatted_total_amount = number_format($booking->total_amount / 100, 2);
                                    echo '$' . $formatted_total_amount; // Display in currency format
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No results found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Pagination for booked courses -->
    <div class="pagination">
        <?php
        // Prepare filter parameters to include in the pagination links
        $filter_params = array();

        if (!empty($filter_course)) {
            $filter_params['filter_course'] = $filter_course;
        }
        if (!empty($filter_duration)) {
            $filter_params['filter_duration'] = $filter_duration;
        }
        if ($has_transport) {
            $filter_params['has_transport'] = $has_transport;
        }
        if (!empty($search_query)) {
            $filter_params['search_query'] = $search_query;
        }

        echo paginate_links(array(
            'total' => $total_booked_pages,
            'current' => $booked_courses_paged,
            'format' => '?booked_courses_paged=%#%', // Format to include only paged parameter
            'add_args' => array_merge(
                $_GET, // Retain any other query params (e.g., page)
                ['page' => 'dubkii-booking', 'active_tab' => 'booked-courses'], // Ensure page and active_tab are included
                $filter_params // Merge the filter parameters
            ), // Append all filter parameters and active_tab
            'show_all' => false,
            'prev_next' => true,
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
        ));
        ?>
    </div>
</div>