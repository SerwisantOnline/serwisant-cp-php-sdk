<?php

namespace Serwisant\SerwisantCp;

use Adbar;
use Serwisant\SerwisantApi\Types\SchemaPublic;
use Silex;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtensions
{
  private $twig;
  private $app;
  private $translator;

  public function __construct(Environment $twig, Silex\Application $app)
  {
    $this->twig = $twig;
    $this->app = $app;
    $this->translator = $app['tr'];
  }

  public function call()
  {
    $this->twig->addFilter(new TwigFilter('has_value', function ($data, $path) {
      $data = new Adbar\Dot($data);
      return $data[$path];
    }));

    $this->twig->addFilter(new TwigFilter('checked', function ($data, $path) {
      $data = new Adbar\Dot($data);
      if ($data[$path] == '1') {
        return 'checked="checked"';
      } else {
        return '';
      }
    }));

    $this->twig->addFilter(new TwigFilter('has_error', function ($errors, $argument, $css_class = '', $index = null) {
      if ($index !== null) {
        $argument = str_replace('%i', $index, $argument);
      }
      $argument_errors = [];
      foreach ($errors as $error) {
        if ($error->argument === $argument) {
          $argument_errors[] = $this->t(['errors', $error->code]);
        }
      }
      if (count($argument_errors) > 0) {
        return 'class="' . $css_class . ' is-invalid" title="' . implode(', ', $argument_errors) . '"';
      } else {
        return 'class="' . $css_class . '"';
      }
    }));

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

    $this->twig->addFunction(new TwigFunction('repair_progress_bar', function ($percent) {
      if (100 === $percent) {
        $css_class = 'progress-bar-success';
      } else {
        $css_class = 'progress-bar-info';
      }
      $title = $this->t(['generic.progressed_about', ['percent' => $percent]]);
      return "
<div class='progress text-tooltip' data-percent='{$percent}' title='{$title}'>
<div class='progress-bar {$css_class}' role='progressbar' style='width: {$percent}%'>
<span class='sr-only'>{$percent}</span>
</div>
</div>";
    }));

    $this->twig->addFunction(new TwigFunction('custom_form_field_value', function (Environment $env, $field_value, $field_type) {
      switch ($field_type) {
        case SchemaPublic\CustomFieldType::PASSWORD:
          return '****************';
        case SchemaPublic\CustomFieldType::CHECKBOX:
          if ($field_value === '0') {
            return '<i class="glyphicon glyphicon-unchecked"></i>';
          } else {
            return '<i class="glyphicon glyphicon-check"></i>';
          }
        default:
          return twig_escape_filter($env, $field_value);
      }
    }, ['needs_environment' => true]));

    $this->twig->addFunction(new TwigFunction('repair_time_pending_badge', function ($days_from_start, $time_status) {
      switch ($time_status) {
        case SchemaPublic\OrderTimeStatus::WARNING:
          $class = 'label label-warning';
          break;
        case SchemaPublic\OrderTimeStatus::DELAYED:
          $class = 'label label-danger';
          break;
        default:
          $class = 'label label-success';
          break;
      }
      return "<span class='{$class}'>{$days_from_start}</span>";
    }));

    return $this->twig;
  }

  private function t(array $keys)
  {
    $args = array_merge([$this->app['locale']], $keys);
    return call_user_func_array([$this->translator, 't'], $args);
  }
}
