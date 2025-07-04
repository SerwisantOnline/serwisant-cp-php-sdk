<?php

namespace Serwisant\SerwisantCp\Actions;

use Adbar\Dot;
use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\Traits;
use Serwisant\SerwisantApi\Types\SchemaPublic;

class RepairsPublic extends Action
{
  use Traits\Devices;
  use Traits\RepairsAction;

  /* queries [newRepair.graphql] */
  public function new($errors = [])
  {
    $this->checkModuleActive();

    if ($this->isAuthenticated()) {
      if ($this->request->get('device')) {
        return $this->redirectTo('new_repair', null, ['device' => $this->request->get('device')]);
      } else {
        return $this->redirectTo('new_repair');
      }
    }

    $result = $this->apiPublic()->publicQuery()->newRequest()->setFile('newRepair.graphql')->execute();

    /* @var $device SchemaPublic\Device */
    $device = $this->getDevicePublic();

    $variables = [
      'customFieldsDefinitions' => $result->fetch('repairCustomFields'),
      'agreementsDefinitions' => $result->fetch('customerAgreements'),
      'dictionary_select_options' => $this->dictionarySelectOptions($result->fetch('dictionaryEntries')),
      'transport_radio_options' => $this->transportRadioOptions(),
      'serviceSupplier' => $result->fetch('viewer')->repairsServiceSupplier,
      'device' => $device,
      'form_params' => $this->request->request,
      'temporary_files' => $this->formHelper()->mapTemporaryFiles($this->request->get('temporary_files')),
      'errors' => $errors,
      'js_files' => ['repairs_shared.js', 'repairs_public.js'],
      'pageTitle' => $this->t('repair_new.title'),
    ];

    return $this->renderPage('repair_public_new.html.twig', $variables);
  }

  /* queries [repairsAction.graphql createRepair.graphql] */
  public function create($device_uid)
  {
    $this->checkModuleActive();

    $action_configuration = $this->actionQuery()->fetch('configuration');
    $redirect_to_prompt = ($action_configuration->repairSubmitPrompt == SchemaPublic\RepairSubmitPrompt::ALWAYS || $action_configuration->repairSubmitPrompt == SchemaPublic\RepairSubmitPrompt::FIRST);

    $helper = $this->formHelper();

    $applicant = new Dot($this->request->get('applicant', []));
    $applicant_input = new SchemaPublic\AnonymousApplicantInput([
      'deviceUid' => $device_uid,
      'person' => $applicant['person'],
      'phone' => new SchemaPublic\PhoneInput($applicant['phone']),
      'email' => $applicant['email'],
      'agreements' => $helper->mapAgreements($applicant['agreements']),
    ]);

    $repair = $this->request->get('repair', []);
    if (array_key_exists('customFields', $repair)) {
      $repair['customFields'] = $helper->mapCustomFields($repair['customFields']);
    }
    $repair['warranty'] = (array_key_exists('warranty', $repair) && $repair['warranty'] == '1');
    $repair_input = new SchemaPublic\RepairInput($repair);

    $device = $this->getDevicePublic();

    if ($repair_input->delivery == SchemaPublic\RepairTransportType::PERSONAL && $repair_input->collection == SchemaPublic\RepairTransportType::PERSONAL) {
      $address_input = null;
    } elseif ($device && $device->address) {
      $address_input = new SchemaPublic\AddressInput(array_merge($device->address->toArray(), ['type' => SchemaPublic\AddressType::OTHER])); // always use original device address
    } else {
      $address_input = new SchemaPublic\AddressInput(array_merge($this->request->get('address', []), ['type' => SchemaPublic\AddressType::OTHER])); // pass given address
    }

    $result = $this->apiPublic()->publicMutation()->createRepair(
      $applicant_input,
      $repair_input,
      [],
      $helper->mapTemporaryFiles($this->request->get('temporary_files')),
      ($device ? $device->ID : null),
      $address_input
    );

    if ($result->errors) {
      return $this->new($result->errors);
    } else {
      $token = $result->repair->secretToken->token;
      if ($redirect_to_prompt) {
        return $this->redirectTo(['token_repair_submit_prompt', ['repairID' => $token]], 'flashes.repair_creation_successful');
      } else {
        return $this->redirectTo(['token', ['token' => $token]], 'flashes.repair_creation_successful');
      }
    }
  }

  /* queries [repairsAction.graphql] */
  public function submitPrompt()
  {
    $this->checkModuleActive();

    $action_query = $this->actionQuery();

    $variables = [
      'repairID' => $this->request->get('repairID'),
      'serviceSupplier' => $action_query->fetch('viewer')->repairsServiceSupplier,
      'prompt_content' => $action_query->fetch('configuration')->repairSubmitPromptContent,
    ];

    return $this->renderPage('repair_public_submit_prompt.html.twig', $variables);
  }
}