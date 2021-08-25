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
});