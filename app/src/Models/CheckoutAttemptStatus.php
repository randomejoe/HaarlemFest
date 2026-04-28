<?php

namespace App\Models;

enum CheckoutAttemptStatus: string
{
    case Initiated = 'initiated';
    case HandoffCreated = 'handoff_created';
    case HandoffFailed = 'handoff_failed';
    case Paid = 'paid';
    case Expired = 'expired';
}
