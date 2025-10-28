<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation;
use CodeZero\BrowserLocale\BrowserLocale;

class LocaleDetector
{
  protected string $default_locale;
  protected string $locale;
  protected $country;

  public function __construct(HttpFoundation\Request $request, string $default_locale = 'pl_PL')
  {
    $this->default_locale = $default_locale;
    $this->setRequest($request);
  }

  private function setRequest(HttpFoundation\Request $request)
  {
    $locale = $this->getLocaleString($request);
    if (is_null($locale)) {
      $locale = $this->default_locale;
    }

    $countries = CountryDatabase::getInstance();
    $country = $countries->where('cca2', explode('_', $locale)[1])->first();
    if (count($country) <= 0) {
      $country = $countries->where('cca2', explode('_', $this->default_locale)[1])->first();
      if (count($country) <= 0) {
        throw new Exception("There is no country for default locale: {$this->default_locale}");
      }
      $locale = explode('_', $locale)[0] . "_" . explode('_', $this->default_locale)[1];
    }

    $this->locale = $locale;
    $this->country = $country;

    return $this;
  }

  private function getLocaleString(HttpFoundation\Request $request): ?string
  {
    if ($request->headers->has('Accept-Language')) {
      $locale = (new BrowserLocale($request->headers->get("Accept-Language")))->getLocale();
      if ($locale) {
        $language = $locale->language;
        $country = $locale->country;
        if (!$country) {
          $country = strtoupper($language);
        }
        return "{$language}_{$country}";
      }
    }
    return null;
  }

  /**
   * @return string
   */
  public function locale(): string
  {
    return $this->locale;
  }

  /**
   * @return string
   */
  public function language(): string
  {
    return explode('_', $this->locale)[0];
  }

  /**
   * @return string
   */
  public function countryISO(): string
  {
    return explode('_', $this->locale)[1];
  }

  /**
   * @return string
   */
  public function timeZone(): string
  {
    return $this->country->hydrate('timezones')->timezones->first()->zone_name;
  }

  /**
   * @return string
   */
  public function flagSVG(): string
  {
    return 'data:image/svg+xml;base64, ' . base64_encode($this->country->get('flag.svg'));
  }

  /**
   * @return false|string
   */
  public function phonePrefix()
  {
    $prefix = $this->country->get('calling_codes')->first();
    if (strlen($prefix) >= 5) {
      $prefix = substr($prefix, 0, 2);
    }
    return $prefix;
  }

  /**
   * @return string|null
   */
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
}