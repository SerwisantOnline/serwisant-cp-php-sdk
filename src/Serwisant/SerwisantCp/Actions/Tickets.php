<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ActionFormHelpers;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketInput;

class Tickets extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $tickets = $this
      ->apiCustomer()
      ->customerQuery()
      ->tickets($this->getListLimit(), $this->request->get('page'), null, TicketsSort::CREATED_AT, ['list' => true]);

    $variables = [
      'tickets' => $tickets
    ];
    return $this->renderPage('tickets.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $helper = new ActionFormHelpers();

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newTicket.graphql')->execute();

    $priorities_select_options = ['' => ''];
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
      'form_params' => $this->request->request,
      'temporary_files' => $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['/assets/tickets.js']
    ];

    return $this->renderPage('ticket_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $helper = new ActionFormHelpers();

    $ticket = $this->request->get('ticket', []);
    $ticket['customFields'] = $helper->mapCustomFields($ticket['customFields']);
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
    if (false === $this->getLayoutVars()['configuration']->caPanelTickets) {
      $this->notFound();
    }
  }
}