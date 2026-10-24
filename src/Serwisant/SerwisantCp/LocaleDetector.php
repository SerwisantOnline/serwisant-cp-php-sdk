<?php

namespace Serwisant\SerwisantCp;

use Symfony\Component\HttpFoundation;
use CodeZero\BrowserLocale\BrowserLocale;
use Symfony\Component\Intl\Countries;
use libphonenumber\PhoneNumberUtil;

class LocaleDetector
{
  protected string $default_locale;
  protected string $locale;

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

    if (!Countries::exists(explode('_', $locale)[1])) {
      if (!Countries::exists(explode('_', $this->default_locale)[1])) {
        throw new Exception("There is no country for default locale: {$this->default_locale}");
      }
      $locale = explode('_', $locale)[0] . "_" . explode('_', $this->default_locale)[1];
    }

    $this->locale = $locale;

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
    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $this->countryISO());
    return count($timezones) > 0 ? $timezones[0] : 'UTC';
  }

  /**
   * Flaga kraju jako emoji w SVG
   *
   * @return string
   */
  public function flagSVG(): string
  {
    $flag = implode('', array_map(fn($letter) => mb_chr(0x1F1E6 + ord($letter) - ord('A')), str_split($this->countryISO())));
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 24'><text x='0' y='20' font-size='20'>{$flag}</text></svg>";
    return 'data:image/svg+xml;base64, ' . base64_encode($svg);
  }

  /**
   * @return false|string
   */
  public function phonePrefix()
  {
    $calling_code = PhoneNumberUtil::getInstance()->getCountryCodeForRegion($this->countryISO());
    return $calling_code > 0 ? "+{$calling_code}" : false;
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