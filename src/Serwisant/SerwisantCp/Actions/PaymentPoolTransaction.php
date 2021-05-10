<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class PaymentPoolTransaction extends Action
{
  public function call()
  {
    $transaction_id = $this->request->get('id');
    if (!$transaction_id) {
      $this->notFound();
    }

    $transaction = $this->api->publicQuery()->paymentTransaction($transaction_id);
    return $this->app->json($transaction, 200);
  }
}