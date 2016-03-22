<?php

/**
 * Validates popular debit and credit cards numbers against regular expressions and Luhn algorithm.
 * Also validates the CVC and the expiration date.
 *
 * @author    Ignacio de Tomás <nacho@inacho.es>
 * @copyright 2014 Ignacio de Tomás (http://inacho.es)
 */

namespace Inacho;

use Inacho\Exception\CreditCardException;
use Inacho\Exception\CreditCardLengthException;
use Inacho\Exception\CreditCardLuhnException;
use Inacho\Exception\CreditCardPatternException;
use Inacho\Exception\CreditCardTypeException;

class CreditCard
{
    const TYPE_AMEX = 'amex';
    const TYPE_DANKORT = 'dankort';
    const TYPE_DINERS_CLUB = 'dinersclub';
    const TYPE_DISCOVER = 'discover';
    const TYPE_FORBRUGSFORENINGEN = 'forbrugsforeningen';
    const TYPE_JCB = 'jcb';
    const TYPE_MAESTRO = 'maestro';
    const TYPE_MASTERCARD = 'mastercard';
    const TYPE_UNION_PAY = 'unionpay';
    const TYPE_VISA = 'visa';
    const TYPE_VISA_ELECTRON = 'visaelectron';

    protected static $cards = array(
        // Debit cards must come first, since they have more specific patterns than their credit-card equivalents.

        self::TYPE_VISA_ELECTRON => array(
            'type' => self::TYPE_VISA_ELECTRON,
            'pattern' => '/^4(026|17500|405|508|844|91[37])/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_MAESTRO => array(
            'type' => self::TYPE_MAESTRO,
            'pattern' => '/^(5(018|0[23]|[68])|6(05|39|7))/',
            'length' => array(12, 13, 14, 15, 16, 17, 18, 19),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_FORBRUGSFORENINGEN => array(
            'type' => self::TYPE_FORBRUGSFORENINGEN,
            'pattern' => '/^600/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_DANKORT => array(
            'type' => self::TYPE_DANKORT,
            'pattern' => '/^5019/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        // Credit cards
        self::TYPE_VISA => array(
            'type' => self::TYPE_VISA,
            'pattern' => '/^4/',
            'length' => array(13, 16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_MASTERCARD => array(
            'type' => self::TYPE_MASTERCARD,
            'pattern' => '/^(5[0-5]|2[2-7])/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_AMEX => array(
            'type' => self::TYPE_AMEX,
            'pattern' => '/^3[47]/',
            'format' => '/(\d{1,4})(\d{1,6})?(\d{1,5})?/',
            'length' => array(15),
            'cvcLength' => array(3, 4),
            'luhn' => true,
        ),
        self::TYPE_DINERS_CLUB => array(
            'type' => self::TYPE_DINERS_CLUB,
            'pattern' => '/^3[0689]/',
            'length' => array(14),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_DISCOVER => array(
            'type' => self::TYPE_DISCOVER,
            'pattern' => '/^6([045]|22)/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
        self::TYPE_UNION_PAY => array(
            'type' => self::TYPE_UNION_PAY,
            'pattern' => '/^(62|88)/',
            'length' => array(16, 17, 18, 19),
            'cvcLength' => array(3),
            'luhn' => false,
        ),
        self::TYPE_JCB => array(
            'type' => self::TYPE_JCB,
            'pattern' => '/^35/',
            'length' => array(16),
            'cvcLength' => array(3),
            'luhn' => true,
        ),
    );

    /**
     * @param string $number
     * @param string|string[]|null $allowTypes By default, all card types are allowed
     * @return array
     */
    public static function validCreditCard($number, $allowTypes = null)
    {
        $ret = array(
            'valid' => false,
            'number' => '',
            'type' => '',
        );

        // Strip non-numeric characters
        $number = preg_replace('/\D/', '', $number);

        if (is_string($allowTypes)) {
            $type = $allowTypes;
        } else {
            $type = self::creditCardType($number);
        }

        if (empty($type) || is_array($allowTypes) && !in_array($type, $allowTypes)) {
            return $ret;
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

    /**
     * @param string $number
     * @param string|string[]|null $allowTypes By default, all card types are allowed
     * @throws CreditCardException
     */
    public static function checkCreditCard($number, $allowTypes = null)
    {
        // Strip non-numeric characters
        $number = preg_replace('/\D/', '', $number);

        if (is_string($allowTypes)) {
            $type = $allowTypes;
        } else {
            $type = self::creditCardType($number);
        }

        if (empty($type) || (is_array($allowTypes) && !in_array($type, $allowTypes))) {
            throw new CreditCardTypeException(sprintf('Type "%s" card is not allowed', $type));
        }

        if (!self::validPattern($number, $type)) {
            throw new CreditCardPatternException(sprintf('Wrong "%s" card pattern', $number));
        }

        if (!self::validLength($number, $type)) {
            throw new CreditCardLengthException(sprintf('Incorrect "%s" card length', $number));
        }

        if (!self::validLuhn($number, $type)) {
            throw new CreditCardLuhnException(sprintf('Invalid card number: "%s". Checksum is wrong', $number));
        }
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
        for ($i=(2-(strlen($number) % 2)); $i<=strlen($number); $i+=2) {
            $checksum += (int) ($number{$i-1});
        }

        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i=(strlen($number)% 2) + 1; $i<strlen($number); $i+=2) {
            $digit = (int) ($number{$i-1}) * 2;
            if ($digit < 10) {
                $checksum += $digit;
            } else {
                $checksum += ($digit-9);
            }
        }

        if (($checksum % 10) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
