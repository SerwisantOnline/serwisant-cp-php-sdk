<?php

namespace Serwisant\SerwisantCp\Actions;

use Symfony\Component\HttpFoundation;

use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantCp\Action;

use Serwisant\SerwisantApi\Types\SchemaPublic;

class RepairDecisionByToken extends Action
{
  public function accept()
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT, (string)$this->token);
  }

  public function acceptOffer($offer_id)
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT, (string)$this->token, $offer_id);
  }

  public function reject()
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::REJECT, (string)$this->token);
  }

  private function makeDecision($decision, $slug, $offer_id = null)
  {
    $this
      ->apiPublic()
      ->publicMutation()
      ->acceptOrRejectRepair($slug, $decision, $offer_id);

    $response = new HttpFoundation\Response('', 204);

    switch ($decision) {
      case SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT:
        $this->flashMessage($this->t('flashes.order_diagnosis_accepted'));
        return $response;
      case SchemaPublic\AcceptOrRejectRepairDecision::REJECT:
        $this->flashMessage($this->t('flashes.order_diagnosis_rejected'));
        return $response;
      default:
        throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}
