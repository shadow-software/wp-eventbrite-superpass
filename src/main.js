import Vue from 'vue';
import FrontEnd from '../src/FrontEnd.vue';

document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        render: h => h(FrontEnd),
    }).$mount('#esp-front-end');
});