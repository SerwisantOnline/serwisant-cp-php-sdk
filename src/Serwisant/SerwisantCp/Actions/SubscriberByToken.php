<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class SubscriberByToken extends Action
{
  public function call()
  {
    return $this->renderPage('subscriber_by_token.html.twig', ['js_files' => ['subscriber_by_token.js']]);
  }
}