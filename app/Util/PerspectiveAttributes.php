<?php

namespace App\Util;

class PerspectiveAttributes
{
    public static $attributes = [
        'TOXICITY' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'de', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'es', 'sv'],
        'SEVERE_TOXICITY' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'sv'],
        'IDENTITY_ATTACK' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'sv'],
        'INSULT' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'sv'],
        'PROFANITY' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'sv'],
        'THREAT' => ['ar', 'zh', 'cs', 'nl', 'en', 'fr', 'hi', 'hi-Latn', 'id', 'it', 'ja', 'ko', 'pl', 'pt', 'ru', 'sv'],
        'SEXUALLY_EXPLICIT'=> ['en'],
    ];

    public static function getAttributesForLanguage($language)
    {
        $attributes = [];

        foreach (self::$attributes as $attribute => $languages) {
            if (in_array($language, $languages)) {
                $attributes[$attribute] = new \stdClass();
            }
        }

        return $attributes;
    }
}
