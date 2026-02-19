<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Database\DB;

final class WebhookController
{
    /** @param array<string, string> $params */
    public static function paypal(array $params, array $payload): array
    {
        // Verify PayPal webhook signature, then handle event (payment.capture.completed, etc.)
        // Create invoice, add credits, update subscription.
        return ['received' => true];
    }
}
