<?php
// Transportaion and Accommodation
global $wpdb;
$transportation_accomodation_fees_table = $wpdb->prefix . 'dubkii_transportation_accommodation_fees';

if (isset($_POST['submit_costs'])) {
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
    if (!$ta_id) {
        echo '<div class="notice notice-error"><p>Costs could not be added</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Costs added successfully</p></div>';
    }
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
<div id="transport-accommodation" class="tab-content" style="display:<?php echo ($active_tab === 'transport-accommodation') ? 'block' : 'none'; ?>">
    <?php
    if (empty($costs)):
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
    <div id="editModal" class="modal">
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

<script>
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
</script>

<?php
/**
 * Update transportation and accommodation costs in the database.
 *
 * @param int $edit_id ID of the cost to edit.
 * @param float $transportation_cost New transportation cost.
 * @param float $accommodation_cost New accommodation cost.
 * @global $wpdb WordPress database object.
 * @return bool True if the update was successful, false otherwise.
 */
function update_costs($edit_id, $administration_fee, $transportation_cost, $accommodation_cost)
{
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
function delete_cost($delete_id)
{
    global $wpdb;
    global $transportation_accomodation_fees_table;

    $deleted = $wpdb->delete($transportation_accomodation_fees_table, ['id' => $delete_id], ['%d']);

    return $deleted !== false;
}
