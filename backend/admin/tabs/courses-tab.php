<?php
global $wpdb;
$courses_table = $wpdb->prefix . "dubkii_courses";
$durations_table = $wpdb->prefix . "dubkii_course_durations";
$start_dates_table = $wpdb->prefix . "dubkii_course_start_dates";
$personal_details_table = $wpdb->prefix . 'dubkii_personal_details';
$course_prices_table = $wpdb->prefix . 'dubkii_courses_prices';

if (isset($_POST['submit_course'])) {
    $course_name = sanitize_text_field($_POST['course_name']);
    $durations = $_POST['durations'];
    $start_dates = $_POST['start_dates'];
    $prices = array_map('floatval', ($_POST['prices']));

    try {
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
        foreach ($durations as $index => $duration_weeks) {
            if (!empty($duration_weeks)) {
                $wpdb->insert($durations_table, array(
                    'course_id' => $course_id,
                    'duration_weeks' => intval($duration_weeks)
                ));
                $duration_id = $wpdb->insert_id;

                if (!$duration_id) {
                    throw new Exception('Failed to insert into durations table.');
                }
                error_log("Inserted duration with ID: $duration_id");
                // Insert into prices table for each duration
                if (isset($prices[$index]) && !empty($prices[$index])) {
                    $wpdb->insert($course_prices_table, array(
                        'course_id' => $course_id,
                        'duration_id' => $duration_id,
                        'price' => floatval($prices[$index])
                    ));
                    $price_id = $wpdb->insert_id;
                    if (!$price_id) {
                        throw new Exception('Failed to insert into prices table.');
                    }
                    error_log("Inserted price: {$prices[$index]} for duration ID: $duration_id");
                } else {
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

                if (!$wpdb->insert_id) {
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
    } catch (Exception $e) {
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

?>

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
            <?php if (!empty($courses)) : ?>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo $course->id; ?></td>
                        <td><?php echo $course->course_name; ?></td>
                        <td><?php echo implode(', ', explode(',', $course->start_dates)); ?></td>
                        <td><?php echo implode(', ', explode(',', $course->durations)); ?></td>
                        <td><?php echo implode(', ', explode(',', $course->prices)); ?></td>
                        <td>
                            <a href="" class="button delete-course" data-course-id="<?php echo $course->id; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8">No courses found.</td>
                </tr>
            <?php endif; ?>
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
<script>
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
function dubkii_handle_delete_course_ajax()
{
    global $wpdb;
    error_log('Delete course AJAX function called');

    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

    if ($course_id <= 0) {
        wp_send_json_error(['message' => 'Invalid course ID.']);
        return;
    }

    $wpdb->query('START TRANSACTION');
    try {
        // Table definitions
        $courses_table = $wpdb->prefix . 'dubkii_courses';
        $durations_table = $wpdb->prefix . 'dubkii_course_durations';
        $start_dates_table = $wpdb->prefix . 'dubkii_course_start_dates';
        $course_prices_table = $wpdb->prefix . 'dubkii_courses_prices';
        // Log for debugging
        error_log('Attempting to delete related records and course');
        // Deleting related records
        $wpdb->delete($durations_table, ['course_id' => $course_id]);
        $wpdb->delete($start_dates_table, ['course_id' => $course_id]);
        $wpdb->delete($course_prices_table, ['course_id' => $course_id]);

        // Delete the main course record
        $deleted = $wpdb->delete($courses_table, ['id' => $course_id]);

        if (!$deleted) {
            error_log("Failed to delete course from main table.");
            throw new Exception('Failed to delete course.');
        }

        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => 'Course deleted successfully!']);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log("Error during deletion: " . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// Hook the function to admin-ajax action
add_action('wp_ajax_delete_course', 'dubkii_handle_delete_course_ajax');
add_action('wp_ajax_nopriv_delete_course', 'dubkii_handle_delete_course_ajax');
?>