<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class PaymentByToken extends Action
{
  public function call($secret_token)
  {
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('paymentByTokenAction.graphql', ['token' => $secret_token])->execute();
    $vars = [
      'payment' => $result->fetch('payment'),
      'payment_methods' => $result->fetch('paymentMethods'),
      'token' => $secret_token,
      'js_files' => ['/assets/online_payment.js']
    ];
    return $this->renderPage('online_payment_by_token.html.twig', $vars, false);
  }
}