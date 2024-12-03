class ECAECalendarClass extends elementorModules.frontend.handlers.Base {
  getDefaultSettings() {
    return {
      selectors: {
        calendarWrapper: '.ectbe-calendar-wrapper',
        calenderSelector: '.ectbe-event-calendar-cls',
        calendarEl: '#ectbe-event-calendar',
        popupmodal: '#ectbe-popup-wraper',
        popupClose: '.ectbe-modal-close',
      },
    };
  }

  getDefaultElements() {
    const selectors = this.getSettings('selectors');
    return {
      $calendarWrapper: this.$element.find(selectors.calendarWrapper),
      $calenderSelector: this.$element.find(selectors.calenderSelector),
      $calendarEl: this.$element.find(selectors.calendarEl),
      $popupmodal: this.$element.find(selectors.popupmodal),
      $popupClose: this.$element.find(selectors.popupClose),
    };
  }

  bindEvents() {
    var selector = this.elements.$calenderSelector;
    if (selector.length > 0) {
      var first_day = selector.data("first_day"),
        cal_id = selector.data("cal_id"),
        locale = selector.data("locale"),
        defaultview = selector.data("defaultview"),
        daterange = selector.data("daterange"),
        rangeStart = selector.data("rangestart"),
        rangeEnd = selector.data("rangeend"),
        max_events = selector.data("max_events"),
        ev_category = selector.data("ev_category"),
        textColor = selector.data("textcolor"),
        color = selector.data("color"),
        calendarEl = document.getElementById('ectbe-event-calendar-' + cal_id);

      var isenglish = locale.indexOf('en-') !== -1;
      var buttonText = {};
      if (isenglish) {
        buttonText = {
          today: 'Today',
          month: 'Month',
          week: 'Week',
          day: 'Day',
          list: 'List'
        };
      }

      var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: defaultview,
        firstDay: first_day,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        buttonText: buttonText,
        navLinks: true,
        nextDayThreshold: "00:00:00",
        weekNumbers: false,
        weekNumberCalculation: 'ISO',
        editable: false,
        selectable: true,
        dayMaxEvents: true,
        locale: locale,
        eventLimit: 3,
        defaultView: defaultview,

        events: function (fetchInfo, successCallback, failureCallback) {
          var startdate = moment(fetchInfo.start).format("YYYY-MM-DD"),
            monthagodate = moment(startdate).subtract(30, 'days').format("YYYY-MM-DD"),
            apiurl,
            categoryarray = '';
          if (ev_category != '' && jQuery.inArray('all', ev_category) == -1) {
            categoryarray = '&categories=' + encodeURIComponent(ev_category);
          }
          if (daterange == 'yes') {
            apiurl = wpApiSettings.root + 'tribe/events/v1/events?start_date=' + encodeURIComponent(rangeStart) + '&end_date=' + encodeURIComponent(rangeEnd) + '&per_page=' + encodeURIComponent(max_events) + '&status=publish' + categoryarray;
          } else {
            apiurl = wpApiSettings.root + 'tribe/events/v1/events?start_date=' + encodeURIComponent(monthagodate) + '&per_page=100&status=publish' + categoryarray;
          }

          jQuery.ajax({
            url: apiurl,
            type: "GET",
            beforeSend: function (xhr) {
              jQuery(".ectbe_calendar_events_spinner").show();
            },
            success: function (result) {
              var events = [];
              jQuery.map(result.events, function (eventEl) {
                var imgurl = eventEl.image.url ? eventEl.image.url : '';
                events.push({
                  'id': eventEl.id,
                  'title': decodeHtmlCharCodes(eventEl.title),
                  'start': eventEl.start_date,
                  'end': eventEl.end_date,
                  'textColor': textColor,
                  'color': color,
                  extendedProps: {
                    'imgurl': imgurl,
                    'description': (eventEl.description.split(" ").length > 10) ? eventEl.description.split(" ").splice(0, 10).join(" ") + "..." : eventEl.description.split(" ").splice(0, 10).join(" "),
                    'detailurl': eventEl.url,
                    'eventcost': (eventEl.cost_details.values.length !== 0) ? (eventEl.cost_details.values[0] !== "0") ? eventEl.cost_details.currency_symbol + eventEl.cost_details.values[0] : "FREE" : ""
                  }
                });
              });
              jQuery(".ectbe_calendar_events_spinner").hide();
              successCallback(events);
            }
          });
        },
        eventClick: function (info) {
          info.jsEvent.preventDefault();
          var popupmodal = jQuery("#ectbe-popup-wraper");
          var enddate = '';
          if (info.event.allDay == false && info.event.end != null) {
            enddate = '- ' + moment(info.event.end).format('LLL');
          }
          popupmodal.css("display", "block");
          popupmodal.addClass("ectbe-ec-popup-ready").removeClass("ectbe-ec-modal-removing");
          jQuery('h2.ectbe-ec-modal-title').text(info.event.title);
          jQuery('.ectbe-event-date-start').text(moment(info.event.start).format('LLL'));
          jQuery('.ectbe-event-date-end').text(enddate);

          var feature_image = info.event.extendedProps.imgurl;
          jQuery('.ectbe-featured-img').html('<img src="' + encodeURI(feature_image) + '"/>');
          if (feature_image != '') {
            jQuery('.ectbe-featured-img img').addClass('ectbe-img');
          } else {
            jQuery('.ectbe-featured-img img').removeClass('ectbe-img');
          }

          let cost = info.event.extendedProps.eventcost;
          if (cost != '') {
            jQuery('.ectbe-modal-body span.ectbe-cost').addClass('fa fa-money');
          } else {
            jQuery('.ectbe-modal-body span.ectbe-cost').removeClass('fa fa-money');
          }
          jQuery('.ectbe-modal-body span.ectbe-cost').text(cost);

          var description = info.event.extendedProps.description;
          description = description.length > 300 ? description.substring(0, 250) + "..." : description;
          jQuery('.ectbe-modal-body p').html(description);

          jQuery('.ectbe-event-details-link').attr("href", encodeURI(info.event.extendedProps.detailurl));
        },
      });
      var popupCloseButton = this.elements.$popupClose,
        popupmodal = this.elements.$popupmodal;
      popupCloseButton.on("click", function (event) {
        event.stopPropagation();
        popupmodal.addClass("ectbe-ec-modal-removing").removeClass("ectbe-ec-popup-ready");
        popupmodal.css("display", "none");
      });
      calendar.render();

      function decodeHtmlCharCodes(str) {
        return str.replace(/(&#(\d+);)/g, function (match, capture, charCode) {
          return String.fromCharCode(charCode);
        });
      }
    }
  }
}

jQuery(window).on('elementor/frontend/init', () => {
  const addHandler = ($element) => {
    elementorFrontend.elementsHandler.addHandler(ECAECalendarClass, {
      $element,
    });
  };

  elementorFrontend.hooks.addAction('frontend/element_ready/the-events-calendar-addon.default', addHandler);
});

