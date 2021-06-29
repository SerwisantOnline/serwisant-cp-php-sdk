<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\MessageInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\MessagesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\MessagesFilterType;


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

  public function show($id)
  {
    $this->checkModuleActive();

    $filter = new MessagesFilter(['type' => MessagesFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->messages(null, null, $filter, ['single' => true]);
    if (count($result->items) !== 1) {
      $this->notFound();
      return null;
    }

    $thread = $result->items[0];

    $variables = [
      'thread' => $thread,
    ];

    $this->apiCustomer()->customerMutation()->markMessageRead($thread->ID);

    return $this->renderPage('message.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $variables = [
      'form_params' => $this->request->request,
      'errors' => $errors,
    ];

    return $this->renderPage('message_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $message = $this->request->get('message', []);

    $message_input = new MessageInput($message);
    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createMessage($message_input);

    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      return $this->redirectTo('messages', 'flashes.message_creation_successful');
    }
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelCommunication) {
      $this->notFound();
    }
  }
}