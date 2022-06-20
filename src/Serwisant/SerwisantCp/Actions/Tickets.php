<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\PrintType;

class Tickets extends Action
{
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

    $addresses_radio_options = [];
    foreach ($result->fetch('viewer')->customer->addresses as $address) {
      $addresses_radio_options[$address->ID] = trim("{$address->postalCode} {$address->city}, {$address->street} {$address->building}");
    }
    $addresses_radio_options[''] = $this->t('ticket_new', 'other_address');

    $variables = [
      'customFieldsDefinitions' => $result->fetch('orderCustomFields'),
      'priorities_select_options' => $priorities_select_options,
      'addresses_radio_options' => $addresses_radio_options,
      'defaultAddress' => ($result->fetch('viewer')->customer->address ? $result->fetch('viewer')->customer->address->ID : null),

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
    if (array_key_exists('customFields', $ticket)) {
      $ticket['customFields'] = $helper->mapCustomFields($ticket['customFields']);
    }
    $ticket['startAt'] = $helper->dateTimeToISO8601($ticket['startAt'], $this->app['timezone']);

    $ticket_input = new TicketInput($ticket);
    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createTIcket($ticket_input, $helper->mapTemporaryFiles($this->request->get('temporary_files')));

    if ($result->errors) {
      return $this->new($result->errors);
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