<?php

namespace Serwisant\SerwisantCp;

use Twig\Environment;
use Twig\TwigFunction;
use Serwisant\SerwisantApi\Types\SchemaPublic\CustomFieldType;
use Serwisant\SerwisantApi\Types\SchemaPublic\OrderTimeStatus;

class TwigSerwisantExtensions extends TwigExtensions
{
  public function call()
  {
    $this->twig->addFunction(new TwigFunction('repair_progress_bar', function ($percent) {
      if (100 === $percent) {
        $css_class = 'progress-bar-success';
      } else {
        $css_class = 'progress-bar-info';
      }
      $title = $this->t(['twig_extensions.progressed_about', ['percent' => $percent]]);
      return "
<div class='progress text-tooltip' data-percent='{$percent}' title='{$title}'>
<div class='progress-bar {$css_class}' role='progressbar' style='width: {$percent}%'>
<span class='sr-only'>{$percent}</span>
</div>
</div>";
    }));

    $this->twig->addFunction(new TwigFunction('custom_form_field_value', function (Environment $env, $field_value, $field_type) {
      switch ($field_type) {
        case CustomFieldType::PASSWORD:
          return '****************';
        case CustomFieldType::CHECKBOX:
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
        case OrderTimeStatus::WARNING:
          $class = 'label label-warning';
          break;
        case OrderTimeStatus::DELAYED:
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
}