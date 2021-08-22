<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class RepairByToken extends Action
{
  public function call($secret_token)
  {
    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('repairByTokenAction.graphql', ['token' => $secret_token])->execute();
    $vars = [
      'repair' => $result->fetch('repair'),
      'configuration' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
      'token' => $secret_token
    ];
    return $this->renderPage('repair_by_token.html.twig', $vars);
  }
}