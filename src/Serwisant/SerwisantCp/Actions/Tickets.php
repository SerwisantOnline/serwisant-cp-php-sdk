<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantApi\Types\SchemaCustomer;

class Tickets extends Action
{
  use Traits\Devices;

  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);
    $filter = new SchemaCustomer\TicketsFilter(['type' => SchemaCustomer\TicketsFilterType::ALL]);
    $sort = SchemaCustomer\TicketsSort::CREATED_AT;

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

  public function show($id, $rating_errors = [])
  {
    $this->checkModuleActive();

    $ticket = $this->fetchTicket($id);

    $variables = [
      'ticket' => $ticket,
      'pageTitle' => $ticket->number,
      'form_params' => $this->request->request,
      'rating_errors' => $rating_errors,
    ];

    return $this->renderPage('ticket.html.twig', $variables);
  }

  public function print($id)
  {
    $result = $this->apiCustomer()->customerMutation()->print(SchemaCustomer\PrintType::TICKET, $id);
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

    /* @var $device SchemaCustomer\Device */
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
    $ticket_input = new SchemaCustomer\TicketInput($ticket);

    $devices = [];
    if ($device = $this->getDevice()) {
      $devices[] = $device->ID;
    }

    if ($device && $device->address) {
      $address_input = new SchemaCustomer\AddressInput(array_merge($device->address->toArray(['ID', 'geoPoint']), ['type' => SchemaCustomer\AddressType::OTHER])); // always use original device address
    } elseif ($this->request->get('addressID')) {
      $address_input = new SchemaCustomer\AddressInput();
      foreach ($this->apiCustomer()->customerQuery()->viewer(['addresses' => true])->customer->addresses as $a) {
        if ($a->ID == $this->request->get('addressID')) {
          $address_input = new SchemaCustomer\AddressInput($a->toArray(['ID']));
        }
      }
    } else {
      $address_input = new SchemaCustomer\AddressInput(array_merge($this->request->get('address', []), ['type' => SchemaCustomer\AddressType::OTHER]));
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

  public function rate($id)
  {
    $this->checkModuleActive();

    $ticket = $this->fetchTicket($id);

    if (false === $ticket->isRateable) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $rating_input = new SchemaCustomer\RatingInput($this->request->get('rating', []));

    $result = $this->apiCustomer()->customerMutation()->setRating($id, SchemaCustomer\RatingSubjectType::TICKET, $rating_input);

    if ($result->errors) {
      return $this->show($id, $result->errors);
    } else {
      return $this->redirectTo(['ticket', ['id' => $id]]);
    }
  }

  private function fetchTicket($id)
  {
    $filter = new SchemaCustomer\TicketsFilter(['type' => SchemaCustomer\TicketsFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->tickets(1, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
    return $result->items[0];
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelTickets) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}