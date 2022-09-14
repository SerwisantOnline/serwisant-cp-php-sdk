<?php

namespace Serwisant\SerwisantCp;

use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

use Serwisant\SerwisantApi\Types\SchemaPublic\CustomFieldType;
use Serwisant\SerwisantApi\Types\SchemaPublic\OrderTimeStatus;
use Serwisant\SerwisantApi\Types\SchemaPublic\RepairState;

use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketState;

class TwigSerwisantExtensions extends TwigExtensions
{
  public function call()
  {
    $this->twig->addFilter(new TwigFilter('group_files', function ($files) {
      return array_map(function ($a) {
        return array_pad($a, 3, null);
      }, array_chunk($files, 3));
    }));

    $this->twig->addFunction(new TwigFunction('repair_progress_bar', function ($percent) {
      if (100 === $percent) {
        $css_class = 'progress-bar-success';
      } else {
        $css_class = 'progress-bar-info';
      }
      $title = $this->t(['twig_extensions.progressed_about', ['percent' => $percent]]);
      $html = "
<div class='progress text-tooltip' data-percent='{$percent}' title='{$title}'>
<div class='progress-bar {$css_class}' role='progressbar' style='width: {$percent}%'>
<span class='sr-only'>{$percent}</span>
</div>
</div>";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('custom_form_field_value', function (Environment $env, $field_value, $field_type) {
      switch ($field_type) {
        case CustomFieldType::PASSWORD:
          $html = '****************';
          break;

        case CustomFieldType::CHECKBOX:
          if ($field_value === '0') {
            $html = '<i class="glyphicon glyphicon-unchecked"></i>';
          } else {
            $html = '<i class="glyphicon glyphicon-check"></i>';
          }
          break;

        default:
          $html = twig_escape_filter($env, $field_value);
          break;
      }

      return new \Twig\Markup($html, 'UTF-8');
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
      $html = "<span class='{$class}'>{$days_from_start}</span>";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('customer_agreement_class', function ($customer_agreement) {
      if ($customer_agreement->visibleBusiness && $customer_agreement->visiblePersonal) {
        return '';
      } elseif ($customer_agreement->visiblePersonal) {
        return 'personal_container';
      } elseif ($customer_agreement->visibleBusiness) {
        return 'business_container undisplayed';
      } else {
        return '';
      }
    }));

    $this->twig->addFunction(new TwigFunction('repair_label', function ($repair) {
      $map = [
        'warning' => [RepairState::WAITING_FOR_DELIVERY],
        'success' => [RepairState::CONFIRMED, RepairState::PASSED_FOR_RETURN, RepairState::CLOSED, RepairState::SCRAPPED],
        'danger' => [RepairState::NOT_ACCEPTED],
        'primary' => [RepairState::DIAGNOSIS, RepairState::IN_PROGRESS, RepairState::UNDER_TESTING],
        'info' => [RepairState::REQ_CUSTOMER_ACCEPT, RepairState::WAITING_FOR_PARTS, RepairState::WAITING_FOR_COLLECTION]
      ];

      $color = 'secondary';
      foreach ($map as $c => $s) {
        if (in_array($repair->status->status, $s)) {
          $color = $c;
          break;
        }
      }

      $status_name = $this->t(['order_status', $repair->status->status]);
      $html = "<span class=\"badge rounded-pill bg-{$color}\">{$status_name}</span>";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('ticket_label', function ($ticket) {
      switch ($ticket->status->status) {
        case TicketState::NEW:
          $color = 'danger';
          break;
        case TicketState::ASSIGNED:
          $color = 'warning';
          break;
        case TicketState::ON_THE_WAY:
        case TicketState::IN_PROGRESS:
          $color = 'info';
          break;
        case TicketState::RESOLVED:
          $color = 'success';
          break;
        default:
          $color = 'secondary';
          break;
      }

      $status_name = $this->t(['ticket_status', $ticket->status->status]);
      $html = "<span class=\"badge rounded-pill bg-{$color}\">{$status_name}</span>";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    return $this->twig;
  }
}