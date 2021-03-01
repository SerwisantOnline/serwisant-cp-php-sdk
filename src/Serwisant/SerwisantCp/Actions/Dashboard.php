<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class Dashboard extends Action
{
  public function dashboard()
  {
    return $this->renderPage('dashboard.html.twig');
  }
}