<?php

namespace Serwisant\SerwisantCp\Actions;

use Adbar\Dot;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaPublic;

class TicketsPublic extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newTicket.graphql')->execute();

    $variables = [
      'tickets' => $tickets,
      'pageTitle' => $this->t('tickets.title'),
    ];
    return $this->renderPage('tickets.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    if ($this->isAuthenticated()) {
      return $this->redirectTo('new_ticket');
    }

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newTicket.graphql')->execute();

    $priorities_select_options = [];
    foreach ($result->fetch('priorities') as $entry) {
      $priorities_select_options[$entry->ID] = $entry->name;
    }

    $variables = [
      'customFieldsDefinitions' => $result->fetch('ticketCustomFields'),
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'priorities_select_options' => $priorities_select_options,
      'addresses_radio_options' => [],
      'defaultAddress' => null,
      'device' => null,
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['tickets.js'],
      'pageTitle' => $this->t('ticket_new.title'),
      'formActionUrl' => $this->generateUrl('token_create_ticket', ['token' => $this->token->token()]),
    ];

    return $this->renderPage('ticket_new.html.twig', $variables);
  }

  public function create($device_uid)
  {
    $this->checkModuleActive();

    $helper = $this->formHelper();

    $applicant = new Dot($this->request->get('applicant', []));

    $applicant_input = new SchemaPublic\AnonymousApplicantInput([
      'deviceUid' => $device_uid,
      'phone' => new SchemaPublic\PhoneInput($applicant['phone']),
      'email' => $applicant['email'],
      'agreements' => $helper->mapAgreements($applicant['agreements']),
    ]);

    $ticket = $this->request->get('ticket', []);
    $ticket['startAt'] = $helper->dateTimeToISO8601($ticket['startAt'], $this->app['timezone']);
    if (array_key_exists('customFields', $ticket)) {
      $ticket['customFields'] = $helper->mapCustomFields($ticket['customFields']);
    }
    $ticket_input = new SchemaPublic\TicketInput($ticket);

    $address_input = new SchemaPublic\AddressInput(array_merge($this->request->get('address', []), ['type' => SchemaPublic\AddressType::OTHER]));

    $result = $this->apiPublic()->publicMutation()->createTicket(
      $applicant_input,
      $ticket_input,
      $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      [],
      $address_input
    );

    $ticket_errors = $result->errors;

    if (count($ticket_errors) > 0) {
      return $this->new(array_merge($ticket_errors));
    } else {
      return $this->redirectTo(['token', ['token' => $this->token->token()]], 'flashes.ticket_creation_successful');
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