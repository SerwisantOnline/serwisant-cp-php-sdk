<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Exception;
use Serwisant\SerwisantCp\ExceptionNotFound;
use Serwisant\SerwisantApi\Types\SchemaPublic\RepairSubmitPrompt;
use Serwisant\SerwisantApi\Types\SchemaCustomer;

class Repairs extends Action
{
  use Traits\Devices;
  use Traits\RepairsAction;

  /* queries [customer/repairs.graphql] */
  public function index()
  {
    $this->checkModuleActive();

    $limit = $this->getListLimit();
    $page = $this->request->get('page', 1);
    $filter = new SchemaCustomer\RepairsFilter(['type' => SchemaCustomer\RepairsFilterType::ALL]);
    $sort = SchemaCustomer\RepairsSort::DATE_UPDATED;

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

  /* queries [customer/repairs.graphql] */
  public function show($id)
  {
    $this->checkModuleActive();

    $filter = new SchemaCustomer\RepairsFilter(['type' => SchemaCustomer\RepairsFilterType::ID, 'ID' => $id]);
    $result = $this->apiCustomer()->customerQuery()->repairs(1, null, $filter, null, ['single' => true]);
    if (count($result->items) !== 1) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $variables = [
      'repair' => $result->items[0],
      'pageTitle' => $result->items[0]->rma,
    ];

    return $this->renderPage('repair.html.twig', $variables);
  }

  /* queries [customer/print.graphql] */
  public function print($id, $type)
  {
    $this->checkModuleActive();

    switch ($type) {
      case 'intro':
        $print_type = SchemaCustomer\PrintType::REPAIR_INTRO;
        break;
      case 'summary':
        $print_type = SchemaCustomer\PrintType::REPAIR_SUMMARY;
        break;
      default:
        throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $result = $this->apiCustomer()->customerMutation()->print($print_type, $id);

    if ($result->errors) {
      $ex_message = array_map(
        function ($error) {
          return trim("{$error->argument} {$error->code} {$error->message}");
        },
        $result->errors
      );
      throw new Exception(implode(',', $ex_message));
    } else {
      return $this->app->redirect($result->temporaryFile->url);
    }
  }

  /* queries [customer/repairsAction.graphql] */
  public function submitPrompt()
  {
    $action_query = $this->actionQuery();
    $vars = [
      'prompt_content' => $action_query->fetch('configuration')->repairSubmitPromptContent,
    ];
    return $this->renderPage('repair_submit_prompt.html.twig', $vars);
  }

  public function new($errors = [])
  {
    $this->checkModuleActive();

    $result = $this->apiCustomer()->customerQuery()->newRequest()->setFile('newRepair.graphql')->execute();

    /* @var $device SchemaCustomer\Device */
    $device = $this->getDevice();

    $addresses_radio_options = [];
    if ($device && $device->address) {
      $addresses_radio_options[$device->address->ID] = trim("{$device->address->postalCode} {$device->address->city}, {$device->address->street} {$device->address->building}");
    }
    foreach ($result->fetch('viewer')->customer->addresses as $address) {
      $addresses_radio_options[$address->ID] = trim("{$address->postalCode} {$address->city}, {$address->street} {$address->building}");
    }
    if (count($addresses_radio_options) > 0) {
      $addresses_radio_options[''] = $this->t('repair_new', 'other_address');
    }
    $default_address = array_key_first($addresses_radio_options);

    $variables = [
      'customFieldsDefinitions' => $result->fetch('orderCustomFields'),
      'dictionary_select_options' => $this->dictionarySelectOptions($result->fetch('dictionaryEntries')),
      'addresses_radio_options' => $addresses_radio_options,
      'transport_radio_options' => $this->transportRadioOptions(),
      'defaultAddress' => $default_address,
      'device' => $device,
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['repairs_shared.js', 'repairs.js'],
      'pageTitle' => $this->t('repair_new.title'),
    ];

    return $this->renderPage('repair_new.html.twig', $variables);
  }

  public function create()
  {
    $this->checkModuleActive();

    $action_configuration = $this->actionQuery()->fetch('configuration');
    $redirect_to_prompt = ($action_configuration->repairSubmitPrompt == RepairSubmitPrompt::ALWAYS || ($action_configuration->repairSubmitPrompt == RepairSubmitPrompt::FIRST && $this->hasNoRepairs()));

    $helper = $this->formHelper();

    $repair = $this->request->get('repair', []);
    if (array_key_exists('customFields', $repair)) {
      $repair['customFields'] = $helper->mapCustomFields($repair['customFields']);
    }
    $repair['warranty'] = (array_key_exists('warranty', $repair) && $repair['warranty'] == '1');
    $repair_input = new SchemaCustomer\RepairInput($repair);

    $device = $this->getDevice();

    if ($this->request->get('addressID')) {
      $address_input = new SchemaCustomer\AddressInput();
      if ($device && $device->address && $device->address->ID == $this->request->get('addressID')) {
        $address_input = new SchemaCustomer\AddressInput($device->address->toArray(['ID', 'geoPoint']));
      } else {
        foreach ($this->apiCustomer()->customerQuery()->viewer(['addresses' => true])->customer->addresses as $a) {
          if ($a->ID == $this->request->get('addressID')) {
            $address_input = new SchemaCustomer\AddressInput($a->toArray(['ID', 'geoPoint']));
          }
        }
      }
    } elseif ($repair_input->delivery == SchemaCustomer\RepairTransportType::PERSONAL && $repair_input->collection == SchemaCustomer\RepairTransportType::PERSONAL) {
      $address_input = null;
    } else {
      $address_input = new SchemaCustomer\AddressInput(array_merge($this->request->get('address', []), ['type' => SchemaCustomer\AddressType::OTHER]));
    }

    $result = $this->apiCustomer()->customerMutation()->createRepair(
      $repair_input,
      [],
      $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      ($device ? $device->ID : null),
      $address_input
    );

    if ($this->request->get('addressSaveToProfile') && !$this->request->get('addressID') && !$result->errors) {
      $this->apiCustomer()->customerMutation()->updateViewer(
        null,
        [],
        [new SchemaCustomer\AddressUpdateInput(array_merge($this->request->get('address', []), ['type' => SchemaCustomer\AddressType::OTHER]))]
      );
    }

    if ($result->errors) {
      return $this->new($result->errors);
    } elseif ($redirect_to_prompt) {
      return $this->redirectTo('repair_prompt', 'flashes.repair_creation_successful');
    } else {
      return $this->redirectTo(['repair', ['id' => $result->repair->ID]], 'flashes.repair_creation_successful');
    }
  }

  private function hasNoRepairs()
  {
    $filter = new SchemaCustomer\RepairsFilter(['type' => SchemaCustomer\RepairsFilterType::ALL]);
    $repairs = $this->apiCustomer()->customerQuery()->repairs(2, 1, $filter, SchemaCustomer\RepairsSort::DATE_UPDATED, ['count' => true]);
    return count($repairs->items) == 0;
  }
}