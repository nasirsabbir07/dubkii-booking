<?php
global $wpdb;
$table_name_coupons = $wpdb->prefix . 'dubkii_coupons';

// Handle form submission for adding a new coupon
if (isset($_POST['add_coupon'])) {
    $code = sanitize_text_field($_POST['code']);
    $discount_type = sanitize_text_field($_POST['discount_type']);
    $discount_value = floatval($_POST['discount_value']);
    $max_redemptions = intval($_POST['max_redemptions']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Insert new coupon into the database
    $wpdb->insert(
        $table_name_coupons,
        [
            'code' => $code,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'max_redemptions' => $max_redemptions,
            'expiry_date' => $expiry_date,
            'is_active' => $is_active,
        ],
        [
            '%s',
            '%s',
            '%f',
            '%d',
            '%s',
            '%d'
        ]
    );
}

// Edit Coupon
if (isset($_POST['update_coupon']) && isset($_POST['coupon_id'])) {
    $coupon_id = intval($_POST['coupon_id']);
    $data = [
        'code' => $_POST['code'],
        'discount_type' => $_POST['discount_type'],
        'discount_value' => $_POST['discount_value'],
        'max_redemptions' => $_POST['max_redemptions'],
        'expiry_date' => $_POST['expiry_date'],
        'is_active' => isset($_POST['is_active']),
    ];

    $updated = update_coupon($coupon_id, $data);

    if ($updated !== false) {
        echo '<div class="notice notice-success"><p>Coupon updated successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to update coupon.</p></div>';
    }
}


// Delete Coupon
if (isset($_POST['delete_coupon']) && isset($_POST['coupon_id'])) {
    $coupon_id = intval($_POST['coupon_id']);
    $table_name_coupons = $wpdb->prefix . 'dubkii_coupons';
    $wpdb->delete($table_name_coupons, ['id' => $coupon_id]);

    echo '<div class="notice notice-success"><p>Coupon deleted successfully.</p></div>';
}

// Fetch all coupons with improved performance due to indexing
$coupons = $wpdb->get_results("SELECT * FROM $table_name_coupons ORDER BY expiry_date ASC", ARRAY_A);
?>
<div id="coupons" class="tab-content" style="display: <?php echo ($active_tab === 'coupons') ? 'block' : 'none'; ?>;">
    <h1>Coupons Management</h1>
    <form method="POST" style="margin-bottom: 30px;">
        <h2>Add New Coupon</h2>
        <table class="form-table">
            <tr>
                <th><label for="code">Coupon Code</label></th>
                <td><input type="text" name="code" id="code" required /></td>
            </tr>
            <tr>
                <th><label for="discount_type">Discount Type</label></th>
                <td>
                    <select name="discount_type" id="discount_type" required>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="discount_value">Discount Value</label></th>
                <td><input type="number" step="0.01" name="discount_value" id="discount_value" required /></td>
            </tr>
            <tr>
                <th><label for="max_redemptions">Max Redemptions</label></th>
                <td><input type="number" name="max_redemptions" id="max_redemptions" required /></td>
            </tr>
            <tr>
                <th><label for="expiry_date">Expiry Date</label></th>
                <td><input type="datetime-local" name="expiry_date" id="expiry_date" required /></td>
            </tr>
            <tr>
                <th><label for="is_active">Active</label></th>
                <td><input type="checkbox" name="is_active" id="is_active" value="1" /></td>
            </tr>
        </table>
        <p><input type="submit" name="add_coupon" class="button button-primary" value="Add Coupon" /></p>
    </form>

    <h2>Existing Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Max Redemptions</th>
                <th>Current Redemptions</th>
                <th>Expiry Date</th>
                <th>Active</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($coupons)) : ?>
                <?php foreach ($coupons as $coupon) : ?>
                    <tr>
                        <td><?php echo esc_html($coupon['id']); ?></td>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><?php echo esc_html($coupon['discount_type']); ?></td>
                        <td><?php echo esc_html($coupon['discount_value']); ?></td>
                        <td><?php echo esc_html($coupon['max_redemptions']); ?></td>
                        <td><?php echo esc_html($coupon['current_redemptions']); ?></td>
                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($coupon['expiry_date']))); ?></td>
                        <td><?php echo $coupon['is_active'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="coupon_id" value="<?php echo esc_attr($coupon['id']); ?>" />
                                <button type="button" class="button" onclick='openEditModal(<?php echo json_encode($coupon); ?>)'>Edit</button>

                            </form>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="coupon_id" value="<?php echo esc_attr($coupon['id']); ?>" />
                                <button type="submit" name="delete_coupon" class="button button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8">No coupons found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div id="editCouponModal" class="modal" style="display: none;">
        <div class="modal-content">
            <!-- <span class="close-button" onclick="closeModal()">&times;</span> -->
            <h2>Edit Coupon</h2>
            <form id="editCouponForm" method="POST">
                <input type="hidden" name="coupon_id" id="edit_coupon_id" />
                <table class="form-table">
                    <tr>
                        <th><label for="edit_code">Coupon Code</label></th>
                        <td><input type="text" name="code" id="edit_code" required /></td>
                    </tr>
                    <tr>
                        <th><label for="edit_discount_type">Discount Type</label></th>
                        <td>
                            <select name="discount_type" id="edit_discount_type" required>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="edit_discount_value">Discount Value</label></th>
                        <td><input type="number" step="0.01" name="discount_value" id="edit_discount_value" required /></td>
                    </tr>
                    <tr>
                        <th><label for="edit_max_redemptions">Max Redemptions</label></th>
                        <td><input type="number" name="max_redemptions" id="edit_max_redemptions" required /></td>
                    </tr>
                    <tr>
                        <th><label for="edit_expiry_date">Expiry Date</label></th>
                        <td><input type="datetime-local" name="expiry_date" id="edit_expiry_date" required /></td>
                    </tr>
                    <tr>
                        <th><label for="edit_is_active">Active</label></th>
                        <td><input type="checkbox" name="is_active" id="edit_is_active" value="1" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="update_coupon" class="button button-primary" value="Update Coupon" /></p>
                <button type="button" class="button button-secondary" onclick="closeCouponModal()">Cancel</button>
            </form>
        </div>
    </div>

</div>
<script>
    function openEditModal(coupon) {
        // Populate modal fields
        document.getElementById('edit_coupon_id').value = coupon.id;
        document.getElementById('edit_code').value = coupon.code;
        document.getElementById('edit_discount_type').value = coupon.discount_type;
        document.getElementById('edit_discount_value').value = coupon.discount_value;
        document.getElementById('edit_max_redemptions').value = coupon.max_redemptions;
        document.getElementById('edit_expiry_date').value = coupon.expiry_date.replace(' ', 'T');
        document.getElementById('edit_is_active').checked = coupon.is_active == 1;

        // Show the modal
        document.getElementById('editCouponModal').style.display = 'flex';
    }

    function closeCouponModal() {
        document.getElementById('editCouponModal').style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('editCouponModal')) {
            closeCouponModal();
            closeModal();
        }
    };
</script>
<?php
function update_coupon($coupon_id, $data)
{
    global $wpdb;
    $table_name_coupons = $wpdb->prefix . 'dubkii_coupons';

    return $wpdb->update(
        $table_name_coupons,
        [
            'code' => sanitize_text_field($data['code']),
            'discount_type' => sanitize_text_field($data['discount_type']),
            'discount_value' => floatval($data['discount_value']),
            'max_redemptions' => intval($data['max_redemptions']),
            'expiry_date' => sanitize_text_field($data['expiry_date']),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ],
        ['id' => intval($coupon_id)]
    );
}
?>