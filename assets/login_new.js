Application.Login = {}

Application.Login.Resolve = function () {
  var
    btn = $('#new-session-resolve-login-btn'),
    loginInput = $('#session_credentials_login_credential');

  Application.Json.Request(btn.attr('data-url'), 'POST', {'login_credential': loginInput.val()}, function (data) {
    if (_.size(data) !== 1) {
      loginInput.addClass('is-invalid');
      loginInput.attr('data-bs-content', btn.attr('data-tr-NOT_FOUND'))
      Application.Ui.FormErrorsToPopover();
    } else {
      var
        login = _.get(_.head(data), 'login'),
        unavailabilityReasons = _.get(_.head(data), 'unavailabilityReasons'),
        id = _.get(_.head(data), 'ID');

      if (_.indexOf(unavailabilityReasons, 'INTERNET_ACCESS_NOT_ENABLED') >= 0) {
        Application.Url.Go(_.replace(Application.Options.Get('createCustomerAccessUrl'), '/ID', '/' + id));
      } else if (_.size(unavailabilityReasons) > 0) {
        unavailabilityReasons = _.map(unavailabilityReasons, function (reason) {
          return btn.attr('data-tr-' + reason);
        });
        loginInput.addClass('is-invalid');
        loginInput.attr('data-bs-content', _.join(unavailabilityReasons, ' '));
        Application.Ui.FormErrorsToPopover();
      } else {
        loginInput.removeClass('is-invalid');
        $('#session_credentials_login').val(login);
        $('#new-session-d-login').slideUp();
        $('#new-session-d-password').slideDown();
        $('#session_credentials_password')
          .focus()
          .onEnterPress(function () {
            $('#new-session-form').submit();
          });
      }
    }
  })
}

$(document).ready(function () {
  $('#new-session-resolve-login-btn').click(Application.Login.Resolve);
  $('#session_credentials_login_credential').onEnterPress(Application.Login.Resolve);
});