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

  $('#repair_type').change(function () {
    customFields($(this).val());
  });
  customFields($('#repair_type').val());

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

  Application.Ui.Autocomplete($('#repair_vendor'));
  Application.Ui.Autocomplete($('#repair_model'));
}

$(document).ready(function () {
  Application.Repairs.Form();
})