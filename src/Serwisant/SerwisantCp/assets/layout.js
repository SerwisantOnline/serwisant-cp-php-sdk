$(document).ready(function () {
  $('.datepicker').datepicker({
    clearBtn: true,
    format: "dd-mm-yyyy",
    language: '{{ locale_ISO|lower }}'
  });

  $('.click-and-get').click(function () {
    window.location.href = $(this).attr('data-url');
  });
});