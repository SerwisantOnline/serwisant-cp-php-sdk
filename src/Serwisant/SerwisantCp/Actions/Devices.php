<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\DevicesFilterType;

class Devices extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);

    $devices = $this
      ->apiCustomer()
      ->customerQuery()
      ->devices($limit, $page, null, null, ['list' => true]);

    $variables = [
      'devices' => $devices,
      'pageTitle' => $this->t('devices.title'),
    ];
    return $this->renderPage('devices.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();

    $filter = new DevicesFilter(['type' => DevicesFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->devices(1, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $variables = [
      'device' => $result->items[0],
      'pageTitle' => $result->items[0]->number,
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