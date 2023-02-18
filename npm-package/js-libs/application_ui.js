Application.Ui = {};

Application.Ui.Autocomplete = function (input) {
  var
    eventSource = null,
    url = input.attr('data-url'),
    datalist_container = $('#' + input.attr('id') + '_datalist');

  if (url && datalist_container.length > 0) {
    input.on('keydown', function (e) {
      eventSource = e.key ? 'input' : 'list';
    })

    input.on('input', function () {
      if (eventSource === 'input') {
        if (input.val().length < 2) {
          return;
        }
        Application.Json.Request(url, 'get', {q: input.val()}, function (data) {
          var html = ''
          _.each(data, function (row) {
            html += "<option value=\"" + row + "\">";
          });
          datalist_container.html(html);
        });
      } else {
        datalist_container.html('');
      }
    });
  }
}

Application.Ui.DatePickerAttach = function () {
  var translations = Application.Options.Get('dpTranslations')
  $("#dtBox").DateTimePicker({
    dateFormat: 'yyyy-MM-dd',
    dateTimeFormat: 'yyyy-MM-dd hh:mm',
    minuteInterval: 5,
    animationDuration: 600,
    shortDayNames: _.map(_.split(translations.shortDayNames, ','), _.trim),
    fullDayNames: _.map(_.split(translations.fullDayNames, ','), _.trim),
    shortMonthNames: _.map(_.split(translations.shortMonthNames, ','), _.trim),
    fullMonthNames: _.map(_.split(translations.fullMonthNames, ','), _.trim),
    titleContentDate: translations.titleContentDate,
    titleContentTime: translations.titleContentTime,
    titleContentDateTime: translations.titleContentDateTime,
    setButtonContent: translations.setButtonContent,
    clearButtonContent: translations.clearButtonContent,
    afterShow: function () {
      $('.dtpicker-content').addClass('rounded-3');
      $('.dtpicker-button').addClass('rounded-1');
    }
  });
};

Application.Ui.Popup = {};

Application.Ui.Popup.ContainerCount = 0;

Application.Ui.Popup.Container = function (src) {
  Application.Ui.Popup.ContainerCount += 1;
  console.log("Application.Ui.Popup.Container: index: " + Application.Ui.Popup.ContainerCount + ", src: " + src);

  var html = "" +
    "  <div class='modal fade' id='modal-popup-" + Application.Ui.Popup.ContainerCount + "' tabindex='-1'>" +
    "    <div class='modal-dialog'>" +
    "      <div class='modal-content'>" +
    "        <div class='modal-header'>" +
    "          <h4 class='modal-title modal-header-title' data-title-confirm='Potwierdzenie' data-title-danger='Uwaga'></h4>" +
    "          <button type='button' class='btn-close' data-bs-dismiss='modal'></button>" +
    "        </div>" +
    "        <div class='modal-body clearfix' id='modal-popup-" + Application.Ui.Popup.ContainerCount + "-body'>" +
    "        </div>" +
    "        <div class='modal-footer clearfix'>" +
    "          <div class='modal-popup-btn-addons pull-left'></div>" +
    "          <button type='button' class='modal-popup-btn-cancel btn btn-default' data-bs-dismiss='modal' tabindex='-1'>Anuluj</button>" +
    "          <button type='button' class='modal-popup-btn-ok'>Dalej</button>" +
    "        </div>" +
    "      </div>" +
    "    </div>" +
    "  </div>";

  $('#modal-popups-container').append(html);

  var popup = $('#modal-popup-' + Application.Ui.Popup.ContainerCount);
  popup.on('popup:destroy', function () {
    var self = $(this);
    self.html('')
  });

  return popup;
};

Application.Ui.Popup.DataMethodAttach = function () {
  if ($(document).find('a[data-method]').length > 0) {
    var container = Application.Ui.Popup.Container('Application.Ui.PopupDataMethod');
    var modal = new bootstrap.Modal(document.getElementById(container.attr('id')), {
      backdrop: 'static',
      keyboard: false
    })

    var func_success = function (response_data, url, success_url) {
      Application.Url.Reload();
    };

    var func_error = function (responseData) {
      if (responseData.status === 402) {
        container.find('.modal-body').html('Z uwagi na nieopłaconą subskrypcję nie możemy wykonać tej operacji. Opłać subskrypcję, aby odzyskać możliwość modyfikacji danych.');
      } else if (responseData.status === 422) {
        container.find('.modal-body').html('Przesłane dane są nieprawidłowe. Być może element został zmodyfikowany przez kogoś innego. Odśwież stronę i spróbuj ponownie.');
      } else {
        container.find('.modal-body').html('Wystąpił problem uniemożliwiający przeprowadzenie tej operacji.');
      }
    };

    var func_submit = function (success_url) {
      var method = container.attr('data-method');
      var url = container.attr('data-url');
      Application.Json.Request(url, method, {}, function (response_data) {
        func_success(response_data, url, success_url)
      }, func_error);
    };

    var func_click = function (msg, color, url, method, success_url) {
      var title_container = container.find('.modal-header-title');
      if (color === 'danger') {
        title_container.html('<span class="text-danger"><span class="glyphicon glyphicon-exclamation-sign icon-margin"></span> ' + title_container.attr('data-title-danger') + '</span>');
      } else {
        title_container.html('<span class="glyphicon glyphicon-info-sign icon-margin"></span>' + title_container.attr('data-title-confirm'));
      }
      container.find('.modal-body').html(msg);
      var s_button = container.find('.modal-popup-btn-ok');
      s_button.removeAttr('class').addClass('modal-popup-btn-ok').addClass('btn').addClass('btn-' + color);
      s_button.attr('disabled', false);
      s_button.unbind().bind('click', function (e) {
        e.preventDefault();
        $(this).attr("disabled", "disabled");
        func_submit(success_url);
      });
      container.attr('data-url', url).attr('data-success-url', success_url).attr('data-method', method);

      modal.show()
    };

    $(document).find('a[data-method]').each(function () {
      var url = $(this).attr('href');
      if (url) {
        console.log('Application.Ui.Popup.DataMethodAttach', url);
        $(this).bind('click', function (e) {
          e.preventDefault();
          if (($(this).attr('data-confirm') || '').length > 0) {
            var color = $(this).attr('data-color');
            if (!color || color.length < 1 || color === 'null') {
              color = 'primary';
            }
            func_click($(this).attr('data-confirm'), color, url, $(this).attr('data-method'), $(this).attr('data-success-url'));
          } else {
            Application.Json.Request(url, $(this).attr('data-method'), {}, function (response_data) {
              func_success(response_data, url)
            });
          }
        }).removeClass('disabled').addClass('was-disabled');
      }
    });
  }
};

Application.Ui.FormErrorsToPopover = function () {
  $.each($('.is-invalid'), function (i, input) {
    if (document.getElementById($(input).attr('id'))) {
      var content = ($(input).attr("data-bs-content") || '').replace(/(?:\r\n|\r|\n)/g, '<br /><br />');
      new bootstrap.Popover(document.getElementById($(input).attr('id')), {
        trigger: 'hover',
        delay: {"show": 100, "hide": 500},
        placement: 'auto',
        html: true,
        content: content
      })
    }
  });
};

Application.Ui.FileUploadConfigure = function () {
  var translations = Application.Options.Get('fpTranslations');

  FilePond.registerPlugin(FilePondPluginImagePreview);
  FilePond.registerPlugin(FilePondPluginFileValidateType);

  FilePond.setOptions(_.merge(translations, {
    server: {
      url: Application.Options.Get('uploadUrl')
    },
    maxFiles: 10,
    allowMultiple: true,
    stylePanelLayout: 'compact',
    name: 'temporary_files[]',
    credits: [],
    allowFileTypeValidation: (Application.Options.Get('uploadOnlyImages') === 1),
    acceptedFileTypes: (Application.Options.Get('uploadOnlyImages') === 1 ? ['image/*'] : [])
  }));
}

Application.Ui.GenerateLogin = function (user_login, src_fields, watch_fields) {
  if (!watch_fields) {
    watch_fields = src_fields;
  }

  // blokuj zmiany loginu jeśli cokolwiek zostało wpisane tam ręcznie
  var user_login_touched = false;
  user_login.bind('blur', function (e) {
    user_login_touched = (user_login.val().replace(/\s+/g, '') !== '');
  });

  // funkcja generująca login na podstawie innych pól
  var display_name_to_login = function () {
    if (user_login_touched || user_login.is('[disabled=disabled]')) {
      return;
    }

    var input = null;

    _.each(src_fields, function (field) {
      if (!input && field.val() !== '') {
        input = field;
      }
    });

    if (input) {
      var str = input.val().toLocaleLowerCase().replace(/[^a-z0-9]/g, '').replace(/\s+/g, '') + ((new Date()).getDate() + 1) + ((new Date()).getDay() + 2) + ((new Date()).getMonth() + 3);
      if (input.val() === '') {
        str = '';
      }

      user_login.val(str);
    }
  };

  _.each(watch_fields, function (field) {
    if (field.attr('type') === 'checkbox') {
      field.bind('change', display_name_to_login);
    } else {
      field.bind('blur', display_name_to_login)
    }
  });
};

Application.Ui.PasswordStrength = function (login, password) {
  var func_password_validate = function (username, password, strength) {
    var
      indicator = $('#password-strength-indicator'),
      val = _.get(strength, 'score', 0),
      status = _.get(strength, 'status', ''),
      level_class = 'bg-danger',
      level_info = '<i class="fas fa-ban"></i>';

    if (status === 'good') {
      level_class = 'bg-warning';
      level_info = '<i class="fas fa-check"></i>';
    } else if (status === 'strong') {
      level_class = 'bg-success';
      level_info = '<i class="fas fa-thumbs-up"></i>';
    }

    if (val < 10) {
      val = 10;
    }

    indicator.removeClass('bg-danger').removeClass('bg-warning').removeClass('bg-success');

    if (_.get(strength, 'password', '').length > 0) {
      indicator.addClass(level_class).css('width', val + '%').attr('aria-valuenow', val).html(level_info);
    } else {
      indicator.addClass(level_class).css('width', 0 + '%').attr('aria-valuenow', 0).html('');
    }
  };

  $.strength(login, password, func_password_validate);
  $(login).keyup();
};

Application.Ui.FormCommitButtonLock = function () {
  $('form').find('button[type=submit]').click(function (e) {
    var submit = $(this);
    if (parseInt(submit.attr('data-skip-locking')) !== 1) {
      setTimeout(function () {
        submit.attr("disabled", true);
      }, 0);
    }
  });
};