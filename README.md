# PHP Credit Card Validator

[![Build Status](https://travis-ci.org/freelancehunt/php-credit-card-validator.svg?branch=master)](https://travis-ci.org/freelancehunt/php-credit-card-validator) 
[![codecov](https://codecov.io/gh/freelancehunt/php-credit-card-validator/branch/master/graph/badge.svg)](https://codecov.io/gh/freelancehunt/php-credit-card-validator) 
[![License](https://img.shields.io/github/license/freelancehunt/php-credit-card-validator.svg)](https://coveralls.io/github/freelancehunt/php-credit-card-validator?branch=master) 

Validates popular debit and credit cards numbers against regular expressions and Luhn algorithm.
Also validates the CVC and the expiration date.

## Installation

Require the package in `composer.json`

```json
"require": {
    "freelancehunt/php-credit-card-validator": "1.*"
},
```
## Usage

### Validate a card number knowing the type:

```php
$card = CreditCard::validCreditCard('5500005555555559', 'mastercard');
print_r($card);
```

Output:

```
Array
(
    [valid] => 1
    [number] => 5500005555555559
    [type] => mastercard
)
```

### Validate a card number and return the type:

```php
$card = CreditCard::validCreditCard('371449635398431');
print_r($card);
```

Output:

```
Array
(
    [valid] => 1
    [number] => 371449635398431
    [type] => amex
)
```

### Validate the CVC

```php
$validCvc = CreditCard::validCvc('234', 'visa');
var_dump($validCvc);
```

Output:

```
bool(true)
```

### Validate the expiration date

```php
$validDate = CreditCard::validDate('2013', '07'); // past date
var_dump($validDate);
```

Output:

```
bool(false)
```

## Tests

Execute the following command to run the unit tests:

    vendor/bin/phpunit
