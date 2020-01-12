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
            ready: false,
            eventbriteData: {
                appKey: undefined,
                accessCode: undefined,
                clientSecret: undefined,
            }
        },
        mounted: function () {
            document.getElementById('esp-admin-app').style.display = "block";
            let data = new FormData();
            data.append('action', 'get_esp_settings');
            axios.post(ajaxurl, data)
                .then(response => (this.settings = response.data))
                .then(() => {this.ready = true})
        },
        methods: {
            eventbriteSetup: function () {
                if (!this.updating && this.checkEventbriteData()) {
                    this.updating = true;
                    let data = new FormData();
                    data.append('action', 'setup_esp_eventbrite_keys');
                    data.append('api_key', this.eventbriteData.appKey);
                    data.append('access_code', this.eventbriteData.accessCode);
                    data.append('client_secret', this.eventbriteData.accessCode);

                    axios
                        .post(ajaxurl, data)
                        .then(response => {
                            if (response.data.success === true) {
                                this.message.content = response.data.message;
                                this.message.type = "success";
                                this.message.show = true;
                                setTimeout(() => {
                                    this.message.show = false
                                }, 5000);
                            } else {

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
            checkEventbriteData: function () {
                return this.eventbriteData.accessCode && this.eventbriteData.clientSecret && this.eventbriteData.appKey;
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