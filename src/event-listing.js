import Vue from 'vue';
import EventList from '../src/EventList.vue';

document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        render: h => h(EventList),
    }).$mount('#esp-event-list');
});