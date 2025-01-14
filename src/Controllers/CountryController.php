<?php

namespace NickDeKruijk\Webshop\Controllers;

use App\Http\Controllers\Controller;
use Cache;
use File;
use GeoIp2\Database\Reader;

class CountryController extends Controller
{
    /**
     * Return current country isoCode based on IP address
     *
     * @return string
     */
    public static function geoCountry()
    {
        $ip = request()->ip();
        if ($get = Cache::get('geoip_' . $ip)) {
            return $get;
        }
        $reader = new Reader(base_path('vendor') . '/bobey/geoip2-geolite2-composer/GeoIP2/GeoLite2-City.mmdb');
        try {
            $record = $reader->city($ip);
            Cache::put('geoip_' . $ip, $record->country->isoCode, 3600);
        } catch (\Throwable $th) {
            return null;
        }
        return $record->country->isoCode;
    }

    /**
     * Return all countries from mledoze/countries package
     *
     * @param string $translation Return specific translation for country names
     * @return array
     */
    public static function countries($translation = null)
    {
        $countryFile = base_path('vendor') . '/mledoze/countries/countries.json';
        abort_if(!File::exists($countryFile), 500, 'Country file not found, is mledoze/countries package loaded?');
        $countries = [];
        foreach (json_decode(File::get($countryFile)) as $country) {
            if ($translation) {
                $countries[$country->cca2] = $country->translations->$translation->common;
            } else {
                $countries[$country->cca2] = $country->name->common;
            }
        }
        asort($countries);
        return $countries;
    }
}
