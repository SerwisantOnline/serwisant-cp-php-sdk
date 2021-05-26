<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaCustomer\TicketsSort;

class Tickets extends Action
{
  public function index()
  {
    $this->checkModuleActive();
    $tickets = $this->apiCustomer()->customerQuery()->tickets($this->getListLimit(), $this->request->get('page'), null, TicketsSort::CREATED_AT, ['list' => true]);
    $variables = [
      'tickets' => $tickets
    ];
    return $this->renderPage('tickets.html.twig', $variables);
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelTickets) {
      $this->notFound();
    }
  }
}