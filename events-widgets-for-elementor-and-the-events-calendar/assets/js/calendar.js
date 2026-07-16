class ECAECalendarClass extends elementorModules.frontend.handlers.Base {
  getDefaultSettings() {
    return {
      selectors: {
        calenderSelector: '.ectbe-event-calendar-cls',
        popupmodal: '#ectbe-popup-wraper',
        popupClose: '.ectbe-modal-close',
      },
    };
  }

  getDefaultElements() {
    const selectors = this.getSettings('selectors');
    return {
      $calenderSelector: this.$element.find(selectors.calenderSelector),
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
            enddate =  moment(fetchInfo.end).format("YYYY-MM-DD"),
            monthagodate = moment(startdate).subtract(30, 'days').format("YYYY-MM-DD"),
            apiurl,
            categoryarray = '';
          if (ev_category != '' && jQuery.inArray('all', ev_category) == -1) {
            categoryarray = '&categories=' + encodeURIComponent(ev_category);
          }
          if (daterange == 'yes') {
            apiurl = wpApiSettings.root + 'tribe/events/v1/events?start_date=' + encodeURIComponent(rangeStart) + '&end_date=' + encodeURIComponent(rangeEnd) + '&per_page=' + encodeURIComponent(max_events) + '&status=publish' + categoryarray;
          } else {
            apiurl = wpApiSettings.root + 'tribe/events/v1/events?start_date='+startdate+'&end_date='+enddate + '&per_page=100&status=publish' + categoryarray;
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
                var descriptionWords = eventEl.description.split(" ");
                var descriptionExcerpt = descriptionWords.slice(0, 10).join(" ");
                if (descriptionWords.length > 10) {
                  descriptionExcerpt += "...";
                }
                events.push({
                  'id': eventEl.id,
                  'title': eventEl.title,
                  'start': eventEl.start_date,
                  'end': eventEl.end_date,
                  extendedProps: {
                    'imgurl': imgurl,
                    'description': descriptionExcerpt,
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
            enddate = '- ' + moment(info.event.end).format('MMMM D, YYYY h:mm A');
          }
          popupmodal.css("display", "block");
          popupmodal.addClass("ectbe-ec-popup-ready").removeClass("ectbe-ec-modal-removing");
          jQuery('h2.ectbe-ec-modal-title').html(info.event.title);
          jQuery('.ectbe-event-date-start').text(moment(info.event.start).format('MMMM D, YYYY h:mm A'));
          jQuery('.ectbe-event-date-end').text(enddate);

          var feature_image = info.event.extendedProps.imgurl;
          var $featured_image = jQuery( '.ectbe-featured-img' );

          if ( feature_image && feature_image.trim() !== '' ) {
          if ( $featured_image.length === 0 ) {
          $featured_image = jQuery( '<div>', {
          class: 'ectbe-featured-img'
          } );

            jQuery( '.ectbe-modal-content' ).prepend( $featured_image );
          }

          $featured_image
            .empty()
            .append(
              jQuery( '<img>', {
                src: encodeURI( feature_image ),
                class: 'ectbe-img'
              } )
            );

          } else {
          $featured_image.remove();
          }

          let cost = info.event.extendedProps.eventcost;
          var $cost = jQuery( '.ectbe-modal-body span.ectbe-cost' );
          if (cost != '') {
            $cost.addClass('fa fa-money');
          } else {
            $cost.removeClass('fa fa-money');
          }
          $cost.text(cost);

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

