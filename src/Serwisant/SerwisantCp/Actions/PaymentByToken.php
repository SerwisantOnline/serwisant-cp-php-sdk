<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class PaymentByToken extends Action
{
  public function call()
  {
    $result = $this
      ->apiPublic()
      ->publicQuery()
      ->newRequest()
      ->setFile('paymentByTokenAction.graphql', ['token' => (string)$this->token])
      ->execute();

    $vars = [
      'payment' => $result->fetch('payment'),
      'payment_methods' => $result->fetch('paymentMethods'),
      'js_files' => ['online_payment.js']
    ];
    return $this->renderPage('online_payment_by_token.html.twig', $vars);
  }
}