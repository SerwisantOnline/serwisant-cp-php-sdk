<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Traits;

class Dashboard extends Action
{
  use Traits\TicketGroups;

  public function index()
  {
    $this->checkModuleActive();

    $vars = [
      'js_files' => ['dashboard.js'],
      'pageTitle' => $this->t('dashboard.title'),
      'ticketGroupsMenu' => [],
      'ticketGroupsMenuOnly' => false,
    ];
    if ($this->getLayoutVars()['configuration']->panelTickets) {
      $vars = array_merge($vars, $this->ticketGroupsMenuVariables($this->fetchCustomerTicketGroups()));
    }
    return $this->renderPage('dashboard.html.twig', $vars);
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
  }
}