<?php

use League\ISO3166\ISO3166;
use PHPUnit\Framework\TestCase;

class CountryCodeTest extends TestCase
{
    public function testCountryCodeReturnsUSForCountryCode840()
    {
        $countryCodeLookup = new ISO3166();
        $data = $countryCodeLookup->numeric('840');
        $this->assertSame('US', $data['alpha2']);
    }

    public function testCountryCodeReturns840ForCountryNameUS()
    {
        $countryCodeLookup = new ISO3166();
        $data = $countryCodeLookup->alpha2('US');
        $this->assertSame('840', $data['numeric']);
    }

    public function testSetsCountryCodeToUS()
    {
        $shipping = [
            'country'=> 840,
            'country_code'=> 'CA',
        ];
        $countryCodeLookup = new ISO3166();
        $country = $shipping['country'] ?? '840';
        $data = $countryCodeLookup->numeric((string)$country);
        $shipping['country_code'] = $data['alpha2'] ?? 'US';

        $this->assertSame('US', $shipping['country_code']);
    }

    public function testSetsCountryCodeToAnguilla()
    {
        $shipping = [
            'country'=> 660,
            'country_code'=> 'US',
        ];
        $countryCodeLookup = new ISO3166();
        $country = $shipping['country'] ?? '840';
        $data = $countryCodeLookup->numeric((string)$country);
        $shipping['country_code'] = $data['alpha2'];

        $this->assertSame('AI', $shipping['country_code']);
    }

    public function testSetsCountryCodeToUSWithBothArgsAccurate()
    {
        $shipping = [
            'country'=> 840,
            'country_code'=> 'US',
        ];
        $countryCodeLookup = new ISO3166();
        $country = $shipping['country'] ?? '840';
        $data = $countryCodeLookup->numeric((string)$country);
        $shipping['country_code'] = $data['alpha2'];

        $this->assertSame('US', $shipping['country_code']);
    }
}
