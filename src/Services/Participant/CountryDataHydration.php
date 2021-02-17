<?php

namespace AllDigitalRewards\RewardStack\Services\Participant;

use League\ISO3166\ISO3166;

class CountryDataHydration
{
    /**
     * Country Code is the Alpha
     * Country is the ISO
     *
     * @param array $shipping
     * @return array
     */
    public function hydrateCountryInputData(array $shipping): array
    {
        $countryConfig = $this->getCountryConfigByAlphaCode($shipping);
        if (empty($countryConfig) === false) {
            $shipping['country'] = $countryConfig[0];
            $shipping['country_code'] = $countryConfig[1];
            return $shipping;
        } else {
            $countryConfig = $this->getCountryConfigByNumericCode($shipping);
            $shipping['country'] = $countryConfig[0] ?? '840';
            $shipping['country_code'] = $countryConfig[1] ?? 'US';
            return $shipping;
        }
    }

    /**
     * @param array $shipping
     * @return array|null
     */
    private function getCountryConfigByAlphaCode(array $shipping): ?array
    {
        try {
            $countryCodeLookup = new ISO3166();
            $country = $shipping['country'] ?? 'US';
            $data = $countryCodeLookup->alpha2((string)$country);
            if (empty($data) === false) {
                return [$data['numeric'], $data['alpha2']];
            }
            return null;
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param array $shipping
     * @return array|null
     */
    private function getCountryConfigByNumericCode(array $shipping): ?array
    {
        try {
            $countryCodeLookup = new ISO3166();
            $country = $shipping['country'] ?? '840';
            $data = $countryCodeLookup->numeric((string)$country);
            if (empty($data) === false) {
                return [$data['numeric'], $data['alpha2']];
            }
            return null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
