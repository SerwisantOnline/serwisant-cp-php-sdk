<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Traits;

class RepairByToken extends Action
{
  public function call($rating_errors = [])
  {
    $result = $this->getData();
    $repair = $result->fetch('repair');

    $vars = [
      'pageTitle' => $repair->rma,
      'repair' => $repair,
      'configuration' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
      'form_params' => $this->request->request,
      'rating_errors' => $rating_errors,
    ];

    return $this->renderPage('repair_by_token.html.twig', $vars);
  }

  use Traits\TokenRating;
  // public function rate()

  private function getData()
  {
    return $this->apiPublic()->publicQuery()->newRequest()->setFile('repairByTokenAction.graphql', ['token' => (string)$this->token])->execute();
  }

  private function getRateable()
  {
    return $this->getData()->fetch('repair');
  }
}