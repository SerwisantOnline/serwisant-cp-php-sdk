<?php

namespace Serwisant\SerwisantCp\Actions;

use Adbar\Dot;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantApi\Types\SchemaPublic;

class TicketsPublic extends Action
{
  use Traits\Devices;

  public function new($errors = [])
  {
    $this->checkModuleActive();

    if ($this->isAuthenticated()) {
      if ($this->request->get('device')) {
        return $this->redirectTo('new_ticket', null, ['device' => $this->request->get('device')]);
      } else {
        return $this->redirectTo('new_ticket');
      }
    }

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newTicket.graphql')->execute();

    $priorities_select_options = [];
    foreach ($result->fetch('priorities') as $entry) {
      $priorities_select_options[$entry->ID] = $entry->name;
    }

    /* @var $device SchemaPublic\Device */
    $device = $this->getDevicePublic();

    /* @var $viewer SchemaPublic\Viewer */
    $viewer = $result->fetch('viewer');

    $variables = [
      'customFieldsDefinitions' => $result->fetch('ticketCustomFields'),
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'priorities_select_options' => $priorities_select_options,
      'serviceSupplier' => $viewer->ticketsServiceSupplier,
      'device' => $device,
      'showAddress' => $this->request->get('showAddress'),
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['tickets_public.js'],
      'pageTitle' => $this->t('ticket_new.title'),
    ];

    return $this->renderPage('ticket_public_new.html.twig', $variables);
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

    $device = $this->getDevicePublic();

    $geo_point = new SchemaPublic\GeoPointInput($this->request->get('geoPoint', []));
    $show_address = $this->request->get('showAddress', false);
    if ($device && $device->address) {
      $address_input = new SchemaPublic\AddressInput(array_merge($device->address->toArray(), ['type' => SchemaPublic\AddressType::OTHER])); // always use original device address
    } elseif (!$show_address && $geo_point->lat && $geo_point->lng) {
      $address_input = new SchemaPublic\AddressInput(['geoPoint' => $geo_point, 'type' => SchemaPublic\AddressType::GPS]); // fill only GPS coords if given, address will be geocoded on backend
    } else {
      $address_input = new SchemaPublic\AddressInput(array_merge($this->request->get('address', []), ['type' => SchemaPublic\AddressType::OTHER])); // pass given address
    }

    $result = $this->apiPublic()->publicMutation()->createTicket(
      $applicant_input,
      $ticket_input,
      $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      ($device ? $device->ID : null),
      $address_input
    );

    $ticket_errors = $result->errors;

    if (is_array($ticket_errors) && count($ticket_errors) > 0) {
      return $this->new(array_merge($ticket_errors));
    } else {
      $token = $result->ticket->secretToken->token;
      return $this->redirectTo(['token', ['token' => $token]], 'flashes.ticket_creation_successful');
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