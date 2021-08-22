<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantApi\Types\SchemaCustomer\MessageInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\MessagesFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\MessagesFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\MessagesSort;

use Serwisant\SerwisantCp\Action;

class Messages extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);
    $filter = new MessagesFilter(['type' => MessagesFilterType::ALL]);
    $sort = MessagesSort::DATE_UPDATED;

    $messages = $this
      ->apiCustomer()
      ->customerQuery()
      ->messages($limit, $page, $filter, $sort, ['list' => true]);

    $variables = [
      'messages' => $messages,
      'pageTitle' => $this->t('messages.title'),
    ];

    return $this->renderPage('messages.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();

    $variables = [
      'thread' => $this->getThread($id),
      'pageTitle' => $this->t('message.title'),
    ];

    $this->apiCustomer()->customerMutation()->markMessageRead($id);

    return $this->renderPage('message.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $variables = [
      'form_params' => $this->request->request,
      'errors' => $errors,
      'pageTitle' => $this->t('message_new.title'),
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

  public function newReply($id, $errors = [])
  {
    $this->checkModuleActive();

    $variables = [
      'form_params' => $this->request->request,
      'errors' => $errors,
      'thread' => $this->getThread($id),
    ];

    return $this->renderPage('message_new_reply.html.twig', $variables);
  }

  public function createReply($id)
  {
    $this->checkModuleActive();

    $message = $this->request->get('message', []);

    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createMessageReply($id, $message['content']);

    if ($result->errors) {
      return $this->newReply($id, $result->errors);
    } else {
      return $this->redirectTo(['message', ['id' => $id]], 'flashes.message_creation_successful');
    }
  }

  private function getThread($id)
  {
    $filter = new MessagesFilter(['type' => MessagesFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->messages(null, null, $filter, null, ['single' => true]);

    if (count($result->items) !== 1) {
      $this->notFound();
      return null;
    }

    return $result->items[0];
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelCommunication) {
      $this->notFound();
    }
  }
}