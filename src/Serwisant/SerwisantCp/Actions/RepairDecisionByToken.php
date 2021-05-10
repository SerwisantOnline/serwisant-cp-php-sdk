<?php

namespace Serwisant\SerwisantCp\Actions;

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

    switch ($decision) {
      case SchemaPublic\AcceptOrRejectRepairDecision::ACCEPT:
        return $this->redirectTo(['token', ['token' => $secret_token]], 'flashes.order_diagnosis_accepted');
      case SchemaPublic\AcceptOrRejectRepairDecision::REJECT:
        return $this->redirectTo(['token', ['token' => $secret_token]], 'flashes.order_diagnosis_rejected');
      default:
        $this->notFound();
    }
  }
}
