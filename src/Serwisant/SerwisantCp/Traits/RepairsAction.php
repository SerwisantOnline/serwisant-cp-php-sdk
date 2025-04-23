<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairTransportType;
use Serwisant\SerwisantCp\ExceptionNotFound;

trait RepairsAction
{
  /**
   * @return void
   * @throws ExceptionNotFound
   */
  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelRepairs) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }

  /**
   * @return \Serwisant\SerwisantApi\GraphqlRequest
   * @throws \Serwisant\SerwisantApi\Exception
   * @throws \Serwisant\SerwisantApi\ExceptionAccessDenied
   * @throws \Serwisant\SerwisantApi\ExceptionNotFound
   */
  private function actionQuery()
  {
    return $this->apiPublic()->publicQuery()->newRequest()->setFile('repairsAction.graphql')->execute();
  }

  /**
   * @param $dictionary_entries
   * @return array
   */
  private function dictionarySelectOptions($dictionary_entries)
  {
    $dictionary_select_options = [];
    foreach ($dictionary_entries as $entry) {
      $dictionary_select_options[$entry->ID] = $entry->name;
    }
    return $dictionary_select_options;
  }

  /**
   * @return array
   */
  private function transportRadioOptions()
  {
    $transport_radio_options = [RepairTransportType::PARCEL => $this->t('transport_types.PARCEL')];
    if ($this->getLayoutVars()['configuration']->personalTransportEnabled) {
      $transport_radio_options[RepairTransportType::PERSONAL] = $this->t('transport_types.PERSONAL');
    }
    if ($this->getLayoutVars()['configuration']->internalTransportEnabled) {
      $transport_radio_options[RepairTransportType::INTERNAL] = $this->t('transport_types.INTERNAL');
    }
    return $transport_radio_options;
  }
}