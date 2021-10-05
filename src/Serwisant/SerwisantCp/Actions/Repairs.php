<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilter;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsFilterType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairsSort;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\AddressUpdateInput;
use Serwisant\SerwisantApi\Types\SchemaCustomer\PrintType;
use Serwisant\SerwisantApi\Types\SchemaCustomer\RepairTransportType;

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
      'repairs' => $repairs,
      'pageTitle' => $this->t('repairs.title'),
    ];

    return $this->renderPage('repairs.html.twig', $variables);
  }

  public function show($id)
  {
    $this->checkModuleActive();

    $filter = new RepairsFilter(['type' => RepairsFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->repairs(null, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
    $variables = [
      'repair' => $result->items[0],
      'pageTitle' => $result->items[0]->rma,
    ];

    return $this->renderPage('repair.html.twig', $variables);
  }

  public function print($id, $type)
  {
    $this->checkModuleActive();

    switch ($type) {
      case 'intro':
        $print_type = PrintType::REPAIR_INTRO;
        break;
      case 'summary':
        $print_type = PrintType::REPAIR_SUMMARY;
        break;
      default:
        throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $result = $this->apiCustomer()->customerMutation()->print($print_type, $id);
    return $this->app->redirect($result->temporaryFile->url);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newRepair.graphql')->execute();

    $dictionary_select_options = [];
    foreach ($result->fetch('dictionaryEntries') as $entry) {
      $dictionary_select_options[$entry->ID] = $entry->name;
    }

    $addresses_radio_options = [];
    foreach ($result->fetch('viewer')->customer->addresses as $address) {
      $addresses_radio_options[$address->ID] = trim("{$address->postalCode} {$address->city}, {$address->street} {$address->building}");
    }

    $transport_radio_options = [RepairTransportType::PARCEL => $this->t('transport_types.PARCEL')];

    if ($this->getLayoutVars()['configuration']->personalTransportEnabled) {
      $transport_radio_options[RepairTransportType::PERSONAL] = $this->t('transport_types.PERSONAL');
    }
    if ($this->getLayoutVars()['configuration']->internalTransportEnabled) {
      $transport_radio_options[RepairTransportType::INTERNAL] = $this->t('transport_types.INTERNAL');
    }

    $variables = [
      'customFieldsDefinitions' => $result->fetch('orderCustomFields'),
      'dictionary_select_options' => $dictionary_select_options,
      'addresses_radio_options' => $addresses_radio_options,
      'transport_radio_options' => $transport_radio_options,
      'defaultReturnAddress' => ($result->fetch('viewer')->customer->address ? $result->fetch('viewer')->customer->address->ID : null),

      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['repairs.js'],
      'pageTitle' => $this->t('repair_new.title'),
    ];

    return $this->renderPage('repair_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $helper = $this->formHelper();

    $addresses = $this->request->get('addresses', []);
    $address_errors = [];

    if ($addresses) {
      $result = $this->apiCustomer()->customerMutation()->updateViewer(
        null,
        [],
        array_map(function ($a) {
          return new AddressUpdateInput($a);
        }, $addresses)
      );
      if ($result->errors) {
        $address_errors = $result->errors;
      } else {
        $repair['returnAddress'] = $result->viewer->customer->address->ID;
      }
    }

    $repair = $this->request->get('repair', []);
    if (array_key_exists('customFields', $repair)) {
      $repair['customFields'] = $helper->mapCustomFields($repair['customFields']);
    }
    $repair['warranty'] = (array_key_exists('warranty', $repair) && $repair['warranty'] == '1');
    if (array_key_exists('returnAddress', $repair)) {
      $repair['pickUpAddress'] = $repair['returnAddress'];
    }

    $repair_input = new RepairInput($repair);
    $result = $this
      ->apiCustomer()
      ->customerMutation()
      ->createRepair($repair_input, [], $helper->mapTemporaryFiles($this->request->get('temporary_files')));

    if ($address_errors || $result->errors) {
      return $this->new(array_merge($result->errors, $address_errors));
    } else {
      return $this->redirectTo('repairs', 'flashes.repair_creation_successful');
    }
  }

  private function checkModuleActive()
  {
    $this->checkPanelActive();
    if (false === $this->getLayoutVars()['configuration']->panelRepairs) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }
  }
}