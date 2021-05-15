<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class PaymentPoolTransaction extends Action
{
  public function call($secret_token)
  {
    $transaction_id = $this->request->get('id');
    if (!$transaction_id) {
      $this->notFound();
      return null;
    }

    $transaction = $this->apiPublic()->publicQuery()->paymentTransaction($transaction_id);
    return $this->app->json($transaction);
  }
}