<?php

namespace Serwisant\SerwisantCp\Actions;

use Symfony\Component\HttpFoundation;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaPublic;

class RepairDecisionByToken extends Action
{
  public function accept($secret_token)
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT, $secret_token);
  }

  public function acceptOffer($secret_token, $offer_id)
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT, $secret_token, $offer_id);
  }

  public function reject($secret_token)
  {
    return $this->makeDecision(SchemaPublic\AcceptOrRejectRepairDecision::REJECT, $secret_token);
  }

  private function makeDecision($decision, $secret_token, $offer_id = null)
  {
    $this->apiPublic()->publicMutation()->acceptOrRejectRepair($secret_token, new SchemaPublic\AcceptOrRejectRepairDecision($decision), $offer_id);

    $response = new HttpFoundation\Response('', 204);

    switch ($decision) {
      case SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT:
        $this->flashMessage($this->t('flashes.order_diagnosis_accepted'));
        return $response;
      case SchemaPublic\AcceptOrRejectRepairDecision::REJECT:
        $this->flashMessage($this->t('flashes.order_diagnosis_rejected'));
        return $response;
      default:
        return $this->notFound();
    }
  }
}
