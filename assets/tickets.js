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

  var otherAddressFunc = function () {
    var addressRadio = $('input[name="ticket[address]"]:checked');
    if (!addressRadio.val()) {
      $('.address-other-container').slideDown()
    } else {
      $('.address-other-container').slideUp()
    }
  }

  $('input[name="ticket[address]"]').change(otherAddressFunc);
  otherAddressFunc();
}

$(document).ready(function () {
  Application.Tickets.Form();
})