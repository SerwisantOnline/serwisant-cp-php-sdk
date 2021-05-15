/*!
  * online_payment
  * Part of serwisant customer panel
  */

Application.OnlinePayment = {}

Application.OnlinePayment.HandleTransaction = function (transaction, onRedirect, onSuccess, onError, afterRedirect) {
  if (transaction.status === "POOL" || (afterRedirect && transaction.status === "REDIRECT_POOL")) {
    setTimeout(function () {
      var url = Application.Options.Get('token_payment_pool_path');
      Application.Json.Request(url, 'GET', {id: transaction.ID}, function (transaction_pooled) {
        Application.OnlinePayment.HandleTransaction(transaction_pooled, onRedirect, onSuccess, onError, afterRedirect);
      }, function () {
        onError();
      });
    }, 1000);
  } else if (transaction.status === "REDIRECT_POOL") {
    if (onRedirect) {
      onRedirect(transaction);
    }
  } else if (transaction.status === "SUCCESSFUL") {
    onSuccess(transaction);
  } else if (transaction.status === "FAILED") {
    onError(transaction);
  }
}

Application.OnlinePayment.PayByBlik = function () {
  var unlockControls = function () {
    $("#pay-button").prop("disabled", false);
    $("#code-input").val('').prop("disabled", false);
  };

  $("#pay-button").prop("disabled", true);
  $("#code-input").prop("disabled", true);

  var container = Application.Ui.Popup.Container();
  var modal = new bootstrap.Modal(document.getElementById(container.attr('id')), {backdrop: 'static', keyboard: false})

  var onSubmit = function (transaction) {
    var onSuccess = function () {
      container.find('.modal-header').html($('#code-header-success').html());
      container.find('.modal-body').html($('#code-body-success').html());
      container.find('.modal-popup-btn-ok').click(function () {
        Application.Url.Go(Application.Options.Get('token_path'))
      }).slideDown();
      unlockControls();
    }
    var onError = function () {
      container.find('.modal-header').html($('#code-header-failure').html());
      container.find('.modal-body').html($('#code-body-failure').html());
      container.find('.modal-popup-btn-cancel').slideDown();
      unlockControls();
    };

    container.find('.modal-header').html($('#code-header-confirm').html());
    container.find('.modal-body').html($('#code-body-waiting').html());

    Application.OnlinePayment.HandleTransaction(transaction, null, onSuccess, onError);
  }

  var onSubmissionError = function (errors) {
    var message = [];
    _.each(errors, function (error) {
      message.push(error.message);
    });
    container.find('.modal-header').html($('#code-header-errors').html());
    container.find('.modal-body').html('<ul><li>' + _.join(message, '<li>') + '</ul>');
    container.find('.modal-popup-btn-cancel').slideDown();
    unlockControls();
  }

  container.on('shown.bs.modal', function () {
    var codeData = {
      code: $("#code-input").val(),
      payment_type: "BLIK",
      agreement_payment: $('#agreement_payment').is(':checked'),
      agreement_data_processing: $('#agreement_data_processing').is(':checked')
    };
    Application.Json.Request(Application.Options.Get('token_payment_pay_path'), 'POST', codeData, onSubmit, onSubmissionError);
  });

  container.find('.modal-header').html($('#code-header-sending').html());
  container.find('.modal-body').html($('#code-body-waiting').html());
  container.find('.modal-popup-btn-cancel').addClass('undisplayed');
  container.find('.modal-popup-btn-ok').addClass('undisplayed btn btn-success');
  modal.show();
}

Application.OnlinePayment.TransferHandleTransaction = function (modal, transaction, afterRedirect) {
  var onRedirect = function (transaction) {
    modal.find('.modal-header').html($('#transfer-header-redirecting').html());
    modal.find('.modal-body').html($('#transfer-body-waiting').html());
    setTimeout(function () {
      Application.Url.Go(transaction.processorUrl);
    }, 3000);
  }
  var onSuccess = function () {
    modal.find('.modal-header').html($('#transfer-header-success').html());
    modal.find('.modal-body').html($('#transfer-body-success').html());
    modal.find('.modal-popup-btn-ok').click(function () {
      Application.Url.Go(Application.Options.Get('token_path'))
    }).slideDown();
  }
  var onError = function () {
    $('#pay-button').prop("disabled", false);
    modal.find('.modal-header').html($('#transfer-header-failure').html());
    modal.find('.modal-body').html($('#transfer-body-failure').html());
    modal.find('.modal-popup-btn-cancel').slideDown();
  }
  Application.OnlinePayment.HandleTransaction(transaction, onRedirect, onSuccess, onError, afterRedirect);
}

Application.OnlinePayment.PayByTransfer = function () {
  $('#pay-button').prop("disabled", true);

  var container = Application.Ui.Popup.Container();
  var modal = new bootstrap.Modal(document.getElementById(container.attr('id')), {backdrop: 'static', keyboard: false})

  var onSubmit = function (transaction) {
    Application.OnlinePayment.TransferHandleTransaction(container, transaction, false);
  }

  var onSubmissionError = function (errors) {
    var message = [];
    _.each(errors, function (error) {
      message.push(error.message);
    });
    container.find('.modal-header').html($('#transfer-header-errors').html());
    container.find('.modal-body').html('<ul><li>' + _.join(message, '<li>') + '</ul>');
    container.find('.modal-popup-btn-cancel').slideDown();
    $('#pay-button').prop("disabled", false);
  }

  container.on('shown.bs.modal', function () {
    var transferData = {
      channel: $('#pay-button').attr('data-payment-channel-id'),
      payment_type: "TRANSFER",
      agreement_payment: $('#agreement_payment').is(':checked'),
      agreement_data_processing: $('#agreement_data_processing').is(':checked')
    };
    Application.Json.Request(Application.Options.Get('token_payment_pay_path'), 'POST', transferData, onSubmit, onSubmissionError);
  })
  container.find('.modal-header').html($('#transfer-header-sending').html());
  container.find('.modal-body').html($('#transfer-body-waiting').html());
  container.find('.modal-popup-btn-cancel').addClass('undisplayed');
  container.find('.modal-popup-btn-ok').addClass('undisplayed btn btn-success');
  modal.show();
}

Application.OnlinePayment.TransferPool = function (currentUrl) {
  var container = Application.Ui.Popup.Container();
  var modal = new bootstrap.Modal(document.getElementById(container.attr('id')), {backdrop: 'static', keyboard: false})

  container.on('shown.bs.modal', function () {
    var transaction = {status: currentUrl.params.result, ID: currentUrl.params.id};
    Application.OnlinePayment.TransferHandleTransaction(container, transaction, true)
  })
  container.find('.modal-header').html($('#transfer-header-receiving').html());
  container.find('.modal-body').html($('#transfer-body-waiting').html());
  container.find('.modal-popup-btn-cancel').addClass('undisplayed');
  container.find('.modal-popup-btn-ok').addClass('undisplayed btn btn-success');
  modal.show();
}

$(document).ready(function () {
  $("#pay-button").prop("disabled", true);

  $('#accordion_payment_methods').on('show.bs.collapse', function (e) {
    var currentMethod = $(e.target).attr('data-payment-method');
    var button = $('#pay-button');
    button.unbind()
    button.attr('data-payment-method', currentMethod)
    if (currentMethod === 'BLIK') {
      button.prop("disabled", false);
      button.click(Application.OnlinePayment.PayByBlik);
    } else if (currentMethod === 'TRANSFER') {
      button.prop("disabled", false);
      button.click(Application.OnlinePayment.PayByTransfer);
    }
  });

  $('.transfer-channel-tile').click(function () {
    $('.transfer-channels').find('img').removeClass('transfer-channel-is-selected').addClass('transfer-channel-not-selected');
    $(this).find('img').removeClass('transfer-channel-not-selected').addClass('transfer-channel-is-selected');
    $('#pay-button').attr('data-payment-channel-id', $(this).attr('data-transfer-channel-id'))
  });

  var currentUrl = Application.Url.Current();
  if (currentUrl.params.result) {
    Application.OnlinePayment.TransferPool(currentUrl)
  }
})