Application.TicketsPublic = {}

Application.TicketsPublic.Form = function () {
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

  var mapContainer = $('#map-container');
  var mapInitialized = false;
  var showAddressToggle = $('#showAddress');
  var funcMap = function() {
    if (mapInitialized) {
      return;
    }
    if ($('#geoPoint_lat').val() && $('#geoPoint_lng').val()) {
      $('#show-address-toggle-container').slideDown();
      mapContainer.slideDown(400, function () {
        Application.Maps.MapWithPointer($('#map'), $('#geoPoint_lat').val(), $('#geoPoint_lng').val(), function (lat, lng) {
          $('#geoPoint_lat').val(lat);
          $('#geoPoint_lng').val(lng);
        });
      });
    } else {
      Application.Maps.GetLocation(function (coords) {
        if (coords.lat === undefined && coords.lng === undefined) {
          $('#address-container').slideDown();
        } else {
          $('#show-address-toggle-container').slideDown();
          $('#geoPoint_lat').val(coords.lat);
          $('#geoPoint_lng').val(coords.lng);
          mapContainer.slideDown(400, function () {
            Application.Maps.MapWithPointer($('#map'), coords.lat, coords.lng, function (lat, lng) {
              $('#geoPoint_lat').val(lat);
              $('#geoPoint_lng').val(lng);
            });
          });
        }
      });
    }
    mapInitialized = true;
  }
  var funcShowAddress = function () {
    if ($(this).is(":checked")) {
      $('#map-container').slideUp();
      $('#address-container').slideDown();
    } else {
      $('#address-container').slideUp();
      $('#map-container').slideDown(400, funcMap);
    }
  }

  if (mapContainer.length > 0) {
    if (!showAddressToggle.is(":checked")) {
      funcMap();
    }
    showAddressToggle.change(funcShowAddress)
  }
}

$(document).ready(function () {
  Application.TicketsPublic.Form();
})