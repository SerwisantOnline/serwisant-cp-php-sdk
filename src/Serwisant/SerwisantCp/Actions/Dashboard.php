<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class Dashboard extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $vars = ['pageTitle' => $this->t('dashboard.title')];
    return $this->renderPage('dashboard.html.twig', $vars);
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
  }
}