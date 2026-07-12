<?php

namespace App\Utility;

use App\Models\Search;

class SearchUtility
{
    // Patterns interdits : injections SQL, XSS, caractères spéciaux suspects
    private static string $BLOCKED_PATTERN = '/[()\'";=<>*\/\\\\]|select\s|sleep\s*\(|sysdate|xor\s|insert\s|update\s|delete\s|drop\s|union\s|from\s|where\s|script\s*[<(]|alert\s*\(/i';

    public static function store($query): void
    {
        if ($query === null || $query === '') {
            return;
        }

        $query = trim($query);

        // Longueur minimale et maximale
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) {
            return;
        }

        // Rejeter les patterns suspects (SQL injection, XSS, chars spéciaux)
        if (preg_match(self::$BLOCKED_PATTERN, $query)) {
            return;
        }

        // Doit contenir au moins 2 caractères alphanumériques consécutifs (pas juste des espaces ou symboles)
        if (!preg_match('/[\p{L}\p{N}]{2,}/u', $query)) {
            return;
        }

        $search = Search::where('query', $query)->first();
        if ($search !== null) {
            $search->count += 1;
            $search->save();
        } else {
            $search = new Search;
            $search->query = $query;
            $search->count = 1;
            $search->save();
        }
    }
}
