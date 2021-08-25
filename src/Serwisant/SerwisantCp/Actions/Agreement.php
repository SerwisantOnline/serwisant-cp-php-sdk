<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaPublic\CustomerAgreementsFilter;

class Agreement extends Action
{
  public function show($id)
  {
    $filter = new CustomerAgreementsFilter();
    $filter->ID = $id;

    $agreements = $this->apiPublic()->publicQuery()->customerAgreements($filter);
    if (count($agreements) == 0) {
      throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $vars = [
      'agreement' => $agreements[0],
      'pageTitle' => $agreements[0]->description,
    ];
    return $this->renderPage('agreements.html.twig', $vars);
  }
}