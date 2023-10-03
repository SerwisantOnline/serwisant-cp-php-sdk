<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilter;
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

  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelDevices) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}