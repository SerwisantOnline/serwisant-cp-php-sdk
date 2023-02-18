/*!
  * application
  * Part of serwisant customer panel
  */

var Application = {};

Application.Options = {};

Application.Options.Get = function (variable) {
  return (application_js_options[variable] !== undefined ? application_js_options[variable] : null);
};

Application.Url = {};

Application.Url.Parse = function (url) {
  var parsed = purl(url);
  return {
    source: parsed.attr('source'),
    protocol: parsed.attr('protocol'),
    host: parsed.attr('host'),
    port: parsed.attr('port'),
    relative: parsed.attr('relative'),
    path: parsed.attr('path'),
    directory: parsed.attr('directory'),
    file: parsed.attr('file'),
    query: parsed.attr('query'),
    anchor: parsed.attr('anchor'),
    params: parsed.param()
  };
};

Application.Url.Build = function (obj) {
  var url = '';
  if (obj.protocol && obj.host) {
    url += obj.protocol + '://' + obj.host;
    if (obj.port) {
      url += ':' + obj.port
    }
  }
  url += obj.path;
  if (obj.query) {
    url += '?' + obj.query;
  }
  if (obj.params && _.size(obj.params) > 0) {
    url += '?' + $.param(obj.params);
  }
  if (obj.anchor) {
    url += '#' + obj.anchor;
  }
  return url;
};

Application.Url.Current = function () {
  return Application.Url.Parse(document.URL);
};

Application.Url.Reload = function () {
  window.location.reload();
};

Application.Url.Go = function (url) {
  var
    current = Application.Url.Parse(location.href),
    changed = Application.Url.Parse(url);

  location.href = url;

  // jeśli zmienia się tylko anchor (ścieżka zostaje ta sama, to przeglądarka nie przeładuje strony) - trzeba to wymusić
  if (current.path === changed.path && current.anchor !== changed.anchor) {
    Application.Url.Reload();
  }
};

Application.Json = {};

Application.Json.Request = function (url, method, data, on_success, on_error) {
  if (!data) {
    data = {};
  }
  if ('put' === method.toLowerCase()) {
    method = 'post';
    $.extend(data, {_method: 'PUT'});
  }
  var params = {
    type: method,
    url: url,
    contentType: 'application/json',
    dataType: 'json',
    success: on_success,
    error: function (data) {
      if (data.status !== 402) {
        console.warn('Application.Json.Request error: ' + data.status + ', data: ' + JSON.stringify(data.responseJSON));
      }
      if (on_error) {
        on_error(data.responseJSON, data.status);
      }
    },
    failure: function (data) {
      console.error("Application.Json.Request failure: " + JSON.stringify(data));
      if (on_error) {
        on_error(data);
      }
    }
  }
  if ('post' === method.toLowerCase()) {
    $.extend(params, {data: JSON.stringify(data)});
  } else if ('get' === method.toLowerCase()) {
    var url_with_data = Application.Url.Parse(params.url);
    url_with_data.params = data;
    $.extend(params, {url: Application.Url.Build(url_with_data)});
  }
  $.ajax(params);
};

Application.ResolveLogin = function (btn, loginInput, onSuccess) {
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

      if (_.indexOf(unavailabilityReasons, 'INTERNET_ACCESS_NOT_ENABLED') >= 0 && Application.Options.Get('panelSignups') == '1') {
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
        onSuccess(login);
      }
    }
  })
}