<?php

namespace huqis\helper;

use \Exception;

/**
 * String helper
 */
class StringHelper {

    /**
     * Default character haystack for generating strings
     * @var string
     */
    const GENERATE_HAYSTACK = '123456789bcdfghjkmnpqrstvwxyz';

    /**
     * Escape double quotes
     * @param string $string String to escape
     * @return string Incoming string with " replaced with \"
     */
    public static function escapeQuotes($string) {
        $result = '';
        for ($index = 0, $count = strlen($string); $index < $count; $index++) {
            if ($string[$index] == '"') {
                $result .= '\\';
            }

            $result .= $string[$index];
        }

        return $result;
    }

    /**
     * Checks whether the string starts with the provided start
     * @param string $string String to check
     * @param string|array $start Start or an array of start strings
     * @param boolean $isCaseInsensitive Set to true to ignore case
     * @return boolean True when the string starts with the provided start
     */
    public static function startsWith($string, $start, $isCaseInsensitive = false) {
        if (!is_array($start)) {
            $start = [$start];
        }

        if ($isCaseInsensitive) {
            $string = strtoupper($string);
        }

        foreach ($start as $token) {
            if ($isCaseInsensitive) {
                $token = strtoupper($token);
            }

            $startLength = strlen($token);
            if (strncmp($string, $token, $startLength) == 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Truncates the provided string
     * @param string $string String to truncate
     * @param integer $length Number of characters to keep
     * @param string $etc String to truncate with
     * @param boolean $breakWords Set to true to keep words as a whole
     * @return string Truncated string
     * @throws Exception when the provided length is not a positive integer
     */
    public static function truncate($string, $length = 80, $etc = '...', $breakWords = false) {
        if (!$string) {
            return '';
        }

        if (!is_numeric($length) || $length <= 0) {
            throw new Exception('Could not truncate the string: provided length is not a positive integer');
        }

        if (strlen($string) < $length) {
            return $string;
        }

        if (!is_string($etc)) {
            throw new Exception('Could not truncate the string: provided etc is not a string');
        }

        $length -= strlen($etc);
        if (!$breakWords) {
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
        }

        return substr($string, 0, $length) . $etc;
    }

    /**
     * Gets a safe string for file name and URL usage
     * @param string $string String to get the safe string of
     * @param string $replacement Replacement string for all non alpha numeric
     * characters
     * @param boolean $lower Set to false to skip strtolower
     * @return string Safe string for file names and URLs
     */
    public static function safeString($string, $replacement = '-', $lower = true) {
        if (!$string) {
            return $string;
        }

        $encoding = mb_detect_encoding($string);
        if ($encoding != 'ASCII') {
            $string = iconv($encoding, 'ASCII//TRANSLIT//IGNORE', $string);
        }

        $string = preg_replace("/[\s]/", $replacement, $string);
        $string = preg_replace("/[^A-Za-z0-9._-]/", '', $string);

        if ($lower) {
            $string = strtolower($string);
        }

        return $string;
    }

    /**
     * Generates a random string
     * @param integer $length Number of characters to generate
     * @param string $haystack String with the haystack to pick characters from
     * @return string A random string
     * @throws Exception when an invalid length is provided
     * @throws Exception when an empty haystack is provided
     * @throws Exception when the requested length is greater then the length
     * of the haystack
     */
    public static function generate($length = 8, $haystack = null) {
        $string = '';
        if ($haystack === null) {
            $haystack = self::GENERATE_HAYSTACK;
        }

        if (!is_integer($length) || $length <= 0) {
            throw new Exception('Could not generate a random string: invalid length provided');
        }

        if (!is_string($haystack) || !$haystack) {
            throw new Exception('Could not generate a random string: empty or invalid haystack provided');
        }

        $haystackLength = strlen($haystack);
        if ($length > $haystackLength) {
            throw new Exception('Length cannot be greater than the length of the haystack. Length is ' . $length . ' and the length of the haystack is ' . $haystackLength);
        }

        $i = 0;
        while ($i < $length) {
            $index = mt_rand(0, $haystackLength - 1);

            $string .= $haystack[$index];

            $i++;
        }

        return $string;
    }

}
