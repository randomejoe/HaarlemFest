<?php

namespace App\Models;

use ValueError;

enum HistoryTourLanguage: string
{
    case English = 'English';
    case Dutch = 'Dutch';
    case Chinese = 'Chinese';

    public static function convertToLanguage(string $language) {
        $convertedLanguage = HistoryTourLanguage::from($language);

        return $convertedLanguage;

    }
}
