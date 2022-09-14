<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class RepairByToken extends Action
{
  public function call()
  {
    $result = $this
      ->apiPublic()
      ->publicQuery()
      ->newRequest()
      ->setFile('repairByTokenAction.graphql', ['token' => (string)$this->token])
      ->execute();

    $vars = [
      'repair' => $result->fetch('repair'),
      'files' => array_chunk($result->fetch('repair')->files, 3),
      'configuration' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
    ];

    return $this->renderPage('repair_by_token.html.twig', $vars);
  }
}