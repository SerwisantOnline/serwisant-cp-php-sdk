$.fn.onEnterPress = function (fnc) {
  return this.each(function () {
    $(this).keypress(function (ev) {
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
  Application.Ui.FormCommitButtonLock();
  Application.Ui.Select2();

  var bsToasts = $('.toast');
  if (bsToasts.length > 0) {
    (new bootstrap.Toast($('.toast'))).show();
  }
});