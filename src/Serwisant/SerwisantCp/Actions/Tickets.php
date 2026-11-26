<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantApi\Types\SchemaCustomer;

class Tickets extends Action
{
  use Traits\Devices;
  use Traits\TicketGroups;

  /**
   * `addressID` radio value of "Point the address" option: address given as coordinates (`geoPoint.lat/lng`) pointed on
   * the map, geocoded by the API. Address IDs are HashIDs, so it can't collide with customer's address.
   */
  const ADDRESS_GPS = 'gps';

  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->requestParam('page', 1);
    $filter = new SchemaCustomer\TicketsFilter(['type' => SchemaCustomer\TicketsFilterType::ALL]);
    $sort = SchemaCustomer\TicketsSort::CREATED_AT;

    $tickets = $this
      ->apiCustomer()
      ->customerQuery()
      ->tickets($limit, $page, $filter, $sort, ['list' => true]);

    $variables = array_merge($this->ticketGroupsMenuVariables($this->fetchCustomerTicketGroups()), [
      'tickets' => $tickets,
      'pageTitle' => $this->t('tickets.title'),
    ]);
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
      'js_files' => ['ticket.js'],
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
    foreach ($result->fetch('ticketPriorities') as $entry) {
      $priorities_select_options[$entry->ID] = $entry->name;
    }

    /* @var $device SchemaCustomer\Device */
    $device = $this->getDevice();

    $addresses_radio_options = [];
    foreach ($result->fetch('viewer')->customer->addresses as $address) {
      $addresses_radio_options[$address->ID] = trim("{$address->postalCode} {$address->city}, {$address->street} {$address->building}");
    }
    // without customer's addresses the map is preselected, as in the public form
    $addresses_radio_options[self::ADDRESS_GPS] = $this->t('ticket_new', 'gps_address');
    $addresses_radio_options[''] = $this->t('ticket_new', 'other_address');
    $default_address = array_key_first($addresses_radio_options);

    $variables = $this->ticketGroupsFormVariables($result->fetch('ticketGroups'), $this->requestParam('group'));
    $variables += [
      'customFieldsDefinitions' => $result->fetch('ticketCustomFields'),
      'priorities_select_options' => $priorities_select_options,
      'addresses_radio_options' => $addresses_radio_options,
      'defaultAddress' => $default_address,
      'device' => $device,
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->requestParam('temporary_files')),
      'errors' => $errors,
      'js_files' => ['tickets_shared.js', 'tickets.js'],
      'pageTitle' => $this->t('ticket_new.title'),
    ];

    return $this->renderPage('ticket_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $helper = $this->formHelper();

    $ticket = $this->requestParam('ticket', []);
    $ticket['startAt'] = $helper->dateTimeToISO8601($ticket['startAt'] ?? '', $this->app['timezone']);
    if (array_key_exists('customFields', $ticket)) {
      $ticket['customFields'] = $helper->mapCustomFields($ticket['customFields']);
    }
    $ticket = $this->applyTicketGroup($this->lockTicketGroup($ticket), fn() => $this->fetchCustomerTicketGroups());
    if ($ticket === null) {
      return $this->new([new SchemaCustomer\Error(['argument' => 'ticket.group', 'code' => 'invalid', 'message' => 'Invalid ticket group'])]);
    }
    $ticket_input = new SchemaCustomer\TicketInput($ticket);

    $devices = [];
    if ($device = $this->getDevice()) {
      $devices[] = $device->ID;
    }

    if ($device && $device->address) {
      $address_input = new SchemaCustomer\AddressInput(array_merge($device->address->toArray(['ID', 'geoPoint']), ['type' => SchemaCustomer\AddressType::OTHER])); // always use original device address
    } elseif ($this->requestParam('addressID') === self::ADDRESS_GPS) {
      $geo_point = $this->geoPointInput($this->requestParam('geoPoint'));
      if ($geo_point === null) {
        return $this->new([new SchemaCustomer\Error(['argument' => 'geoPoint', 'code' => 'invalid', 'message' => 'Invalid coordinates'])]);
      }
      // coordinates only - any of street, building, postalCode, city would disable geocoding on the API side
      $address_input = new SchemaCustomer\AddressInput(['geoPoint' => $geo_point, 'type' => SchemaCustomer\AddressType::GPS]);
    } elseif ($this->requestParam('addressID')) {
      $address_input = new SchemaCustomer\AddressInput();
      foreach ($this->apiCustomer()->customerQuery()->viewer(['addresses' => true])->customer->addresses as $a) {
        if ($a->ID == $this->requestParam('addressID')) {
          $address_input = new SchemaCustomer\AddressInput($a->toArray(['ID']));
        }
      }
    } else {
      $address_input = new SchemaCustomer\AddressInput(array_merge($this->requestParam('address', []), ['type' => SchemaCustomer\AddressType::OTHER]));
    }

    $result = $this->apiCustomer()->customerMutation()->createTicket(
      $ticket_input,
      $helper->mapTemporaryFiles($this->requestParam('temporary_files')),
      $devices,
      $address_input
    );

    if ($result->errors) {
      $ticket_errors = $result->errors;
    } else {
      $ticket_errors = [];
    }

    if (count($ticket_errors) > 0) {
      $this->fallbackToOtherAddress($ticket_errors);
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

    $rating_input = new SchemaCustomer\RatingInput($this->requestParam('rating', []));

    $result = $this->apiCustomer()->customerMutation()->setRating($id, SchemaCustomer\RatingSubjectType::TICKET, $rating_input);

    if ($result->errors) {
      return $this->show($id, $result->errors);
    } else {
      return $this->redirectTo(['ticket', ['id' => $id]]);
    }
  }

  /**
   * When the API could not determine the address from coordinates pointed on the map (`address` / `geocoding_failed`,
   * or incomplete geocoding result, eg. `address.city` / `blank`), the form is displayed again with "Enter another
   * address" selected, as the error asks the customer to enter the address manually.
   *
   * @param SchemaCustomer\Error[] $errors
   */
  private function fallbackToOtherAddress(array $errors): void
  {
    if ($this->requestParam('addressID') !== self::ADDRESS_GPS) {
      return;
    }
    foreach ($errors as $error) {
      $argument = (string)$error->argument;
      if ($argument === 'address' || (str_starts_with($argument, 'address.') && !str_starts_with($argument, 'address.geoPoint'))) {
        $this->request->request->set('addressID', '');
        return;
      }
    }
  }

  /**
   * Coordinates pointed on the map, null if missing or invalid: API accepts any numeric string (decimal point only)
   * without range check.
   */
  private function geoPointInput($geo_point): ?SchemaCustomer\GeoPointInput
  {
    $lat = is_array($geo_point) ? ($geo_point['lat'] ?? null) : null;
    $lng = is_array($geo_point) ? ($geo_point['lng'] ?? null) : null;
    foreach ([[$lat, 90], [$lng, 180]] as [$value, $max]) {
      if (!is_string($value) || !preg_match('/^-?\d{1,3}(\.\d+)?$/', $value) || abs((float)$value) > $max) {
        return null;
      }
    }
    return new SchemaCustomer\GeoPointInput(['lat' => $lat, 'lng' => $lng]);
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