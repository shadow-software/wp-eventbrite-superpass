"use strict";

document.addEventListener('DOMContentLoaded', (e) => {

    Vue.config.devtools = true

    var ESP_ADMIN_JS = new Vue({
        el: "#esp-admin-app",
        data: {
            settings: {},
            updating: false,
            message: {
                show: false,
                content: "",
                type: "",
            },
            editingPasses: false,
            ready: false,
            eventbriteData: {
                appKey: undefined,
                accessCode: undefined,
                clientSecret: undefined,
            },
            creatingPass: false,
            superPasses: [],
            superPass: {
                name : "",
                cost : 0.00,
                events: [],
            },
            superPassUpdating: false,
            queuedActions : 0,
        },
        mounted: function () {
            document.getElementById('esp-admin-app').style.display = "block";
            // We're going to make 2 AJAX calls but we don't want to display anything until both are done.
            this.queuedActions = 2;
            this.getSettings();
            this.getSuperPasses();
        },
        methods: {
            getSettings: function() {
                let data = new FormData();
                data.append('action', 'get_esp_settings');
                axios.post(ajaxurl, data)
                    .then(response => (this.settings = response.data))
                    .then(() => {
                        if (this.queuedActions > 0) {
                            this.queuedActions --;
                            if (this.queuedActions === 0) {
                                this.ready = true;
                            }
                        } else {
                            this.ready = true;
                        }
                        setTimeout( () => {
                            this.setupState();
                        }, 100)
                    })
            },
            getSuperPasses: function() {
                let data = new FormData();
                data.append('action', 'esp_get_super_passes');
                axios.post(ajaxurl, data)
                    .then(response => (this.superPasses = response.data))
                    .then(() => {
                        if (this.queuedActions > 0) {
                            this.queuedActions --;
                            if (this.queuedActions === 0) {
                                this.ready = true;
                            }
                        } else {
                            this.ready = true;
                        }
                        this.superPassUpdating = false;
                    });
            },
            setupState: function() {
                if (this.settings.eventbrite_setup_required === true) {
                    // Check if we are being redirected to this page from eventbrite with an access code.
                    var code = getAllUrlParams().code;
                    if (code) {
                        // Great we have our access code now! Let's save it on the server
                        var data = new FormData();
                        data.append('action', 'setup_esp_eventbrite_keys');
                        data.append('access_code', code.toUpperCase());

                        axios
                            .post(ajaxurl, data)
                            .then( response => {
                                if (response.data.success === true) {
                                    this.settings.eventbrite_setup_required = false;
                                } else {
                                    this.message.content = response.data.message;
                                    this.message.type = 'error';
                                    this.message.show = true;
                                }
                            })
                    }
                }
            },
            eventbriteSetup: function () {
                if (!this.updating && this.checkEventbriteData()) {
                    this.updating = true;
                    let data = new FormData();
                    data.append('action', 'setup_esp_eventbrite_keys');
                    data.append('api_key', this.eventbriteData.appKey);
                    data.append('client_secret', this.eventbriteData.clientSecret);

                    axios
                        .post(ajaxurl, data)
                        .then(response => {
                            if (response.data.success === true) {
                                window.location.href = response.data.link;
                            } else if (response.data.success === false) {
                                this.message.content = response.data.message;
                                this.message.type = 'error';
                                this.message.show = true;
                            }
                        })
                        .catch(error => {
                            console.log(error);
                        })
                        .then(() => {
                            this.updating = false;
                        })
                }
            },
            createSuperPass: function() {
              if (!this.superPassUpdating && this.superPassValid()) {
                  this.superPassUpdating = true;
                  let data = new FormData();
                  data.append('action', 'esp_create_super_pass');
                  data.append('name', this.superPass.name);
                  data.append('cost', this.superPass.cost);
                  data.append('events', this.superPass.events);

                  axios
                      .post(ajaxurl, data)
                      .then(response => {
                          if (response.data.success === true) {
                              this.creatingPass = false;
                              this.superPass = {
                                  events : [],
                                  name : "",
                                  cost : 0.00,
                              };
                              this.getSuperPasses();
                          } else {
                              this.message.show = true;
                              this.message.content = response.data.message; 
                              this.message.type = "warning";
                          }
                      })
                      .catch(error => {
                          console.log(error);
                      })
              }
            },
            deleteSuperPass: function(e) {
              if (!this.superPassUpdating) {
                  this.superPassUpdating = true;
                  var id = parseInt(e.target.value);
                  var data = new FormData();
                  data.append('action', 'esp_delete_super_pass');
                  data.append('id', id);

                  axios
                      .post(ajaxurl, data)
                      .then(response => {
                          if (response.data.success === true) {
                              this.getSuperPasses();
                          } else {
                              // Display error message
                          }
                      })
                      .catch(error => {
                         console.log(error);
                      })
              }
            },
            checkEventbriteData: function () {
                return this.eventbriteData.clientSecret && this.eventbriteData.appKey;
            },
            toggleEvent: function(e) {
                var event_id = e.target.value;
                if (!this.superPass.events.includes(event_id)) {
                    this.superPass.events.push(event_id);
                } else {
                    this.superPass.events.splice(this.superPass.events.indexOf(event_id), 1);
                }
            },
            superPassValid: function() {
                return this.superPass.name && this.superPass.cost && this.superPass.events.length > 0;
            }
        }
    });

    Vue.component('spinner', {
        template:
            '<div class="preloader-wrapper small active">\n' +
            '    <div class="spinner-layer spinner-green-only">\n' +
            '      <div class="circle-clipper left">\n' +
            '        <div class="circle"></div>\n' +
            '      </div><div class="gap-patch">\n' +
            '        <div class="circle"></div>\n' +
            '      </div><div class="circle-clipper right">\n' +
            '        <div class="circle"></div>\n' +
            '      </div>\n' +
            '    </div>\n' +
            '  </div>',
    });
});