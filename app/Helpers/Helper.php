<?php

namespace App\Helpers;

class Helper
{
    public static function capitalizeWords($string)
    {
        $functionWords = ['a', 'an', 'the', 'of', 'for', 'to', 'and'];

        $words = explode(' ', $string);

        foreach ($words as $key => $word) {
            if ($key === 0) {
                $words[$key] = ucfirst($word);
            } 
            elseif (in_array(strtolower($word), $functionWords)) {
                $words[$key] = strtolower($word);
            } 
            else {
                $words[$key] = ucwords($word);
            }
        }

        return implode(' ', $words);
    }
}