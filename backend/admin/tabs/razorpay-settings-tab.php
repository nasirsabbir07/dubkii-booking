<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_settings_nonce'])) {
    // Verify nonce
    if (wp_verify_nonce($_POST['razorpay_settings_nonce'], 'save_razorpay_settings')) {
        // Save Razorpay API keys
        update_option('razorpay_key_id', sanitize_text_field($_POST['razorpay_key_id']));
        update_option('razorpay_key_secret', sanitize_text_field($_POST['razorpay_key_secret']));

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    } else {
        echo '<div class="error"><p>Failed to save settings. Please try again.</p></div>';
    }
}

// Get the saved keys
$key_id = get_option('razorpay_key_id', '');
$key_secret = get_option('razorpay_key_secret', '');
?>

<div class="wrap">
    <h2>Razorpay Settings</h2>
    <form method="POST">
        <?php wp_nonce_field('save_razorpay_settings', 'razorpay_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="razorpay_key_id">Razorpay Key ID</label></th>
                <td><input type="text" name="razorpay_key_id" id="razorpay_key_id" value="<?php echo esc_attr($key_id); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="razorpay_key_secret">Razorpay Key Secret</label></th>
                <td><input type="text" name="razorpay_key_secret" id="razorpay_key_secret" value="<?php echo esc_attr($key_secret); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php submit_button('Save Settings'); ?>
    </form>
</div>