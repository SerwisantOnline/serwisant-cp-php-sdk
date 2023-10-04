Application.Login = {}

Application.Login.Call = function () {
  var
    btn = $('#new-session-resolve-login-btn'),
    loginInput = $('#session_credentials_login_credential');

  var onSuccess = function (login) {
    $('#session_credentials_login').val(login);
    $('#new-session-d-login').slideUp();
    $('#new-session-d-password').slideDown();
    $('#session_credentials_password')
      .focus()
      .onEnterPress(function () {
        $('#new-session-form').submit();
      });
  };

  Application.ResolveLogin(btn, loginInput, onSuccess);
}

$(document).ready(function () {
  $('#new-session-resolve-login-btn').click(Application.Login.Call);
  $('#session_credentials_login_credential').onEnterPress(Application.Login.Call);
});