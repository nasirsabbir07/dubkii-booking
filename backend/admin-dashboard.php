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
    $course_prices_table = $wpdb->prefix . 'dubkii_courses_prices';
    $transportation_accomodation_fees_table = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

    //Handle form submission for adding new courses
    if(isset($_POST['submit_course'])){
        $course_name = sanitize_text_field($_POST['course_name']);
        $durations = $_POST['durations'];
        $start_dates = $_POST['start_dates'];
        $prices = array_map('floatval',($_POST['prices']));

        try{  
            // start transaction
            $wpdb->query('START TRANSACTION');
            
            // Insrt into courses table
            $wpdb->insert($courses_table, array(
            'course_name' => $course_name,
            ));
            $course_id = $wpdb->insert_id;

            if (!$course_id) {
                throw new Exception('Failed to insert into courses table.');
            }

            // Insert multiple durations and their prices
            foreach ($durations as $index=>$duration_weeks) {
                if (!empty($duration_weeks)) {
                    $wpdb->insert($durations_table, array(
                        'course_id' => $course_id,
                        'duration_weeks' => intval($duration_weeks)
                    ));
                    $duration_id = $wpdb->insert_id;

                    if(!$duration_id){
                        throw new Exception('Failed to insert into durations table.');
                    }
                    error_log("Inserted duration with ID: $duration_id");
                    // Insert into prices table for each duration
                    if(isset($prices[$index]) && !empty($prices[$index])){
                        $wpdb->insert($course_prices_table, array(
                            'course_id' => $course_id,
                            'duration_id' => $duration_id,
                            'price' => floatval($prices[$index])
                        ));
                        $price_id = $wpdb->insert_id;
                        if(!$price_id){
                            throw new Exception('Failed to insert into prices table.');
                        }
                        error_log("Inserted price: {$prices[$index]} for duration ID: $duration_id");
                    }else {
                        error_log("Price not set or empty for duration index $index");
                    }
                }
            }

            // Insert multiple start dates
            
            foreach ($start_dates as $start_date) {
                if (!empty($start_date)) {
                    $wpdb->insert($start_dates_table, array(
                        'course_id' => $course_id,
                        'start_date' => sanitize_text_field($start_date)
                    ));

                    if(!$wpdb->insert_id){
                        throw new Exception('Failed to insert into start dates table.');
                    }
                }
            }
            // Commit the transaction
            $wpdb->query('COMMIT');
            error_log("Transaction committed successfully.");
            add_settings_error('dubkii_booking', 'course_add_success', 'Course added successfully!', 'updated');
            // wp_redirect(add_query_arg('active_tab', 'courses', $_SERVER['REQUEST_URI']));
            // exit;
        }catch (Exception $e) {
            // Rollback transaction in case of any error
            $wpdb->query('ROLLBACK');
            add_settings_error('dubkii_booking', 'course_add_error', 'An error occurred: ' . $e->getMessage(), 'error');
        }
            
    }

    // Display error or success messages
    settings_errors('dubkii_booking');


    // Pagination variables
    $courses_per_page = 10;  // Number of courses to show per page
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $courses_per_page;

    // Fetch all courses with durations and start dates
    $courses = $wpdb->get_results("
        SELECT c.id, c.course_name, 
        GROUP_CONCAT(DISTINCT d.duration_weeks ORDER BY d.duration_weeks) AS durations,
        GROUP_CONCAT(DISTINCT cp.price ORDER BY d.duration_weeks) AS prices,
        GROUP_CONCAT(DISTINCT s.start_date ORDER BY s.start_date) AS start_dates
        FROM $courses_table c
        LEFT JOIN $durations_table d ON c.id = d.course_id
        LEFT JOIN $course_prices_table cp ON c.id = cp.course_id AND d.id = cp.duration_id
        LEFT JOIN $start_dates_table s ON c.id = s.course_id
        GROUP BY c.id
        LIMIT $courses_per_page OFFSET $offset
    ");

    // Get the total number of courses for pagination
    $total_courses = $wpdb->get_var("
        SELECT COUNT(DISTINCT c.id)
        FROM $courses_table c
        LEFT JOIN $durations_table d ON c.id = d.course_id
        LEFT JOIN $course_prices_table cp ON c.id = cp.course_id AND d.id = cp.duration_id
        LEFT JOIN $start_dates_table s ON c.id = s.course_id
    ");

    // Calculate total pages
    $total_pages = ceil($total_courses / $courses_per_page);

    // Set the serach query
    $search_query = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';

    // Initialize filter variable
    $filter_course = isset($_GET['filter_course']) ? intval($_GET['filter_course']) : '';
    $filter_duration = isset($_GET['filter_duration']) ? intval($_GET['filter_duration']) : '';
    $has_transport = isset($_GET['has_transport']) && $_GET['has_transport'] !== '' ? intval($_GET['has_transport']) : null;

    // Build WHERE conditions dynamically
    $where_conditions = [];
    if(!empty($search_query)){
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

    // Transportaion and Accommodation
    if(isset($_POST['submit_costs'])){
        $administration_fee = isset($_POST['administration']) ? floatval($_POST['administration']) : 0;
        $transportation_cost = isset($_POST['transportation']) ? floatval($_POST['transportation']) : 0;
        $accommodation_cost = isset($_POST['accommodation']) ? floatval($_POST['accommodation']) : 0;

        error_log(print_r($_POST, true)); // Log the form data

        $wpdb->insert(
            $transportation_accomodation_fees_table,
            [   
                'administration_fee' => $administration_fee,
                'transportation_cost' => $transportation_cost,
                'accommodation_cost' => $accommodation_cost,
            ],
            ['%f', '%f', '%f']
        );
        $ta_id = $wpdb->insert_id;
        if(!$ta_id){
            echo '<div class="notice notice-error"><p>Costs could not be added</p></div>';
        }else{echo '<div class="notice notice-success"><p>Costs added successfully</p></div>';}
    }

    // Handle Edit Action
    if (isset($_POST['edit_cost'])) {
        $edit_id = intval($_POST['edit_id']);
        $administration_fee = floatval($_POST['administration']);
        $transportation_cost = floatval($_POST['transportation']);
        $accommodation_cost = floatval($_POST['accommodation']);

        $wpdb->update(
            $transportation_accomodation_fees_table,
            [   
                'administration_fee' => $administration_fee,
                'transportation_cost' => $transportation_cost,
                'accommodation_cost' => $accommodation_cost,
            ],
            ['id' => $edit_id],
            ['%f', '%f', '%f'],
            ['%d']
        );

        echo '<div class="notice notice-success"><p>Cost updated successfully.</p></div>';
    }

    // Handle Delete Action
    if (isset($_POST['delete_cost'])) {
        $delete_id = intval($_POST['delete_id']);
        $wpdb->delete($transportation_accomodation_fees_table, ['id' => $delete_id], ['%d']);
        echo '<div class="notice notice-success"><p>Cost deleted successfully.</p></div>';
    }

    $costs = $wpdb->get_results("SELECT * FROM $transportation_accomodation_fees_table ORDER BY id DESC");
    ?>


    <div class="wrap">
        <h1>Dubkii Booking - Manage Courses</h1>
        <!-- Tab Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'courses'), admin_url('admin.php')); ?>" 
           class="nav-tab <?php echo ($active_tab === 'courses') ? 'nav-tab-active' : ''; ?>">
                Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'booked-courses'), admin_url('admin.php')); ?>" 
            class="nav-tab <?php echo ($active_tab === 'booked-courses') ? 'nav-tab-active' : ''; ?>">
                Booked Courses
            </a>
            <a href="<?php echo add_query_arg(array('page' => 'dubkii-booking', 'active_tab' => 'transport-accommodation'), admin_url('admin.php')); ?>" class="nav-tab <?php echo($active_tab === 'transport-accommodation') ? 'nav-tab-active' : '';?>">Transport and Accommodation</a>
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
                        <th><label for="durations">Durations (weeks)</label></th>
                        <td>
                            <div id="duration-wrapper">
                                <div class="duration-field">
                                    <input type="number" name="durations[]" placeholder="Duration in weeks" required>
                                    <input type="number" name="prices[]" step="0.01" placeholder="Price" required>
                                </div>
                                
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
                            <td><?php echo implode(', ', explode(',', $course->start_dates)); ?></td>
                            <td><?php echo implode(', ', explode(',', $course->durations)); ?></td>
                            <td><?php echo implode(', ', explode(',', $course->prices)); ?></td>
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
                <div class="booked-courses-table" style="width: 75%; padding: 10px;">
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
                            <?php if(!empty($booked_courses)): ?>
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
                                                $formatted_transport_cost = number_format($booking->transport_cost,2);
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
                if (!empty($search_query)){
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
        <div id="transport-accommodation" class="tab-content" style="display:<?php echo($active_tab === 'transport-accommodation') ? 'block' : 'none'; ?>">
            <?php
            if(empty($costs)):
            ?>
                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th><label for="administration">Administration Fee</label></th>
                            <td><input type="number" name="administration" step="0.01" placeholder="Administration Fee" required></td>
                        </tr>
                        <tr>
                            <th><label for="transportation">Transportation</label></th>
                            <td><input type="number" name="transportation" step="0.01" placeholder="Transportation Cost" required></td>
                        </tr>
                        <tr>
                            <th><label for="accommodation">Accommodation</label></th>
                            <td><input type="number" name="accommodation" step="0.01" placeholder="Accommodation Cost" required></td>
                        </tr>
                    </table>
                    <?php submit_button('Submit', 'primary', 'submit_costs'); ?>
                </form>
            <?php else: ?>
                <p>The table already contains records. The form is hidden.</p>
            <?php endif; ?>
            <h2>Saved Costs</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Administration Fee</th>
                        <th>Transportation Cost</th>
                        <th>Accommodation Cost</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($costs)) : ?>
                        <?php foreach ($costs as $cost) : ?>
                            <tr>
                                <td><?php echo esc_html($cost->id); ?></td>
                                <td><?php echo esc_html('$' . number_format($cost->administration_fee, 2)); ?></td>
                                <td><?php echo esc_html('$' . number_format($cost->transportation_cost, 2)); ?></td>
                                <td><?php echo esc_html('$' . number_format($cost->accommodation_cost, 2)); ?></td>
                                <td><?php echo esc_html($cost->created_at); ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <button type="button" class="button button-primary edit-btn" data-id="<?php echo esc_attr($cost->id); ?>"
                                    data-administration="<?php echo esc_attr($cost->administration_fee); ?>"
                                    data-transportation="<?php echo esc_attr($cost->transportation_cost); ?>"
                                    data-accommodation="<?php echo esc_attr($cost->accommodation_cost); ?>"
                                    data-toggle="modal" data-target="#editModal">Edit</button>

                                    <!-- Delete Button -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo esc_attr($cost->id); ?>">
                                        <button type="submit" name="delete_cost" class="button button-secondary" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                        </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">No costs added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Modal Structure -->
            <div id="editModal" class="modal" >
                <div class="modal-content">
                    <h2>Edit Costs</h2>
                    <form method="post" id="editCostForm">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <label for="administration">Administration Fee</label>
                        <input type="number" name="administration" id="administration" step="0.01" required><br>

                        <label for="transportation">Transportation</label>
                        <input type="number" name="transportation" id="transportation" step="0.01" required><br>

                        <label for="accommodation">Accommodation</label>
                        <input type="number" name="accommodation" id="accommodation" step="0.01" required><br>

                        <button type="submit" name="edit_cost" class="button button-primary">Save Changes</button>
                        <button type="button" class="button button-secondary" onclick="closeModal()">Cancel</button>
                    </form>
                </div>
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

        // Open the modal and populate the fields
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const administration = this.getAttribute('data-administration');
                const transportation = this.getAttribute('data-transportation');
                const accommodation = this.getAttribute('data-accommodation');

                // Set the values in the modal form
                document.getElementById('edit_id').value = id;
                document.getElementById('administration').value = administration;
                document.getElementById('transportation').value = transportation;
                document.getElementById('accommodation').value = accommodation;

                // Show the modal
                document.getElementById('editModal').style.display = 'flex';
            });
        });

        // Close the modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function addDurationField() {
            var durationWrapper = document.getElementById('duration-wrapper');
            var newDurationField = document.createElement('div');
            newDurationField.classList.add('duration-field');
            
            // Create duration input
            var durationInput = document.createElement('input');
            durationInput.type = 'number';
            durationInput.name = 'durations[]';
            durationInput.placeholder = 'Duration in weeks';
            newDurationField.appendChild(durationInput);

            // Create price input
            var priceInput = document.createElement('input');
            priceInput.type = 'number';
            priceInput.name = 'prices[]';
            priceInput.step = '0.01';
            priceInput.placeholder = 'Price';
            newDurationField.appendChild(priceInput);

            // Add new fields to the wrapper
            durationWrapper.appendChild(newDurationField);
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

/**
 * Update transportation and accommodation costs in the database.
 *
 * @param int $edit_id ID of the cost to edit.
 * @param float $transportation_cost New transportation cost.
 * @param float $accommodation_cost New accommodation cost.
 * @global $wpdb WordPress database object.
 * @return bool True if the update was successful, false otherwise.
 */
function update_costs($edit_id,$administration_fee, $transportation_cost, $accommodation_cost) {
    global $wpdb;
    global $transportation_accomodation_fees_table;

    $updated = $wpdb->update(
        $transportation_accomodation_fees_table,
        [   
            'administration_fee' => $administration_fee,
            'transportation_cost' => $transportation_cost,
            'accommodation_cost' => $accommodation_cost,
        ],
        ['id' => $edit_id],
        ['%f', '%f'],
        ['%d']
    );

    return $updated !== false;
}

/**
 * Delete a cost entry from the database.
 *
 * @param int $delete_id ID of the cost to delete.
 * @global $wpdb WordPress database object.
 * @return bool True if the deletion was successful, false otherwise.
 */
function delete_cost($delete_id) {
    global $wpdb;
    global $transportation_accomodation_fees_table;

    $deleted = $wpdb->delete($transportation_accomodation_fees_table, ['id' => $delete_id], ['%d']);

    return $deleted !== false;
}

?>

