Application.Tickets = {}

Application.Tickets.Form = function () {
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

  if ($('#create_ticket_file_uploader').length > 0) {
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
    pond.appendTo(document.getElementById('create_ticket_file_uploader'));
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
  Application.Tickets.Form();
})