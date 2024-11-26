<?php
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php'; // Ensure the Stripe PHP SDK is loaded

function create_payment_intent($amount, $email) {
    \Stripe\Stripe::setApiKey('sk_test_51QMaBbEOc0eb0uqdyRYppv21k8ZJrXVpbYTg2cCRAa09ZTpC6Xdggaq9Ckv6fWmNtzCuHVuvg6P63KDijWEv1BtH00CEGLv6Bx'); // Replace with your Stripe secret key

    try {
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => $amount, // Amount in cents
            'currency' => 'usd', // Adjust currency as needed
            'receipt_email' => $email, // Email for the receipt
            'automatic_payment_methods' => ['enabled' => true], // Enable automatic payment methods
        ]);

        $retrieved_intent = \Stripe\PaymentIntent::retrieve($payment_intent->id);
        if($retrieved_intent->status === 'succeeded'){
            return $retrieved_intent;
        }
        return $payment_intent;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe API error: ' . $e->getMessage());
        return null;
    }
}
?>
