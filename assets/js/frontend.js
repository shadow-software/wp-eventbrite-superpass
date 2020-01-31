"use strict";

document.addEventListener('DOMContentLoaded', (e) => {
    var calendarEl = document.getElementById('full-calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: [ 'timeGrid' ],
        defaultView: 'timeGridDay',
        eventClick: this.handleEventClick,
    });

    Vue.config.devtools = true;
    var ajaxurl = ajax_object.ajax_url;
    MicroModal.init({
        onShow: modal => console.info(`${modal.id} is shown`), // [1]
        onClose: modal => console.info(`${modal.id} is hidden`), // [2]
        openTrigger: 'data-micromodal-open', // [3]
        closeTrigger: 'data-micromodal-close', // [4]
        disableScroll: true, // [5]
        disableFocus: false, // [6]
        awaitOpenAnimation: false, // [7]
        awaitCloseAnimation: false, // [8]
        debugMode: true // [9]
    });

    var ESP_FRONTEND_JS = new Vue({
        el: "#esp-front-end",
        data: {
            settings : {},
            updating : false,
            customerData : esp_data.customer_data,
            superPass : null,
            modal : {
                title : "",
                content: "",
            },
            currentEvent: "",
        },
        mounted: function() {
            this.setup();
        },
        methods: {
            setup: function() {
                this.superPass = this.customerData.super_passes[0];

                if (this.superPass.events.length > 0) {
                    var events = this.superPass.events.map( function(event) {
                        return {
                            "id": event.id,
                            "title": event.name.text,
                            "allDay": true,
                            "start": event.start.local,
                            "end": event.end.local,
                            "extendedProps": {
                                "description": event.description.html,
                                "image": event.logo ? event.logo.original.url : false,
                                "url": event.url,
                            }
                        }
                    });

                    var startDate = events[0].start;

                    calendar.render();
                }
            },
            handleEventClick: function(info) {
                this.modal = {
                    title: info.event.title,
                    content: info.event.extendedProps.description,
                    image: info.event.extendedProps.image,
                    url: info.event.extendedProps.url
                }
                this.currentEvent = info.event;
                MicroModal.show('esp-modal');
            },
            attendEvent: function() {
                if (!this.updating) {
                    this.updating = true;
                    var data = new FormData();
                    data.append("action", "esp_customer_attend_event");
                    data.append('event_id', this.currentEvent.id);
                    data.append('super_pass_id', this.superPass.id);

                    axios
                        .post(ajaxurl, data)
                        .then(response => {
                            if (response.data.success === true) {
                                window.location.href = esp_data.eb_checkout_url + '?event_id=' + response.data.result.event_id + '&attendance=' + response.data.result.id;
                            } else {
                                this.updating = false;
                            }
                        })
                }
            }
        }
    });

    Vue.component('spinner', {
        template:
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="margin: auto; background: rgba(0, 0, 0, 0) none repeat scroll 0% 0%; display: block; shape-rendering: auto;" width="20px" height="20px" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">\n' +
            '<circle cx="50" cy="50" fill="none" stroke="#ffffff" stroke-width="10" r="35" stroke-dasharray="164.93361431346415 56.97787143782138">\n' +
            '  <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>\n' +
            '</circle>\n' +
            '</svg>'
    });
});