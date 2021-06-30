<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ActionFormHelpers;

use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairInput;

class Repairs extends Action
{
  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);
    $filter = new RepairsFilter(['type' => RepairsFilterType::ALL]);
    $sort = RepairsSort::DATE_UPDATED;

    $repairs = $this
      ->apiCustomer()
      ->customerQuery()
      ->repairs($limit, $page, $filter, $sort, ['list' => true]);

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
    $this->checkModuleActive();

    $helper = new ActionFormHelpers();

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newRepair.graphql')->execute();

    $dictionary_select_options = ['' => ''];
    foreach ($result->fetch('dictionaryEntries') as $entry) {
      $dictionary_select_options[$entry->ID] = $entry->name;
    }

    $variables = [
      'customFieldsDefinitions' => $result->fetch('orderCustomFields'),
      'dictionary_select_options' => $dictionary_select_options,
      'form_params' => $this->request->request,
      'temporary_files' => $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['/assets/repairs.js']
    ];

    return $this->renderPage('repair_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $helper = new ActionFormHelpers();

    $repair = $this->request->get('repair', []);
    $repair['customFields'] = $helper->mapCustomFields($repair['customFields']);
    $repair['warranty'] = (array_key_exists('warranty', $repair) && $repair['warranty'] == '1');

    $repair_input = new RepairInput($repair);
    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createRepair($repair_input, [], $helper->mapTemporaryFiles($this->request->get('temporary_files')));

    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      return $this->redirectTo('repairs', 'flashes.repair_creation_successful');
    }
  }

  private function checkModuleActive()
  {
    if (false === $this->getLayoutVars()['configuration']->caPanelRepairs) {
      $this->notFound();
    }
  }
}