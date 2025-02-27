<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\PrintType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\Device;

class Tickets extends Action
{
  use Traits\Devices;

  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);
    $filter = new TicketsFilter(['type' => TicketsFilterType::ALL]);
    $sort = TicketsSort::CREATED_AT;

    $tickets = $this
      ->apiCustomer()
      ->customerQuery()
      ->tickets($limit, $page, $filter, $sort, ['list' => true]);

    $variables = [
      'tickets' => $tickets,
      'pageTitle' => $this->t('tickets.title'),
    ];
    return $this->renderPage('tickets.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();

    $filter = new TicketsFilter(['type' => RepairsFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->tickets(1, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $variables = [
      'ticket' => $result->items[0],
      'pageTitle' => $result->items[0]->number,
    ];

    return $this->renderPage('ticket.html.twig', $variables);
  }

  public function print($id)
  {
    $result = $this->apiCustomer()->customerMutation()->print(PrintType::TICKET, $id);
    return $this->app->redirect($result->temporaryFile->url);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newTicket.graphql')->execute();

    $priorities_select_options = [];
    foreach ($result->fetch('priorities') as $entry) {
      $priorities_select_options[$entry->ID] = $entry->name;
    }

    /* @var $device Device */
    $device = $this->getDevice();

    $addresses_radio_options = [];
    foreach ($result->fetch('viewer')->customer->addresses as $address) {
      $addresses_radio_options[$address->ID] = trim("{$address->postalCode} {$address->city}, {$address->street} {$address->building}");
    }
    if (count($addresses_radio_options) > 0) {
      $addresses_radio_options[''] = $this->t('ticket_new', 'other_address');
    }
    $default_address = array_key_first($addresses_radio_options);

    $variables = [
      'customFieldsDefinitions' => $result->fetch('ticketCustomFields'),
      'priorities_select_options' => $priorities_select_options,
      'addresses_radio_options' => $addresses_radio_options,
      'defaultAddress' => $default_address,
      'device' => $device,
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['tickets.js'],
      'pageTitle' => $this->t('ticket_new.title'),
    ];

    return $this->renderPage('ticket_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $helper = $this->formHelper();

    $ticket = $this->request->get('ticket', []);
    $ticket['startAt'] = $helper->dateTimeToISO8601($ticket['startAt'], $this->app['timezone']);
    if (array_key_exists('customFields', $ticket)) {
      $ticket['customFields'] = $helper->mapCustomFields($ticket['customFields']);
    }
    $ticket_input = new TicketInput($ticket);

    $devices = [];
    if ($device = $this->getDevice()) {
      $devices[] = $device->ID;
    }

    if ($device && $device->address) {
      $address_input = new AddressInput(array_merge($device->address->toArray(['ID', 'geoPoint']), ['type' => AddressType::OTHER])); // always use original device address
    } elseif ($this->request->get('addressID')) {
      $address_input = new AddressInput();
      foreach ($this->apiCustomer()->customerQuery()->viewer(['addresses' => true])->customer->addresses as $a) {
        if ($a->ID == $this->request->get('addressID')) {
          $address_input = new AddressInput($a->toArray(['ID']));
        }
      }
    } else {
      $address_input = new AddressInput(array_merge($this->request->get('address', []), ['type' => AddressType::OTHER]));
    }

    $result = $this->apiCustomer()->customerMutation()->createTicket(
      $ticket_input,
      $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      $devices,
      $address_input
    );

    if ($result->errors) {
      $ticket_errors = $result->errors;
    } else {
      $ticket_errors = [];
    }

    if (count($ticket_errors) > 0) {
      return $this->new(array_merge($ticket_errors));
    } else {
      return $this->redirectTo('tickets', 'flashes.ticket_creation_successful');
    }
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelTickets) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}