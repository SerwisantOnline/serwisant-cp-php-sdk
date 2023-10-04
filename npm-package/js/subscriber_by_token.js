$(document).ready(function () {
  $('#token-button').click(function (e) {
    e.preventDefault();
    var tokenUrl = $('#token-button').attr('data-url');
    var inputVal = _.toUpper($('#token-input').val().replace(/[^a-z0-9]+/gi, ''));
    Application.Url.Go(_.replace(tokenUrl, 'ExampleToken', inputVal));
  });
})