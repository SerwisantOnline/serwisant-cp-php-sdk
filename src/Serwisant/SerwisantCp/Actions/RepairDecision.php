<?php

namespace Serwisant\SerwisantCp\Actions;

use Symfony\Component\HttpFoundation;

use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantCp\Action;

use Serwisant\SerwisantApi\Types\SchemaCustomer;

class RepairDecision extends Action
{
  public function accept($id)
  {
    return $this->makeDecision(SchemaCustomer\AcceptOrRejectRepairDecision::ACCEPT, $id);
  }

  public function acceptOffer($id, $offer_id)
  {
    return $this->makeDecision(SchemaCustomer\AcceptOrRejectRepairDecision::ACCEPT, $id, $offer_id);
  }

  public function reject($id)
  {
    return $this->makeDecision(SchemaCustomer\AcceptOrRejectRepairDecision::REJECT, $id);
  }

  private function makeDecision($decision, $id, $offer_id = null)
  {
    $this
      ->apiCustomer()
      ->customerMutation()
      ->acceptOrRejectRepair($id, $decision, $offer_id);

    $response = new HttpFoundation\Response('', 204);

    switch ($decision) {
      case SchemaCustomer\AcceptOrRejectRepairDecision::ACCEPT:
        $this->flashMessage($this->t('flashes.order_diagnosis_accepted'));
        return $response;
      case SchemaCustomer\AcceptOrRejectRepairDecision::REJECT:
        $this->flashMessage($this->t('flashes.order_diagnosis_rejected'));
        return $response;
      default:
        throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}
