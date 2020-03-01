import Vue from 'vue';
import WorkShopPicker from '../src/WorkShopPicker.vue';

document.addEventListener('DOMContentLoaded', function () {
    new Vue({
        render: h => h(WorkShopPicker),
    }).$mount('#esp-front-end');
});