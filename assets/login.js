Application.Login = {}

Application.Login.Resolve = function () {
  var
    btn = $('#new-session-resolve-login-btn'),
    login_credential_input = $('#session_credentials_login_credential');

  Application.Json.Request(btn.attr('data-url'), 'POST', {'login_credential': login_credential_input.val()}, function (data) {
    if (_.size(data) !== 1) {
      login_credential_input.addClass('is-invalid');
      login_credential_input.attr('data-bs-content', btn.attr('data-tr-login-not-found'))
      Application.Ui.FormErrorsToPopover();
    } else {
      login_credential_input.removeClass('is-invalid');
      $('#session_credentials_login').val(_.get(_.head(data), 'login'));
      $('#new-session-d-login').slideUp();
      $('#new-session-d-password').slideDown();
      $('#session_credentials_password')
        .focus()
        .onEnterPress(function () {
          $('#new-session-form').submit();
        });
    }
  })
}

$(document).ready(function () {
  $('#new-session-resolve-login-btn').click(Application.Login.Resolve);
  $('#session_credentials_login_credential').onEnterPress(Application.Login.Resolve);
});