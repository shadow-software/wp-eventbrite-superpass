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
                .then(() => {
                    this.ready = true;
                    setTimeout( () => {
                        this.setupMaterialUtils();
                        this.setupState();
                    }, 100)
                })
        },
        methods: {
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
            checkEventbriteData: function () {
                return this.eventbriteData.clientSecret && this.eventbriteData.appKey;
            },
            setupMaterialUtils: function () {
                console.log('hit')
                var elems = document.querySelectorAll('.collapsible');
                var options = {
                    'accordion' : true,
                    'inDuration' : 300,
                    'outDuration' : 300,
                }
                var instances = M.Collapsible.init(elems, options);
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