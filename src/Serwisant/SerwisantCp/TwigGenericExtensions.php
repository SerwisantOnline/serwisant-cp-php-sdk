<?php

namespace Serwisant\SerwisantCp;

use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigGenericExtensions extends TwigExtensions
{
  public function call()
  {
    $this->twig->addFunction(new TwigFunction('t', function (...$keys) {
      return $this->t($keys);
    }));

    $this->twig->addFilter(new TwigFilter('format_currency', function ($number, $currency_code = null) {
      if (!$currency_code) {
        $symbol = $this->translator->localeToCurrencySymbol($this->app['locale']);
      } else {
        $symbol = $this->translator->codeToCurrencySymbol($currency_code);
      }
      return number_format($number, 2, ',', '') . ' ' . $symbol;
    }));

    $this->twig->addFilter(new TwigFilter('format_datetime', function ($date_ISO8601, $tz = null) {
      $date = \DateTime::createFromFormat(\DateTime::ISO8601, $date_ISO8601);
      if (!$tz) {
        $tz = $this->app['timezone'];
      }
      $date->setTimezone(new \DateTimeZone($tz));
      return $date->format('Y-m-d H:i');
    }));

    return $this->twig;
  }
}