<?php

namespace Serwisant\SerwisantCp;

use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigGenericExtensions extends TwigExtensions
{
  public function call()
  {
    $this->twig->addFunction(new TwigFunction('path', function ($binding, $variables = []) {
      if (!array_key_exists('token', $variables) && isset($this->app['token']) && (string)$this->app['token'] !== '') {
        $variables['token'] = (string)$this->app['token'];
      }
      return $this->app['url_generator']->generate($binding, $variables);
    }));

    $this->twig->addFunction(new TwigFunction('paginator', function ($pages, $param_name = 'page') {
      $current_uri = parse_url($this->app['request']->getUri());

      $get_args = [];
      if (true === array_key_exists('query', $current_uri)) {
        parse_str($current_uri['query'], $get_args);
      }

      $current_page = array_key_exists($param_name, $get_args) ? $get_args[$param_name] : 1;

      $get_args[$param_name] = '__page__';
      $placeholder_path = $current_uri['path'] . '?' . http_build_query($get_args);

      $html = '';
      $html = $html . '<nav aria-label="Page navigation example">';
      $html = $html . '<ul class="pagination">';
      if ($current_page > 1) {
        $html = $html . '<li class="page-item"><a class="page-link" href="' . str_replace('__page__', ($current_page - 1), $placeholder_path) . '">' . $this->t(['twig_extensions', 'prev_page']) . '</a></li>';
      } else {
        $html = $html . '<li class="page-item disabled"><a class="page-link" href="#">' . $this->t(['twig_extensions', 'prev_page']) . '</a></li>';
      }
      for ($i = 0; $i < $pages; $i++) {
        $html = $html . '<li class="page-item"><a class="page-link" href="' . str_replace('__page__', ($i + 1), $placeholder_path) . '">' . ($i + 1) . '</a></li>';
      }
      if ($current_page < $pages) {
        $html = $html . '<li class="page-item"><a class="page-link" href="' . str_replace('__page__', ($current_page + 1), $placeholder_path) . '">' . $this->t(['twig_extensions', 'next_page']) . '</a></li>';
      } else {
        $html = $html . '<li class="page-item disabled"><a class="page-link" href="#">' . $this->t(['twig_extensions', 'next_page']) . '</a></li>';
      }
      $html = $html . '</ul>';
      $html = $html . '</nav>';

      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('t', function (...$keys) {
      return $this->t($keys);
    }));

    $this->twig->addFilter(new TwigFilter('format_currency', function ($number, $currency_code) {
      $symbol = $this->translator->codeToCurrencySymbol($currency_code);
      return number_format($number, 2, ',', '') . ' ' . $symbol;
    }));

    $this->twig->addFilter(new TwigFilter('format_datetime', function ($date_ISO8601) {
      $date = \DateTime::createFromFormat('Y-m-d\TH:i:sO', $date_ISO8601);
      $tz = $this->app['timezone'];
      $date->setTimezone(new \DateTimeZone($tz));
      return $date->format('Y-m-d H:i');
    }));

    $this->twig->addFunction(new TwigFunction('locale_ISO', function (...$keys) {
      return $this->app['locale_detector']->countryISO();
    }));

    $this->twig->addFunction(new TwigFunction('locale_flag', function (...$keys) {
      return $this->app['locale_detector']->flagSVG();
    }));

    $this->twig->addFunction(new TwigFunction('locale_VatPrefix', function (...$keys) {
      return $this->app['locale_detector']->vatPrefix();
    }));

    $this->twig->addFunction(new TwigFunction('locale_PhonePrefix', function (...$keys) {
      return $this->app['locale_detector']->phonePrefix();
    }));

    return $this->twig;
  }
}