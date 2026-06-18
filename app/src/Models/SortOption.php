<?php

namespace App\Models;

enum SortOption: string
{
    case Name_AZ = 'name_asc';
    case Name_ZA = 'name_desc';
    case Date_Desc = 'date_desc';
    case Date_Asc = 'date_asc';
    case None = 'none';

    public static function convertToOption(string $option): self {
        return self::from($option);
    }
}
