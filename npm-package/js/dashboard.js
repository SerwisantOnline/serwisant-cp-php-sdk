$(document).ready(function () {
  var calendarContainer = $('#calendar');
  var calendar = new FullCalendar.Calendar(calendarContainer[0], {
    initialView: 'timeGridWeek',
    locale: 'pl',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    dateClick: function (info) {
      calendar.changeView('timeGridDay', info.dateStr);
    },
    themeSystem: 'bootstrap5',
    eventSources: [
      {url: calendarContainer.attr('data-schedules-url'), startParam: 'from', endParam: 'to'},
      {url: calendarContainer.attr('data-tickets-url'), startParam: 'from', endParam: 'to'},
    ]
  });
  calendar.render();
});