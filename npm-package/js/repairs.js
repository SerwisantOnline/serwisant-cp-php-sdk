$(document).ready(function () {
  Application.Ui.Autocomplete($('#repair_vendor'));
  Application.Ui.Autocomplete($('#repair_model'));

  var otherAddressContainer = $('.address-other-container');

  var otherAddressFunc = function () {
    var addressRadio = $('input[name="addressID"]:checked');
    if (!addressRadio.val()) {
      otherAddressContainer.slideDown()
    } else {
      otherAddressContainer.slideUp()
    }
  }

  $('input[name="addressID"]').change(otherAddressFunc);
  otherAddressFunc();

  var showHideOtherAddress = function () {
    if ($('input:radio[name="repair[delivery]"]:checked').val() === 'PERSONAL' && $('input:radio[name="repair[collection]"]:checked').val() === 'PERSONAL') {
      otherAddressContainer.slideUp();
    } else {
      otherAddressFunc();
    }
  }

  $('input:radio[name="repair[delivery]"]').on('click', showHideOtherAddress);
  $('input:radio[name="repair[collection]"]').on('click', showHideOtherAddress);

  Application.RepairsShared.Form();
})