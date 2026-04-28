<?php

namespace App\Models;

enum HoldExpiryReason: string
{
    case Released = 'released';
    case Cooldown = 'cooldown';
    case Skipped = 'skipped';
}
