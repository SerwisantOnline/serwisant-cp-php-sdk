Application.Tickets = {}

Application.Tickets.Form = function () {
  Application.TicketsShared.GroupFields();

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

  // "Point the address" option (addressID = gps): coordinates from the map; without browser location falls back to
  // "Enter another address"
  var map = Application.TicketsShared.MapPointer(function () {
    $('input[name="addressID"][value=""]').prop('checked', true).trigger('change');
  });

  // overridden ticket_new.html.twig from before 1.8.1 has no map - the option can't be used
  if ($('#map-container').length === 0) {
    var gpsRadio = $('input[name="addressID"][value="gps"]');
    if (gpsRadio.is(':checked')) {
      $('input[name="addressID"][value=""]').prop('checked', true);
    }
    gpsRadio.closest('.form-check').remove();
  }

  var otherAddressFunc = function () {
    var address = $('input[name="addressID"]:checked').val();
    if (address === 'gps') {
      $('.address-other-container').slideUp();
      map.show();
    } else {
      map.hide();
      if (!address) {
        $('.address-other-container').slideDown()
      } else {
        $('.address-other-container').slideUp()
      }
    }
  }

  $('input[name="addressID"]').change(otherAddressFunc);
  otherAddressFunc();
}

$(document).ready(function () {
  Application.Tickets.Form();
})