<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class RepairDetailsByToken extends Action
{
  public function call($secret_token)
  {
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('repairDetailsByTokenAction.graphql', ['token' => $secret_token])->execute();
    $vars = [
      'repair' => $result->fetch('repair'),
      'config' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
      'token' => $secret_token
    ];
    return $this->renderPage('repair_details_by_token.html.twig', $vars, false);
  }
}