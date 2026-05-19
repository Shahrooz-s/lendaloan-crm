<?php

namespace Modules\Invoice\Enums;

use Modules\Core\Support\InteractsWithEnums;

enum PaymentMethodEnum: string {
    use InteractsWithEnums;

    case PAYPAL = 'paypal';
    case STRIPE = 'stripe';
}
