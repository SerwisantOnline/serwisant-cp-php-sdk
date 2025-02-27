Application.Repairs = {}

Application.Repairs.Form = function () {
  var customFields = function (id) {
    $('.custom-field').each(function () {
      if ($(this).attr('data-type-id') !== '') {
        if ($(this).attr('data-type-id') === id) {
          $(this).removeClass('undisplayed');
        } else {
          $(this).addClass('undisplayed');
        }
      }
    })
  };

  var repairTypeInput = $('#repair_type');
  repairTypeInput.on('change', function () {
    customFields($(this).val());
  })
  customFields(repairTypeInput.val());

  $('#repair_warranty').change(function () {
    if ($(this).is(":checked")) {
      $('#create_repair_warranty_attributes').slideDown();
    } else {
      $('#create_repair_warranty_attributes').slideUp();
      $('#repair_warrantyPurchaseDate').val('');
      $('#repair_warrantyPurchaseDocument').val('');
    }
  })

  if ($('#repair_warranty').is(":checked")) {
    $('#create_repair_warranty_attributes').removeClass('undisplayed');
  }

  if ($('#create_repair_file_uploader').length > 0) {
    Application.Ui.FileUploadConfigure();
    var pond = FilePond.create({
      files: _.map($('.temporary-file-json'), function (div) {
        return {
          source: $(div).attr('data-ID'),
          options: {
            type: 'local'
          }
        }
      })
    });
    pond.appendTo(document.getElementById('create_repair_file_uploader'));
    pond.on('addfilestart', function () {
      $('.form-buttons > button').addClass('disabled');
    })
    pond.on('processfile', function () {
      $('.form-buttons > button').removeClass('disabled');
    })
    pond.on('error', function () {
      $('.form-buttons > button').removeClass('disabled');
    })
  }

  Application.Ui.Autocomplete($('#repair_vendor'));
  Application.Ui.Autocomplete($('#repair_model'));

  var otherAddressFunc = function () {
    var addressRadio = $('input[name="addressID"]:checked');
    if (!addressRadio.val()) {
      $('.address-other-container').slideDown()
    } else {
      $('.address-other-container').slideUp()
    }
  }

  $('input[name="addressID"]').change(otherAddressFunc);
  otherAddressFunc();
}

$(document).ready(function () {
  Application.Repairs.Form();
})