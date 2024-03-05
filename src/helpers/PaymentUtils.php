<?php
namespace MyPhpApp\Helpers;
/**
 * Utility class for handling various payment methods.
 */
class PaymentUtils {
    /**
     * Returns an instance of the UseStripe class for handling Stripe payments.
     *
     * @return UseStripe An instance of the UseStripe class.
     */
    public static function useStripe(): UseStripe {
        return new UseStripe();
    }

    // More methods for other payment methods (e.g., eSewa, Khalti, IME Pay, etc.) coming soon...
}



