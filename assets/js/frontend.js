"use strict";

document.addEventListener('DOMContentLoaded', (e) => {

    Vue.config.devtools = true;
    var ajaxurl = ajax_object.ajax_url;

    var ESP_FRONTEND_JS = new Vue({
        el: "#esp-front-end",
        data: {
            settings : {},
            updating : false,
            customer_data : esp_data.customer_data,
        },
        mounted: function() {

        },
        methods: {

        }
    });
});