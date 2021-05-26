Application.Repairs = {}

Application.Repairs.Form = function () {
  $('#repair_type').change(function () {
    var id = $(this).val();
    $('.custom-field').each(function () {
      if ($(this).attr('data-type-id') !== '') {
        if ($(this).attr('data-type-id') === id) {
          $(this).removeClass('undisplayed');
        } else {
          $(this).addClass('undisplayed');
        }
      }
    })
  })
}

$(document).ready(function () {
  Application.Repairs.Form();
})