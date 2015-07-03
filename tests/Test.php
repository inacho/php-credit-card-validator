<?php

use Inacho\CreditCard;

class Test extends PHPUnit_Framework_TestCase
{
    // card numbers of visa, mastercard, amex, dinersclub, discover and jcb taken from:
    // https://www.paypalobjects.com/en_US/vhelp/paypalmanager_help/credit_card_numbers.htm

    // Card numbers of visaelectron, maestro and dankort taken from:
    // http://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html

    protected $validCards = array(

        // Debit cards
        'visaelectron' => array(
            '4917300800000000',
        ),
        'maestro' => array(
            '6759649826438453',
            '6799990100000000019',
        ),
        'forbrugsforeningen' => array(
            '6007220000000004',
        ),
        'dankort' => array(
            '5019717010103742',
        ),

        // Credit cards
        'visa' => array(
            '4111111111111111',
            '4012888888881881',
            '4222222222222',
            '4462030000000000',
            '4484070000000000',
        ),
        'mastercard' => array(
            '5555555555554444',
            '5454545454545454',
            '2221000002222221',
        ),
        'amex' => array(
            '378282246310005',
            '371449635398431',
            '378734493671000', // American Express Corporate
        ),
        'dinersclub' => array(
            '30569309025904',
            '38520000023237',
            '36700102000000',
            '36148900647913',
        ),
        'discover' => array(
            '6011111111111117',
            '6011000990139424',
        ),
        'unionpay' => array(
            '6271136264806203568',
            '6236265930072952775',
            '6204679475679144515',
            '6216657720782466507',
        ),
        'jcb' => array(
            '3530111333300000',
            '3566002020360505',
        ),
    );

    public function testCardsTypes()
    {
        foreach ($this->validCards as $type => $numbers) {
            foreach ($numbers as $number) {
                $result = CreditCard::validCreditCard($number);

                $this->assertEquals(true, $result['valid']);
                $this->assertEquals($type, $result['type']);
            }
        }
    }

    public function testNumbers()
    {
        // Empty number
        $result = CreditCard::validCreditCard('');
        $this->assertEquals(false, $result['valid']);

        // Number with spaces
        $result = CreditCard::validCreditCard('       ');
        $this->assertEquals(false, $result['valid']);

        // Valid number
        $result = CreditCard::validCreditCard('4242424242424242');
        $this->assertEquals(true, $result['valid']);

        // Valid number with dashes
        $result = CreditCard::validCreditCard('4242-4242-4242-4242');
        $this->assertEquals(true, $result['valid']);

        // Valid number with spaces
        $result = CreditCard::validCreditCard('4242 4242 4242 4242');
        $this->assertEquals(true, $result['valid']);

        // More than 16 digits
        $result = CreditCard::validCreditCard('42424242424242424');
        $this->assertEquals(false, $result['valid']);

        // Less than 10 digits
        $result = CreditCard::validCreditCard('424242424');
        $this->assertEquals(false, $result['valid']);
    }

    public function testLuhn()
    {
        $result = CreditCard::validCreditCard('4242424242424241');
        $this->assertEquals(false, $result['valid']);
    }

    public function testDate()
    {
        // Invalid month
        $this->assertEquals(false, CreditCard::validDate(date('Y'), '13'));

        // Invalid year
        $this->assertEquals(false, CreditCard::validDate(date('Y'), '15'));

        // Integer values
        $this->assertEquals(true, CreditCard::validDate(intval(date('Y')), intval(date('m'))));

        // Not numbers
        $this->assertEquals(false, CreditCard::validDate('j201', 'd4'));

        // Past year, future month
        $timestamp = strtotime('-1 month');
        $this->assertEquals(false, CreditCard::validDate(date('Y', $timestamp) - 1, date('m', $timestamp)));

        // Current year, past month
        $timestamp = strtotime('-1 month');
        $this->assertEquals(false, CreditCard::validDate(date('Y', $timestamp), date('m', $timestamp)));

        // Current year, current month
        $this->assertEquals(true, CreditCard::validDate(date('Y'), date('m')));

        // Next year
        $timestamp = strtotime('+1 year');
        $this->assertEquals(true, CreditCard::validDate(date('Y', $timestamp), date('m', $timestamp)));
    }

    public function testCvc()
    {
        // Empty
        $this->assertEquals(false, CreditCard::validCvc('', ''));

        // Empty type
        $this->assertEquals(false, CreditCard::validCvc('123', ''));

        // Empty number
        $this->assertEquals(false, CreditCard::validCvc('', 'visa'));

        // Valid
        $this->assertEquals(true, CreditCard::validCvc('123', 'visa'));

        // Non digits
        $this->assertEquals(false, CreditCard::validCvc('12e', 'visa'));

        // Less than 3 digits
        $this->assertEquals(false, CreditCard::validCvc('12', 'visa'));

        // More than 3 digits
        $this->assertEquals(false, CreditCard::validCvc('1234', 'visa'));
    }
}
