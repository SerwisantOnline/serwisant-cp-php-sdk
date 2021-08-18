<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class Dashboard extends Action
{
  public function index()
  {
    $vars = [];
    return $this->renderPage('dashboard.html.twig', $vars);
  }
}