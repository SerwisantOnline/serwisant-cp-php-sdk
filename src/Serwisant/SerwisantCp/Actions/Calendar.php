<?php

// @todo: this requires implementation on API and panel sides.

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\DateRangeInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\ScheduleDatesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilterType;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Token;
use Silex;
use Symfony\Component\HttpFoundation\Request;

class Calendar extends Action
{
  public function ticketDates()
  {
    $date_range = new DateRangeInput(['from' => $this->request->get('from', ''), 'to' => $this->request->get('to', '')]);
    $filter = new TicketsFilter(['type' => TicketsFilterType::SCHEDULED_ON, 'dateRange' => $date_range]);

    $tickets = $this->apiCustomer()->customerQuery()->tickets(null, 1, $filter, null, ['calendar' => true]);

    $results = [];
    foreach ($tickets->items as $ticket) {
      $row = [
        'title' => $ticket->number,
        'start' => $ticket->status->scheduledOn,
        'end'  => $ticket->status->scheduledTo,
        'allDay' => false,
        'url' => $this->generateUrl('ticket', ['token' => (string)$this->app['token'], 'id' => $ticket->ID])
      ];
      $results[] = $row;
    }

    return $this->app->json($results);
  }

  public function scheduleDates()
  {
    $date_range = new DateRangeInput(['from' => $this->request->get('from', ''), 'to' => $this->request->get('to', '')]);
    $filter = new ScheduleDatesFilter(['dateRange' => $date_range]);

    $calendar_dates = $this->apiCustomer()->customerQuery()->scheduleDates($filter);

    $results = [];
    foreach ($calendar_dates as $calendar_date) {
      if (!$calendar_date->ticket) {
        $row = [
          'start' => $calendar_date->date,
          'allDay' => true,
        ];
        if ($calendar_date->schedule->concern == 'DEVICE') {
          $row['title'] = "{$calendar_date->schedule->device->displayName}: {$calendar_date->schedule->title}";
          $row['url'] = $this->generateUrl('device', ['token' => (string)$this->app['token'], 'id' => $calendar_date->schedule->device->ID]);
        } else {
          $row['title'] = $calendar_date->schedule->title;
        }
        $results[] = $row;
      }
    }
    return $this->app->json($results);
  }
}