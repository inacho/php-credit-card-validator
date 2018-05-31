<?php

/**
 * Validates popular debit and credit cards numbers against regular expressions and Luhn algorithm.
 * Also validates the CVC and the expiration date.
 *
 * @author    Ignacio de Tomás <nacho@inacho.es>
 * @copyright 2014 Ignacio de Tomás (http://inacho.es)
 */

namespace Inacho;

class CreditCard
{
    protected static $cards = [
        // Debit cards must come first, since they have more specific patterns than their credit-card equivalents.

        'visaelectron' => [
            'type' => 'visaelectron',
            'pattern' => '/^4(026|17500|405|508|844|91[37])/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'maestro' => [
            'type' => 'maestro',
            'pattern' => '/^(5(018|0[23]|[68])|6(39|7))/',
            'length' => [12, 13, 14, 15, 16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'forbrugsforeningen' => [
            'type' => 'forbrugsforeningen',
            'pattern' => '/^600/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'dankort' => [
            'type' => 'dankort',
            'pattern' => '/^5019/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        // Credit cards
        'visa' => [
            'type' => 'visa',
            'pattern' => '/^4/',
            'length' => [13, 16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'mastercard' => [
            'type' => 'mastercard',
            'pattern' => '/^(5[0-5]|2[2-7])/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'amex' => [
            'type' => 'amex',
            'pattern' => '/^3[47]/',
            'format' => '/(\d{1,4})(\d{1,6})?(\d{1,5})?/',
            'length' => [15],
            'cvcLength' => [3, 4],
            'luhn' => true,
        ],
        'dinersclub' => [
            'type' => 'dinersclub',
            'pattern' => '/^3[0689]/',
            'length' => [14],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'discover' => [
            'type' => 'discover',
            'pattern' => '/^6([045]|22)/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'unionpay' => [
            'type' => 'unionpay',
            'pattern' => '/^(62|88)/',
            'length' => [16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn' => false,
        ],
        'jcb' => [
            'type' => 'jcb',
            'pattern' => '/^35/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        'elo' => [
            'type' => 'elo',
            'pattern' => '/^((50670[7-8])|(506715)|(50671[7-9])|(50672[0-1])|(50672[4-9])|(50673[0-3])|(506739)|(50674[0-8])|(50675[0-3])|(50677[4-8])|(50900[0-9])|(50901[3-9])|(50902[0-9])|(50903[1-9])|(50904[0-9])|(50905[0-9])|(50906[0-4])|(50906[6-9])|(50907[0-2])|(50907[4-5])|(636368)|(636297)|(504175)|(438935)|(40117[8-9])|(45763[1-2])|(457393)|(431274)|(50907[6-9])|(50908[0-9])|(627780))/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
    ];

    public static function validCreditCard($number, $type = null)
    {
        $ret = array(
            'valid' => false,
            'number' => '',
            'type' => '',
        );

        // Strip non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        if (empty($type)) {
            $type = self::creditCardType($number);
        }

        if (array_key_exists($type, self::$cards) && self::validCard($number, $type)) {
            return array(
                'valid' => true,
                'number' => $number,
                'type' => $type,
            );
        }

        return $ret;
    }

    public static function validCvc($cvc, $type)
    {
        return (ctype_digit($cvc) && array_key_exists($type, self::$cards) && self::validCvcLength($cvc, $type));
    }

    public static function validDate($year, $month)
    {
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);

        if (! preg_match('/^20\d\d$/', $year)) {
            return false;
        }

        if (! preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
            return false;
        }

        // past date
        if ($year < date('Y') || $year == date('Y') && $month < date('m')) {
            return false;
        }

        return true;
    }

    // PROTECTED
    // ---------------------------------------------------------

    protected static function creditCardType($number)
    {
        foreach (self::$cards as $type => $card) {
            if (preg_match($card['pattern'], $number)) {
                return $type;
            }
        }

        return '';
    }

    protected static function validCard($number, $type)
    {
        return (self::validPattern($number, $type) && self::validLength($number, $type) && self::validLuhn($number, $type));
    }

    protected static function validPattern($number, $type)
    {
        return preg_match(self::$cards[$type]['pattern'], $number);
    }

    protected static function validLength($number, $type)
    {
        foreach (self::$cards[$type]['length'] as $length) {
            if (strlen($number) == $length) {
                return true;
            }
        }

        return false;
    }

    protected static function validCvcLength($cvc, $type)
    {
        foreach (self::$cards[$type]['cvcLength'] as $length) {
            if (strlen($cvc) == $length) {
                return true;
            }
        }

        return false;
    }

    protected static function validLuhn($number, $type)
    {
        if (! self::$cards[$type]['luhn']) {
            return true;
        } else {
            return self::luhnCheck($number);
        }
    }

    protected static function luhnCheck($number)
    {
        $checksum = 0;
        $length = strlen($number);
        for ($i = (2 - ($length % 2)); $i <= $length; $i += 2) {
            $checksum += (int)($number{$i - 1});
        }

        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i = ($length % 2) + 1; $i < $length; $i += 2) {
            $digit = (int)($number{$i - 1}) * 2;
            if ($digit < 10) {
                $checksum += $digit;
            } else {
                $checksum += ($digit - 9);
            }
        }

        if (($checksum % 10) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
