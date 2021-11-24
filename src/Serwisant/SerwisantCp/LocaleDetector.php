<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation;
use CodeZero\BrowserLocale\BrowserLocale;
use PragmaRX\Countries\Package\Countries;

class LocaleDetector
{
  private string $default_locale;
  private string $locale;

  private $country;

  public function __construct(string $default_locale = 'pl_PL')
  {
    $this->default_locale = $default_locale;
  }

  public function setRequest(HttpFoundation\Request $request)
  {
    $locale = $this->getLocaleString($request);
    if (is_null($locale)) {
      return $this->setDefaults();
    }
    $this->locale = $locale;

    $country = (new Countries())->where('cca2', $this->countryISO())->first();
    if (count($country) <= 0) {
      return $this->setDefaults();
    }
    $this->country = $country;

    return $this;
  }

  private function setDefaults(): LocaleDetector
  {
    $this->locale = $this->default_locale;
    $this->country = (new Countries())->where('cca2', explode('_', $this->default_locale)[1])->first();
    return $this;
  }

  protected function getLocaleString(HttpFoundation\Request $request): ?string
  {
    $locale = (new BrowserLocale($request->headers->get("accept-language")))->getLocale();
    if ($locale) {
      $language = $locale->language;
      $country = $locale->country;
      if (!$country) {
        $country = strtoupper($language);
      }
      return "{$language}_{$country}";
    }
    return null;
  }

  public function locale(): string
  {
    return $this->locale;
  }

  public function timeZone(): string
  {
    try {
      return $this->country->hydrate('timezones')->timezones->first()->zone_name;
    } catch (\Exception $e) {

    }
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