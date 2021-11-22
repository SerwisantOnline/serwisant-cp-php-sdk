<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation;
use CodeZero\BrowserLocale\BrowserLocale;
use PragmaRX\Countries\Package\Countries;

class LocaleDetector
{
  private string $locale;
  private $country;

  public function setRequest(HttpFoundation\Request $request)
  {
    $locale = $this->getLocaleString($request);
    $countries = new Countries();

    $this->locale = $locale;
    $this->country = $countries->where('cca2', $this->countryISO())->first();

    return $this;
  }

  protected function getLocaleString(HttpFoundation\Request $request): string
  {
    $locale = (new BrowserLocale($request->headers->get("accept-language")))->getLocale();
    if ($locale) {
      $language = $locale->language;
      $country = $locale->country;
      if (!$country) {
        $country = strtoupper($language);
      }
    } else {
      $language = 'pl';
      $country = 'PL';
    }

    return "{$language}_{$country}";
  }

  public function locale(): string
  {
    return $this->locale;
  }

  public function timeZone(): string
  {
    return $this->country->hydrate('timezones')->timezones->first()->zone_name;
  }

  public function flagSVG(): string
  {
    return 'data:image/svg+xml;base64, ' . base64_encode($this->country->get('flag.svg'));
  }

  public function phonePrefix()
  {
    return $this->country->get('calling_codes')->first();
  }

  public function vatPrefix(): ?string
  {
    switch ($this->countryISO()) {
      case 'GR':
        return 'EL';
      case 'AT':
      case 'BE':
      case 'BG':
      case 'HR':
      case 'CY':
      case 'CZ':
      case 'DK':
      case 'EE':
      case 'FI':
      case 'DE':
      case 'HU':
      case 'IE':
      case 'IT':
      case 'LV':
      case 'LT':
      case 'LU':
      case 'MT':
      case 'NL':
      case 'PL':
      case 'PT':
      case 'RO':
      case 'SK':
      case 'SI':
      case 'ES':
      case 'SE':
        return $this->countryISO();
      case 'FR':
      case 'MC':
        return 'FR';
      default:
        return null;
    }
  }

  public function countryISO(): string
  {
    return explode('_', $this->locale)[1];
  }


}