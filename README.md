Exchange extension for Nette framework
-------
[![Build Status](https://travis-ci.org/h4kuna/exchange-nette.svg?branch=master)](https://travis-ci.org/h4kuna/exchange-nette)
[![Latest stable](https://img.shields.io/packagist/v/h4kuna/exchange-nette.svg)](https://packagist.org/packages/h4kuna/exchange-nette)
[![Downloads this Month](https://img.shields.io/packagist/dm/h4kuna/exchange-nette.svg)](https://packagist.org/packages/h4kuna/exchange-nette)

This library is extension for Nette Framework and for this [Exchange](//github.com/h4kuna/exchange).

## Instalation
Simple via composer 
```sh
$ composer require h4kuna/exchange-nette
```

## Registation
```neon
extensions:
    exchangeExtension: h4kuna\Exchange\DI\ExchangeExtension
```
Extension is ready to use other configuration are optionally. Default is defined three currencies CZK, EUR and USD. Currencies has any default format.

## Configuration

Format options for currency read [h4kuna/number-format](//github.com/h4kuna/number-format)

```neon
exchangeExtension:
    currencies:
            czk:
                decimals: 3
                decimalPoint: '.'
                thousandsSeparator: ','
                zeroIsEmpty: TRUE
                emptyValue: '-'
                zeroClear: TRUE
                intOnly: NULL
                mask: '1U'
                showUnit: FALSE 
                nbsp: TRUE
                unit: Kč
                
            usd:
                unit: '$'
            gbp: [] # use default format 
    
    session: TRUE # default disabled, save only to cookie
    vat: 21 # default disabled
    strict: FALSE # default enabled, download only defined currencies
    defaultFormat: [] # how format currency if format is not defined
    tempDir: %tempDir% # path for cache 
    managerParameter: currency # is parameter for query, cookie and session if is available
    filters: # extension define two filter for latte, you can rename
        currency: currency
        vat: vat
```

## Latte
Now we have two new filters.
```latte
{=100|currency}
{=100|vat}
```

## Request
Create url with parameter currency and change value.
```url
/?currency=USD
```
