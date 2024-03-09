<?php
namespace Bibek8366\MyPhpApp\Helpers;
include 'vendor/autoload.php';
use stripe\StripeClient;
use stripe\Webhook;
use Exception;
/**
 * Utility class for interacting with the Stripe payment gateway.
 */
class UseStripe {
    /**
     * Initializes Stripe with the provided secret key.
     *
     * @param string $stripeSecretKey The secret key for authenticating with Stripe.
     * @return StripeClient An instance of StripeClient for making requests to Stripe.
     */
    public static function initializeStripe(string $stripeSecretKey): StripeClient {
        try {
            return new StripeClient($stripeSecretKey);
        } catch (Exception $e) {
            error_log("Stripe initialization error: " . $e->getMessage());
            // caller should handle the exception
            throw new Exception("Stripe initialization error: " . $e->getMessage());
        }
    }

    /**
     * Initiates the checkout process with Stripe.
     *
     * @param StripeClient $stripe An instance of StripeClient for making requests to Stripe.
     * @param string $successUrl The URL to redirect to after successful payment.
     * @param string $cancelUrl The URL to redirect to if payment is canceled.
     * @param array $lineItems An array containing details of the line items in the checkout.
     * @throws Exception If an error occurs while retrieving the checkout session.
     * @return void
     */
    public static function checkout(
        StripeClient $stripe,
        string $successUrl,
        string $cancelUrl,
        array $lineItems
    ): void {
        try {
        $checkout_session = $stripe->checkout->sessions->create([
            'success_url' => "$successUrl?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => $cancelUrl,
            'mode' => 'payment',
            'line_items' => [[
                'price' => $lineItems['price'],
                'quantity' => $lineItems['quantity'],
            ]]
        ]);
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
     } catch (Exception $e) {
        error_log("Error creating checkout session: " . $e->getMessage());
        // caller should handle the exception
        throw new Exception("Error creating checkout session: " . $e->getMessage());
     }
    }

    /**
     * Retrieves the checkout session object on successful payment.
     *
     * @param StripeClient $stripe An instance of StripeClient for making requests to Stripe.
     * @return object The checkout session object.
     * @throws Exception If an error occurs while retrieving the checkout session.
     */
    public function getCheckoutSessionOnSuccess(StripeClient $stripe): object {
        try {
            $sessionId = $_GET['session_id'];
            return $stripe->checkout->sessions->retrieve($sessionId);
        } catch (Exception $e) {
            error_log("Error retrieving checkout session: {$e->getMessage()}");
            // Caller should handle the exception
            throw new Exception("Error retrieving checkout session: {$e->getMessage()}");
        }
    }

    /**
     * Handles webhook events from Stripe.
     *
     * @param string $webhookInput The raw input received from the webhook.
     * @param string $stripeSignature The signature header received from Stripe.
     * @param string $webhookSecret The webhook secret configured in the Stripe dashboard.
     * @return void
     */
    public static function webhook(
        string $webhookInput,
        string $stripeSignature,
        string $webhookSecret
    ): void {
        /* Optional but recommended to handle webhook events ----------------- */
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            error_log('Received non POST request to webhook.php');
            exit;
        }
        $event = null;
        try {
            $event = Webhook::constructEvent(
                $webhookInput,
                $stripeSignature,
                $webhookSecret
            );
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        if ($event->type == 'checkout.session.completed') {
            error_log('🔔  Checkout Session was completed!');
            // Handle checkout session completion
        } else {
            error_log('🔔  Other webhook received! ' . $event->type);
            // Handle other webhook events if necessary
        }
        echo json_encode(['status' => 'success']);
    }
}


