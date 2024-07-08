Application.Devices = {}

Application.Devices.Form = function () {
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

  var repairTypeInput = $('#device_type');
  repairTypeInput.on('change', function () {
    customFields($(this).val());
  })
  customFields(repairTypeInput.val());

  if ($('#create_device_file_uploader').length > 0) {
    Application.Ui.FileUploadConfigure(true)
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
    pond.appendTo(document.getElementById('create_device_file_uploader'));
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

  Application.Ui.Autocomplete($('#device_vendor'));
  Application.Ui.Autocomplete($('#device_model'));
}

$(document).ready(function () {
  Application.Devices.Form();
})