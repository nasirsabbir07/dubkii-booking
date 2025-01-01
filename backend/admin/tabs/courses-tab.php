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

if (isset($_POST['update_course'])) {
    $course_id = intval($_POST['course_id']);
    $course_name = sanitize_text_field($_POST['course_name']);
    $durations = $_POST['edit_durations'];
    $start_dates = $_POST['edit_start_dates'];
    $prices = array_map('floatval', ($_POST['edit_prices']));

    try {
        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Update course name
        $wpdb->update($courses_table, array(
            'course_name' => $course_name,
        ), array('id' => $course_id));

        // Delete old durations, prices, and start dates
        $wpdb->delete($durations_table, array('course_id' => $course_id));
        $wpdb->delete($course_prices_table, array('course_id' => $course_id));
        $wpdb->delete($start_dates_table, array('course_id' => $course_id));

        // Insert new durations and prices
        foreach ($durations as $index => $duration_weeks) {
            if (!empty($duration_weeks)) {
                // Insert into durations table
                $wpdb->insert($durations_table, array(
                    'course_id' => $course_id,
                    'duration_weeks' => intval($duration_weeks)
                ));
                $duration_id = $wpdb->insert_id;

                // Insert into prices table for each duration
                if (isset($prices[$index]) && !empty($prices[$index])) {
                    $wpdb->insert($course_prices_table, array(
                        'course_id' => $course_id,
                        'duration_id' => $duration_id,
                        'price' => floatval($prices[$index])
                    ));
                }
            }
        }

        // Insert new start dates
        foreach ($start_dates as $start_date) {
            if (!empty($start_date)) {
                $wpdb->insert($start_dates_table, array(
                    'course_id' => $course_id,
                    'start_date' => sanitize_text_field($start_date)
                ));
            }
        }

        // Commit the transaction
        $wpdb->query('COMMIT');
        add_settings_error('dubkii_booking', 'course_update_success', 'Course updated successfully!', 'updated');
    } catch (Exception $e) {
        // Rollback in case of error
        $wpdb->query('ROLLBACK');
        add_settings_error('dubkii_booking', 'course_update_error', 'An error occurred: ' . $e->getMessage(), 'error');
    }
}

if (isset($_POST['delete_course'])) {
    $course_id = intval($_POST['delete_course_id']);

    try {
        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Delete related durations, prices, and start dates
        $wpdb->delete($durations_table, array('course_id' => $course_id));
        $wpdb->delete($course_prices_table, array('course_id' => $course_id));
        $wpdb->delete($start_dates_table, array('course_id' => $course_id));

        // Delete the course
        $deleted = $wpdb->delete($courses_table, array('id' => $course_id));
        if (!$deleted) {
            throw new Exception('Failed to delete course.');
        }

        // Commit the transaction
        $wpdb->query('COMMIT');
        add_settings_error('dubkii_booking', 'course_delete_success', 'Course deleted successfully!', 'updated');
    } catch (Exception $e) {
        // Rollback in case of error
        $wpdb->query('ROLLBACK');
        add_settings_error('dubkii_booking', 'course_delete_error', 'Error deleting course: ' . $e->getMessage(), 'error');
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
            ORDER BY c.id DESC
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
                        <input type="date" name="start_dates[]" class="start-date-field">
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
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="course_id" value="<?php echo esc_attr($course->id); ?>" />
                                <button type="button" class="button" onclick='openCourseEditModal(<?php echo json_encode($course); ?>)'>Edit</button>
                            </form>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="delete_course_id" value="<?php echo esc_attr($course->id); ?>" />
                                <button type="submit" name="delete_course" class="button button-danger" onclick="return confirm('Are you sure you want to delete this course?');">Delete</button>
                            </form>
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
    <div id="editCourseModal" class="modal" style="display: none;">
        <div class="modal-content edit-modal">
            <h2>Edit Course</h2>
            <form id="editCourseForm" method="POST">
                <input type="hidden" name="course_id" id="edit_course_id" />
                <table class="form-table">
                    <tr>
                        <th><label for="edit_course_name">Course Name</label></th>
                        <td><input type="text" name="course_name" id="edit_course_name" required /></td>
                    </tr>
                    <tr>
                        <th><label for="edit_durations">Durations (weeks)</label></th>
                        <td>
                            <div id="edit_duration_wrapper">
                                <!-- Dynamically populated duration fields -->
                            </div>
                            <button type="button" onclick="addEditDurationField()">Add Another Duration</button>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="edit_start_dates">Start Dates</label></th>
                        <td>
                            <div id="edit_start_date_wrapper">
                                <!-- Dynamically populated start date fields -->
                            </div>
                            <button type="button" onclick="addEditStartDateField()">Add Another Start Date</button>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" name="update_course" class="button button-primary" value="Update Course" /></p>
                <button style="width: 100%;" type="button" class="button button-secondary" onclick="closeCourseModal()">Cancel</button>
            </form>
        </div>
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
        input.classList.add('start-date-field')
        input.type = 'date';
        input.name = 'start_dates[]';
        wrapper.appendChild(input);
    }

    function openCourseEditModal(course) {
        document.getElementById('edit_course_id').value = course.id;
        document.getElementById('edit_course_name').value = course.course_name;

        // Populate durations
        const durationWrapper = document.getElementById('edit_duration_wrapper');
        durationWrapper.innerHTML = ''; // Clear existing fields
        const durations = course.durations.split(',');
        const prices = course.prices.split(',');
        durations.forEach((duration, index) => {
            const durationField = `
            <div class="duration-field">
                <input type="number" name="edit_durations[]" value="${duration.trim()}" placeholder="Duration in weeks" required />
                <input type="number" name="edit_prices[]" value="${prices[index]?.trim() || ''}" step="0.01" placeholder="Price" required />
            </div>
        `;
            durationWrapper.insertAdjacentHTML('beforeend', durationField);
        });

        // Populate start dates
        const startDateWrapper = document.getElementById('edit_start_date_wrapper');
        startDateWrapper.innerHTML = ''; // Clear existing fields
        const startDates = course.start_dates.split(',');
        startDates.forEach((date) => {
            const dateField = `
            <div class="start-date-field">
                <input type="date" name="edit_start_dates[]" value="${date.trim()}" required />
            </div>
        `;
            startDateWrapper.insertAdjacentHTML('beforeend', dateField);
        });

        // Open the modal
        document.getElementById('editCourseModal').style.display = 'flex';
    }
    // Function to add a new duration field in edit mode
    function addEditDurationField() {
        const durationWrapper = document.getElementById('edit_duration_wrapper');
        const newDurationField = document.createElement('div');
        newDurationField.classList.add('duration-field');

        // Create duration input
        const durationInput = document.createElement('input');
        durationInput.type = 'number';
        durationInput.name = 'edit_durations[]';
        durationInput.placeholder = 'Duration in weeks';
        newDurationField.appendChild(durationInput);

        // Create price input
        const priceInput = document.createElement('input');
        priceInput.type = 'number';
        priceInput.name = 'edit_prices[]';
        priceInput.step = '0.01';
        priceInput.placeholder = 'Price';
        newDurationField.appendChild(priceInput);

        // Add new fields to the wrapper
        durationWrapper.appendChild(newDurationField);
    }

    // Function to add a new start date field in edit mode
    function addEditStartDateField() {
        const startDateWrapper = document.getElementById('edit_start_date_wrapper');
        const newStartDateField = document.createElement('div');
        newStartDateField.classList.add('start-date-field');

        // Create start date input
        const startDateInput = document.createElement('input');
        startDateInput.type = 'date';
        startDateInput.name = 'edit_start_dates[]';
        newStartDateField.appendChild(startDateInput);

        // Add new start date field to the wrapper
        startDateWrapper.appendChild(newStartDateField);
    }

    function closeCourseModal() {
        document.getElementById('editCourseModal').style.display = 'none';
    }
</script>