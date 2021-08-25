<?php

namespace Serwisant\SerwisantCp\Actions;

use Serwisant\SerwisantCp\Action;
use Serwisant\SerwisantCp\ExceptionNotFound;

use Serwisant\SerwisantApi\Types\SchemaPublic;

class PaymentPayByToken extends Action
{
  public function call()
  {
    $agreements = new SchemaPublic\OnlineTransactionAgreementsInput();
    $agreements->payment = ($this->request->get('agreement_payment') == '1');
    $agreements->dataProcessing = ($this->request->get('agreement_data_processing') == '1');

    $online_transaction = new SchemaPublic\OnlineTransactionInput();
    $online_transaction->agreements = $agreements;

    switch ($this->request->get('payment_type')) {
      case SchemaPublic\OnlinePaymentMethodType::BLIK:
        $online_transaction->type = SchemaPublic\OnlinePaymentMethodType::BLIK;
        $online_transaction->code = $this->request->get('code');
        break;
      case SchemaPublic\OnlinePaymentMethodType::TRANSFER:
        $online_transaction->type = SchemaPublic\OnlinePaymentMethodType::TRANSFER;
        $online_transaction->channel = $this->request->get('channel');
        break;
      default:
        throw new ExceptionNotFound(__CLASS__, __LINE__);
    }

    $payment = $this->callMutation($online_transaction, (string)$this->token);

    if (!$payment->errors) {
      return $this->app->json($payment->onlineTransaction, 201);
    } else {
      return $this->app->json($this->translateErrors($payment->errors), 406);
    }
  }

  private function callMutation($online_transaction, $token)
  {
    $proto_host = $this->request->getScheme() . '://' . $this->request->getHttpHost();

    return $this->apiPublic()->publicMutation()->pay(
      $token,
      $online_transaction,
      $proto_host . $this->generateUrl('token', ['token' => $token], ['result' => SchemaPublic\OnlineTransactionStatus::POOL, 'id' => '%s']),
      $proto_host . $this->generateUrl('token', ['token' => $token], ['result' => SchemaPublic\OnlineTransactionStatus::FAILED])
    );
  }
}