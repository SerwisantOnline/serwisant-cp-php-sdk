Application.PasswordReset = {}

Application.PasswordReset.Call = function (e) {
  e.preventDefault();

  var
    btn = $('#password-reset-new-submit'),
    loginInput = $('#loginOrEmail'),
    form = $('#password_reset_new_form');

  var onSuccess = function (login) {
    form.submit();
  };

  Application.ResolveLogin(btn, loginInput, onSuccess);
}

$(document).ready(function () {
  $('#password-reset-new-submit').click(Application.PasswordReset.Call);
  $('#loginOrEmail').onEnterPress(Application.PasswordReset.Call);
});