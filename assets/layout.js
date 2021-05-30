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

Application.Ui = {};

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
    var content = ($(input).attr("data-bs-content") || '').replace(/(?:\r\n|\r|\n)/g, '<br /><br />');
    new bootstrap.Popover(document.getElementById($(input).attr('id')), {
      trigger: 'hover',
      delay: {"show": 100, "hide": 500},
      placement: 'auto',
      html: true,
      content: content
    })
  });
};

Application.Ui.FileUploadConfigure = function () {
  var translations = {
    labelIdle: 'Przeciągnij i upuść lub <span class="filepond--label-action">wybierz</span> pliki',
    labelInvalidField: 'Nieprawidłowe pliki',
    labelFileWaitingForSize: 'Pobieranie rozmiaru',
    labelFileSizeNotAvailable: 'Nieznany rozmiar',
    labelFileLoading: 'Wczytywanie',
    labelFileLoadError: 'Błąd wczytywania',
    labelFileProcessing: 'Przesyłanie',
    labelFileProcessingComplete: 'Przesłano',
    labelFileProcessingAborted: 'Przerwano',
    labelFileProcessingError: 'Przesyłanie nie powiodło się',
    labelFileProcessingRevertError: 'Coś poszło nie tak',
    labelFileRemoveError: 'Nieudane usunięcie',
    labelTapToCancel: 'Anuluj',
    labelTapToRetry: 'Ponów',
    labelTapToUndo: 'Cofnij',
    labelButtonRemoveItem: 'Usuń',
    labelButtonAbortItemLoad: 'Przerwij',
    labelButtonRetryItemLoad: 'Ponów',
    labelButtonAbortItemProcessing: 'Anuluj',
    labelButtonUndoItemProcessing: 'Cofnij',
    labelButtonRetryItemProcessing: 'Ponów',
    labelButtonProcessItem: 'Prześlij',
    labelMaxFileSizeExceeded: 'Plik jest zbyt duży',
    labelMaxFileSize: 'Dopuszczalna wielkość pliku to {filesize}',
    labelMaxTotalFileSizeExceeded: 'Przekroczono łączny rozmiar plików',
    labelMaxTotalFileSize: 'Łączny rozmiar plików nie może przekroczyć {filesize}',
    labelFileTypeNotAllowed: 'Niedozwolony rodzaj pliku',
    fileValidateTypeLabelExpectedTypes: 'Oczekiwano {allButLastType} lub {lastType}',
    imageValidateSizeLabelFormatError: 'Nieobsługiwany format obrazu',
    imageValidateSizeLabelImageSizeTooSmall: 'Obraz jest zbyt mały',
    imageValidateSizeLabelImageSizeTooBig: 'Obraz jest zbyt duży',
    imageValidateSizeLabelExpectedMinSize: 'Minimalne wymiary obrazu to {minWidth}×{minHeight}',
    imageValidateSizeLabelExpectedMaxSize: 'Maksymalna wymiary obrazu to {maxWidth}×{maxHeight}',
    imageValidateSizeLabelImageResolutionTooLow: 'Rozdzielczość jest zbyt niska',
    imageValidateSizeLabelImageResolutionTooHigh: 'Rozdzielczość jest zbyt wysoka',
    imageValidateSizeLabelExpectedMinResolution: 'Minimalna rozdzielczość to {minResolution}',
    imageValidateSizeLabelExpectedMaxResolution: 'Maksymalna rozdzielczość to {maxResolution}'
  }

  FilePond.registerPlugin(FilePondPluginImagePreview);
  FilePond.setOptions(_.merge(translations, {
    server: {
      url: '/temporary_file'
    },
    maxFiles: 10,
    allowMultiple: true,
    stylePanelLayout: 'compact',
    name: 'temporary_files[]',
    credits: []
  }));
}

$.fn.onEnterPress = function (fnc) {
  return this.each(function () {
    $(this).keypress(function (ev) {
      console.log('press')
      var keycode = (ev.keyCode ? ev.keyCode : ev.which);
      if (parseInt(keycode) === 13) {
        ev.preventDefault();
        fnc.call(this, ev);
      }
    })
  })
}

$(document).ready(function () {
  Application.Ui.Popup.DataMethodAttach();
  Application.Ui.DatePickerAttach();
  Application.Ui.FormErrorsToPopover();
  Application.Ui.FileUploadConfigure();
});