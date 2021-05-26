<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairInput;


class Repairs extends Action
{
  public function index()
  {
    $this->checkModuleActive();
    $repairs = $this->apiCustomer()->customerQuery()->repairs($this->getListLimit(), $this->request->get('page'), null, RepairsSort::DATE_UPDATED, ['list' => true]);
    $variables = [
      'repairs' => $repairs
    ];
    return $this->renderPage('repairs.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();
    $filter = new RepairsFilter(['type' => RepairsFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->repairs(null, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      $this->notFound();
      return null;
    }
    $variables = [
      'repair' => $result->items[0],
    ];
    return $this->renderPage('repair.html.twig', $variables);
  }

  public function new($errors = [])
  {
    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newRepair.graphql')->execute();

    $dictionary_select_options = ['' => ''];
    foreach ($result->fetch('dictionaryEntries') as $entry) {
      $dictionary_select_options[$entry->ID] = $entry->name;
    }

    $variables = [
      'customFieldsDefinitions' => $result->fetch('orderCustomFields'),
      'dictionary_select_options' => $dictionary_select_options,
      'form_params' => $this->request->request,
      'errors' => $errors,
      'js_files' => ['/assets/repairs.js']
    ];
    return $this->renderPage('repair_new.html.twig', $variables);
  }

  public function create()
  {
    $repair = new RepairInput();
    $this->apiCustomer()->customerMutation()->createRepair($repair, [], []);
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelRepairs) {
      $this->notFound();
    }
  }
}