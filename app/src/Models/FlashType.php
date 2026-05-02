<?php

namespace App\Models;

enum FlashType: string
{
    case Error = 'error';
    case Success = 'success';
    case Info = 'info';
}
