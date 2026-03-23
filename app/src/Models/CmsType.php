<?php

namespace App\Models;

use ValueError;

enum CmsType: string
{
    case Page = 'page';
    case Content = 'content';
    case Event = 'event';
    case Location = 'location';
    case None = 'none';

    public static function convertToType(string $type) {
        try {
            $convertedType = CmsType::from($type);
        }
        catch (ValueError $e) {
            try {
                $convertedType = CmsType::from(substr($type, 0, -1));
            }
            catch (ValueError $e){
                return CmsType::None;
            }
        }
        return $convertedType;

    }
}
