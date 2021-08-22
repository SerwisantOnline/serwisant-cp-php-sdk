<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class Dashboard extends Action
{
  public function index()
  {
    $vars = [
      'pageTitle' => $this->t('dashboard.title'),
    ];
    return $this->renderPage('dashboard.html.twig', $vars);
  }
}