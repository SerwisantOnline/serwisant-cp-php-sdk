<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;

class Messages extends Action
{
  public function index()
  {
    $this->checkModuleActive();
    $messages = $this->apiCustomer()->customerQuery()->messages($this->getListLimit(), $this->request->get('page'), null, ['list' => true]);
    $variables = [
      'messages' => $messages
    ];
    return $this->renderPage('messages.html.twig', $variables);
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelCommunication) {
      $this->notFound();
    }
  }
}