<?php

namespace App\Models;

use ValueError;

enum HistoryTourLanguage: string
{
    case English = 'English';
    case Dutch = 'Dutch';
    case Chinese = 'Chinese';
    case Unknown = 'Unknown';

    public static function convertToLanguage(string $language) {
        try {
            $convertedLanguage = HistoryTourLanguage::from($language);
        }
        catch (ValueError $e) {
            $convertedLanguage = HistoryTourLanguage::Unknown;
        }

        return $convertedLanguage;

    }
}
