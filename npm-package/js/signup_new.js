Application.Signup = {}

Application.Signup.Form = function () {
  var personal_or_company = function () {
    var val = $('#customer_type').val();
    if (val === 'PERSONAL') {
      $('.business_container').slideUp();
      $('.personal_container').slideDown();

      $('#customer_companyName').val('');
    } else {
      $('.business_container').slideDown();
      $('.personal_container').slideUp();
    }
  }

  $('#customer_type').change(personal_or_company);
  personal_or_company();

  var customer_name = $('#customer_companyName'), customer_person = $('#customer_person');
  Application.Ui.GenerateLogin($('#customer_login'), [customer_person, customer_name], [customer_person, customer_name]);

  Application.Ui.PasswordStrength('#customer_login', '#customer_password');

  var pleaseLoginModal = $('#pleaseLoginModal');
  if (pleaseLoginModal.length > 0) {
    $('#pleaseLoginModalConfirm').on('click', function () {
      Application.Url.Go($(this).attr('data-url'));
    });
    var modal = new bootstrap.Modal(pleaseLoginModal, {});
    modal.show();
  }
}


$(document).ready(function () {
  Application.Signup.Form();
})