Application.TicketsShared = {}

// ticket group (#ticket_group) drives custom fields visibility and priority field, used by new ticket forms
Application.TicketsShared.GroupFields = function () {
  // custom fields with concern are visible only for selected ticket group (CustomField.concern = Dictionary.ID)
  var customFields = function (id) {
    $('.custom-field').each(function () {
      if ($(this).attr('data-type-id') !== '') {
        if ($(this).attr('data-type-id') === id) {
          $(this).removeClass('undisplayed');
        } else {
          $(this).addClass('undisplayed');
        }
      }
    })
  };

  // when ticket groups are available, priority field is hidden and becomes visible only for a group without assigned
  // priority; group with priority sets it instead
  var ticketGroupInput = $('#ticket_group');
  var groupPriorities = $('#ticket_group_container').data('priorities') || {};
  var priorityInput = $('#ticket_priority');
  var priorityContainer = $('#ticket_priority_container');
  // priority chosen by user before the field was hidden (null = field not hidden by us); if the field is hidden
  // already by the server, user did not choose anything yet
  var userPriority = priorityContainer.hasClass('undisplayed') ? '' : null;

  var groupPriority = function (id) {
    if (ticketGroupInput.length === 0) {
      return;
    }
    var priority = id ? groupPriorities[id] : null;
    if (id && !priority) {
      if (userPriority !== null) {
        priorityInput.val(userPriority).trigger('change');
        userPriority = null;
      }
      priorityContainer.removeClass('undisplayed');
    } else {
      if (userPriority === null) {
        userPriority = priorityInput.val();
      }
      if (priority) {
        priorityInput.val(priority).trigger('change');
      } else if (priorityContainer.length > 0) {
        priorityInput.val('').trigger('change'); // no group - do not send priority the customer can't see
      }
      priorityContainer.addClass('undisplayed');
    }
  };

  ticketGroupInput.on('change', function () {
    customFields($(this).val());
    groupPriority($(this).val());
  })
  customFields(ticketGroupInput.val());
  groupPriority(ticketGroupInput.val());
}
