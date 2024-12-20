<?php
global $wpdb;
$table_name_coupons = $wpdb->prefix . 'dubkii_coupons';

// Handle form submission for adding a new coupon
if (isset($_POST['create_coupon'])) {
    // Sanitize and validate input fields
    $code = sanitize_text_field($_POST['code']);
    $discount_type = sanitize_text_field($_POST['discount_type']);
    $max_redemptions = intval($_POST['max_redemptions']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    try {
        // Optionally specify the timezone if necessary
        $formatted_expiry_date = (new DateTime($expiry_date))->format('Y-m-d H:i:s');
        // For debugging, you can log or echo the formatted date
        error_log('Formatted expiry date: ' . $formatted_expiry_date);
    } catch (Exception $e) {
        // Log the error message for further troubleshooting
        error_log('Error: ' . $e->getMessage());
        wp_die('Invalid expiry date format.');
    }
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate coupon code
    if (empty($code)) {
        wp_die('Coupon code is required.');
    }

    // Validate max redemptions
    if ($max_redemptions <= 0) {
        wp_die('Max redemptions must be greater than zero.');
    }

    // Validate expiry date
    if (empty($expiry_date)) {
        wp_die('Expiry date is required.');
    }

    // Process discount-specific fields
    $data = [
        'code' => $code,
        'discount_type' => $discount_type,
        'max_redemptions' => $max_redemptions,
        'expiry_date' => $formatted_expiry_date,
        'is_active' => $is_active,
    ];

    // Process discount-specific fields
    if ($discount_type === 'fixed') {
        $discount_value = floatval($_POST['discount_value']);
        if ($discount_value <= 0) {
            wp_die('Fixed discount value must be greater than zero.');
        }

        $data['discount_value'] = $discount_value;
        $data['min_price_range'] = NULL;
        $data['max_price_range'] = NULL;
        $data['min_discount_percentage'] = NULL;
        $data['max_discount_percentage'] = NULL;
    } elseif ($discount_type === 'percentage') {
        $min_price_range = floatval($_POST['min_price_range']);
        $max_price_range = floatval($_POST['max_price_range']);
        $min_discount_percentage = floatval($_POST['min_discount_percentage']);
        $max_discount_percentage = floatval($_POST['max_discount_percentage']);

        // Validate percentage-based fields
        if ($min_price_range <= 0 || $max_price_range <= 0 || $min_price_range > $max_price_range) {
            wp_die('Invalid price range. Ensure that Min Price is less than Max Price');
        }
        if ($min_discount_percentage < 0 || $max_discount_percentage > 100 || $min_discount_percentage > $max_discount_percentage) {
            wp_die('Invalid percentage range. Ensure that Min Percentage is less than or equal to Max Percentage and within 0-100');
        }

        $data['discount_value'] = NULL;
        $data['min_price_range'] = $min_price_range;
        $data['max_price_range'] = $max_price_range;
        $data['min_discount_percentage'] = $min_discount_percentage;
        $data['max_discount_percentage'] = $max_discount_percentage;
    } else {
        wp_die('Invalid discount type.');
    }
    error_log(print_r($data, true));
    // Insert new coupon into the database
    $wpdb->insert(
        $table_name_coupons,
        $data,
        [
            '%s', // code
            '%s', //discount type
            '%d', // max redemption
            '%s', // expiry_date
            '%d', // is_active
            '%f', // discount value
            '%f', // min price range
            '%f', // max price range
            '%f', // min discount range
            '%f' // max discount range

        ]
    );

    $coupon = $wpdb->insert_id;
    if ($wpdb->last_error) {
        error_log('SQL Error: ' . $wpdb->last_error);
    }
    error_log($coupon);

    if (!$coupon) {
        echo '<div class="notice notice-error"><p>Coupon could not be created.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Coupon created succefully.</p></div>';
    }
}


// Handle form submission for updating a coupon
if (isset($_POST['update_coupon']) && isset($_POST['coupon_id'])) {
    $coupon_id = intval($_POST['coupon_id']);

    // Sanitize and validate input fields
    $code = sanitize_text_field($_POST['code']);
    $discount_type = sanitize_text_field($_POST['discount_type']);
    $max_redemptions = intval($_POST['max_redemptions']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($code)) {
        wp_die('Coupon code is required.');
    }
    if ($max_redemptions <= 0) {
        wp_die('Max redemptions must be greater than zero.');
    }
    if (empty($expiry_date)) {
        wp_die('Expiry date is required.');
    }

    // Process discount-specific fields
    $data = [
        'code' => $code,
        'max_redemptions' => $max_redemptions,
        'expiry_date' => $expiry_date,
        'is_active' => $is_active,
    ];

    // Process discount-specific fields based on current discount type
    $existing_coupon = $wpdb->get_row($wpdb->prepare(
        "SELECT discount_type FROM $table_name_coupons WHERE id = %d",
        $coupon_id
    ));

    if (!$existing_coupon) {
        wp_die('Coupon not found.');
    }

    $discount_type = $existing_coupon->discount_type;

    if ($discount_type === 'fixed') {
        $discount_value = floatval($_POST['discount_value']);
        if ($discount_value <= 0) {
            wp_die('Fixed discount value must be greater than zero.');
        }
        $data['discount_value'] = $discount_value;
        $data['min_price_range'] = NULL;
        $data['max_price_range'] = NULL;
        $data['min_discount_percentage'] = NULL;
        $data['max_discount_percentage'] = NULL;
    } elseif ($discount_type === 'percentage') {
        $min_price_range = floatval($_POST['min_price_range']);
        $max_price_range = floatval($_POST['max_price_range']);
        $min_discount_percentage = floatval($_POST['min_discount_percentage']);
        $max_discount_percentage = floatval($_POST['max_discount_percentage']);

        // Validate percentage-based fields
        if ($min_price_range <= 0 || $max_price_range <= 0 || $min_price_range > $max_price_range) {
            wp_die('Invalid price range. Ensure Min Price is less than Max Price.');
        }
        if ($min_discount_percentage < 0 || $max_discount_percentage > 100 || $min_discount_percentage > $max_discount_percentage) {
            wp_die('Invalid percentage range. Ensure Min Percentage <= Max Percentage and within 0-100.');
        }

        $data['discount_value'] = NULL;
        $data['min_price_range'] = $min_price_range;
        $data['max_price_range'] = $max_price_range;
        $data['min_discount_percentage'] = $min_discount_percentage;
        $data['max_discount_percentage'] = $max_discount_percentage;
    } else {
        wp_die('Invalid discount type.');
    }

    // Update the coupon in the database
    $updated = $wpdb->update(
        $table_name_coupons,
        $data,
        ['id' => $coupon_id],
        [
            '%s', // code
            '%d', // max redemption
            '%s', // expiry_date
            '%d', // is_active
            '%f', // discount value
            '%f', // min price range
            '%f', // max price range
            '%f', // min discount range
            '%f' // max discount range

        ],
        ['%d']
    );

    // Provide feedback
    if ($updated !== false) {
        echo '<div class="notice notice-success"><p>Coupon updated successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Coupon could not be updated.</p></div>';
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
    <form method="POST" id="coupon_form" style="margin-bottom: 30px;">
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
            <!-- Fixed Discount -->
            <tr id="fixed_discount_row" style="display:none;">
                <th><label for="discount_value">Fixed Discount Amount</label></th>
                <td><input type="number" name="discount_value" id="fixed_discount" step="0.01" min="0"></td>
            </tr>
            <!-- Percentage Discount -->
            <tr id="percentage_discount_row" style="display:none;">
                <th><label for="price_range">Applicable Price Range (Min - Max)</label></th>
                <td>
                    <input type="number" name="min_price_range" id="min_price_range" step="0.01" placeholder="Min Price">
                    <input type="number" name="max_price_range" id="max_price_range" step="0.01" placeholder="Max Price">
                </td>
            </tr>
            <tr id="percentage_range_row" style="display:none;">
                <th><label for="discount_percentage">Percentage Range (Min - Max)</label></th>
                <td>
                    <input type="number" name="min_discount_percentage" id="min_discount_percentage" step="0.01" min="0" max="100" placeholder="Min %">
                    <input type="number" name="max_discount_percentage" id="max_discount_percentage" step="0.01" min="0" max="100" placeholder="Max %">
                </td>
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
        <!-- <p><input type="submit" name="add_coupon" class="button button-primary" value="Add Coupon" /></p> -->
        <?php submit_button('Create Coupon', 'primary', 'create_coupon'); ?>
    </form>

    <h2>Existing Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Max Course Price</th>
                <th>Min Course Price</th>
                <th>Max Discount Percent</th>
                <th>Min Discount Percent</th>
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
                        <td><?php echo esc_html($coupon['discount_value'] !== null ? $coupon['discount_value'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($coupon['max_price_range'] !== null ? $coupon['max_price_range'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($coupon['min_price_range'] !== null ? $coupon['min_price_range'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($coupon['max_discount_percentage'] !== null ? $coupon['max_discount_percentage'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($coupon['min_discount_percentage'] !== null ? $coupon['min_discount_percentage'] : 'N/A'); ?></td>
                        <td><?php echo esc_html($coupon['max_redemptions']); ?></td>
                        <td><?php echo esc_html($coupon['current_redemptions']); ?></td>
                        <td><?php echo esc_html(date('d-m-Y H:i', strtotime($coupon['expiry_date']))); ?></td>
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
                        <td><span id="edit_discount_type" name="edit_discount_type"></span></td>
                    </tr>
                    <!-- Fixed Discount -->
                    <tr id="edit_fixed_discount_row" style="display:none;">
                        <th><label for="discount_value">Fixed Discount Amount</label></th>
                        <td><input type="number" name="discount_value" id="edit_fixed_discount" step="0.01" min="0"></td>
                    </tr>
                    <!-- Percentage Discount -->
                    <tr id="edit_percentage_discount_row" style="display:none;">
                        <th><label for="price_range">Applicable Price Range (Min - Max)</label></th>
                        <td>
                            <input type="number" name="min_price_range" id="edit_min_price_range" step="0.01" placeholder="Min Price">
                            <input type="number" name="max_price_range" id="edit_max_price_range" step="0.01" placeholder="Max Price">
                        </td>
                    </tr>
                    <tr id="edit_percentage_range_row" style="display:none;">
                        <th><label for="discount_percentage">Percentage Range (Min - Max)</label></th>
                        <td>
                            <input type="number" name="min_discount_percentage" id="edit_min_discount_percentage" step="0.01" min="0" max="100" placeholder="Min %">
                            <input type="number" name="max_discount_percentage" id="edit_max_discount_percentage" step="0.01" min="0" max="100" placeholder="Max %">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="max_redemptions">Max Redemptions</label></th>
                        <td><input type="number" name="max_redemptions" id="edit_max_redemptions" required /></td>
                    </tr>
                    <tr>
                        <th><label for="expiry_date">Expiry Date</label></th>
                        <td><input type="datetime-local" name="expiry_date" id="edit_expiry_date" required /></td>
                    </tr>

                    <tr>
                        <th><label for="edit_is_active">Active</label></th>
                        <td><input type="checkbox" name="is_active" id="edit_is_active" value="1" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="update_coupon" class="button button-primary" value="Update Coupon" /></p>
                <button style="width: 100%;" type="button" class="button button-secondary" onclick="closeCouponModal()">Cancel</button>
            </form>
        </div>
    </div>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('coupon_form');
        const discountTypeField = document.getElementById('discount_type');
        const couponCodeField = document.getElementById('coupon_code');
        const fixedDiscountField = document.getElementById('fixed_discount');
        const minPriceField = document.getElementById('min_price_range');
        const maxPriceField = document.getElementById('max_price_range');
        const minPercentageField = document.getElementById('min_discount_percentage');
        const maxPercentageField = document.getElementById('max_discount_percentage');
        const expiryDateField = document.getElementById('expiry_date');

        function updateDiscountFields() {
            const discountType = discountTypeField.value;

            // Show/hide rows based on the selected discount type
            document.getElementById('fixed_discount_row').style.display = (discountType === 'fixed') ? '' : 'none';
            document.getElementById('percentage_discount_row').style.display = (discountType === 'percentage') ? '' : 'none';
            document.getElementById('percentage_range_row').style.display = (discountType === 'percentage') ? '' : 'none';
        }

        discountTypeField.addEventListener('change', updateDiscountFields);
        updateDiscountFields(); // Initialize fields visibility

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let errorMessage = '';

            // Validate coupon code
            if (!couponCodeField.value.trim()) {
                isValid = false;
                errorMessage += 'Coupon code is required.\n';
            }

            // Validate discount type-specific fields
            if (discountTypeField.value === 'fixed') {
                if (!fixedDiscountField.value || parseFloat(fixedDiscountField.value) <= 0) {
                    isValid = false;
                    errorMessage += 'Fixed discount value must be greater than zero.\n';
                }
            } else if (discountTypeField.value === 'percentage') {
                if (!minPriceField.value || !maxPriceField.value || parseFloat(minPriceField.value) <= 0 || parseFloat(maxPriceField.value) <= 0 || parseFloat(minPriceField.value) > parseFloat(maxPriceField.value)) {
                    isValid = false;
                    errorMessage += 'Valid price range (min < max) is required.\n';
                }
                if (!minPercentageField.value || !maxPercentageField.value || parseFloat(minPercentageField.value) < 0 || parseFloat(maxPercentageField.value) > 100 || parseFloat(minPercentageField.value) > parseFloat(maxPercentageField.value)) {
                    isValid = false;
                    errorMessage += 'Valid percentage range (min <= max and within 0-100) is required.\n';
                }
            }

            // Validate expiry date
            if (!expiryDateField.value) {
                isValid = false;
                errorMessage += 'Expiry date is required.\n';
            }

            if (!isValid) {
                e.preventDefault(); // Prevent form submission
                alert(errorMessage.trim()); // Show error messages
            }
        });
    });

    function openEditModal(coupon) {
        // Populate modal fields with matching IDs
        document.getElementById('edit_coupon_id').value = coupon.id;
        document.getElementById('edit_code').value = coupon.code;

        // Display discount type as plain text
        document.getElementById('edit_discount_type').textContent = coupon.discount_type === 'fixed' ? 'Fixed Amount' : 'Percentage';
        console.log(coupon.discount_type);
        // Handle the discount value based on the type
        if (coupon.discount_type === 'fixed') {
            document.getElementById('edit_fixed_discount_row').style.display = 'table-row';
            document.getElementById('edit_percentage_discount_row').style.display = 'none';
            document.getElementById('edit_percentage_range_row').style.display = 'none';

            // Set the fixed discount value
            const fixedDiscountField = document.getElementById('edit_fixed_discount');
            if (fixedDiscountField) {
                fixedDiscountField.value = coupon.discount_value || '';
            }
        } else if (coupon.discount_type === 'percentage') {
            document.getElementById('edit_fixed_discount_row').style.display = 'none';
            document.getElementById('edit_percentage_discount_row').style.display = 'table-row';
            document.getElementById('edit_percentage_range_row').style.display = 'table-row';

            // Populate percentage discount fields
            document.getElementById('edit_min_price_range').value = coupon.min_price_range || '';
            document.getElementById('edit_max_price_range').value = coupon.max_price_range || '';
            document.getElementById('edit_min_discount_percentage').value = coupon.min_discount_percentage || '';
            document.getElementById('edit_max_discount_percentage').value = coupon.max_discount_percentage || '';
        }

        // Populate other fields
        document.getElementById('edit_max_redemptions').value = coupon.max_redemptions || '';
        document.getElementById('edit_expiry_date').value = coupon.expiry_date.replace(' ', 'T') || '';
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
        }
    };
</script>