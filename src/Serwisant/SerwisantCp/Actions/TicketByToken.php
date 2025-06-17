<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Traits;

class TicketByToken extends Action
{
  public function call($rating_errors = [])
  {
    $result = $this->getData();
    $ticket = $result->fetch('ticket');

    $vars = [
      'pageTitle' => $ticket->number,
      'ticket' => $ticket,
      'configuration' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
      'js_files' => ['ticket_by_token.js'],
      'form_params' => $this->request->request,
      'rating_errors' => $rating_errors,
    ];

    return $this->renderPage('ticket_by_token.html.twig', $vars);
  }

  use Traits\TokenRating;
  // public function rate()

  private function getData()
  {
    return $this->apiPublic()->publicQuery()->newRequest()->setFile('ticketByTokenAction.graphql', ['token' => (string)$this->token])->execute();
  }

  private function getRateable()
  {
    return $this->getData()->fetch('ticket');
  }
}