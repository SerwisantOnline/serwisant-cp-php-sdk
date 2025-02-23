<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class TicketByToken extends Action
{
  public function call()
  {
    $result = $this
      ->apiPublic()
      ->publicQuery()
      ->newRequest()
      ->setFile('ticketByTokenAction.graphql', ['token' => (string)$this->token])
      ->execute();

    $ticket = $result->fetch('ticket');

    $vars = [
      'pageTitle' => $ticket->number,
      'ticket' => $ticket,
      'configuration' => $result->fetch('configuration'),
      'currency' => $result->fetch('configuration')->currency,
      'js_files' => ['ticket_by_token.js'],
    ];

    return $this->renderPage('ticket_by_token.html.twig', $vars);
  }
}