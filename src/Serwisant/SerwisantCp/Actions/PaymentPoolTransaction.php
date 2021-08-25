<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

class PaymentPoolTransaction extends Action
{
  public function call()
  {
    $transaction_id = $this->request->get('id');

    if (!$transaction_id) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $transaction = $this->apiPublic()->publicQuery()->paymentTransaction($transaction_id);
    return $this->app->json($transaction);
  }
}