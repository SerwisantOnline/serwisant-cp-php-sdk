<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressType;

use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DeviceInput;

use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsSort;

use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsSort;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class Devices extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $devices = $this
      ->apiCustomer()
      ->customerQuery()
      ->devices($this->getListLimit(), $this->request->get('page', 1), null, null, ['list' => true]);

    $variables = [
      'devices' => $devices,
      'pageTitle' => $this->t('devices.title'),
    ];

    return $this->renderPage('devices.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();

    $devicesFilter = new DevicesFilter(['type' => DevicesFilterType::ID, 'ID' => $id]);
    $devices = $this->apiCustomer()->customerQuery()->devices(1, null, $devicesFilter, null, ['single' => true]);

    if (count($devices->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $repairsFilter = new RepairsFilter(['type' => RepairsFilterType::DEVICE, 'ID' => $id]);
    $repairs = $this->apiCustomer()->customerQuery()->repairs(10, $this->request->get('repairs_page', 1), $repairsFilter, RepairsSort::DATE_UPDATED, ['list' => true]);

    $ticketsFilter = new TicketsFilter(['type' => TicketsFilterType::DEVICE, 'ID' => $id]);
    $tickets = $this->apiCustomer()->customerQuery()->tickets(10, $this->request->get('tickets_page', 1), $ticketsFilter, TicketsSort::CREATED_AT, ['list' => true]);

    $variables = [
      'device' => $devices->items[0],
      'repairs' => $repairs,
      'tickets' => $tickets,
      'pageTitle' => $devices->items[0]->number,
    ];

    return $this->renderPage('device.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive(true);

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newDevice.graphql')->execute();

    $dictionary_select_options = [];
    foreach ($result->fetch('dictionaryEntries') as $entry) {
      $dictionary_select_options[$entry->ID] = $entry->name;
    }

    $variables = [
      'customFieldsDefinitions' => $result->fetch('deviceCustomFields'),
      'dictionary_select_options' => $dictionary_select_options,

      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['devices_new.js'],
      'pageTitle' => $this->t('repair_new.title'),
    ];
    return $this->renderPage('device_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive(true);

    $helper = $this->formHelper();

    $device = $this->request->get('device', []);

    $device_input = new DeviceInput($device);
    if (array_key_exists('customFields', $device)) {
      $device_input->customFields = $helper->mapCustomFields($device['customFields']);
    }
    $temporary_files = $helper->mapTemporaryFiles($this->request->get('temporary_files'));
    if (count($temporary_files) > 0) {
      $device_input->copyOfSaleDocumentTemporaryFile = $temporary_files[0];
    }

    $address_input = new AddressInput($this->request->get('address', []));
    $address_input->type = AddressType::HOME;

    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createDevice($device_input, $address_input);

    if ($result->errors) {
      $device_errors = $result->errors;
    } else {
      $device_errors = [];
    }

    if (count($device_errors)) {
      return $this->new($device_errors);
    } else {
      return $this->redirectTo('devices', 'flashes.device_registration_successful');
    }
  }

  private function checkModuleActive($check_registration = false)
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelDevices) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
    if ($check_registration && false === $this->getLayoutVars()['configuration']->panelDevicesRegistration) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}