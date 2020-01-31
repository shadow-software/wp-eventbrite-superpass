<template>
    <div>
        <div>
            <label for="superPassSelection">Pass:</label>
            <select v-on:change="changeSuperPass" id="superPassSelection">
                <option v-for="superPass in customerData.super_passes" :value="superPass.id">{{ superPass.name }}</option>
            </select>
        </div>
        <h3>Events you're attending:</h3>
        <div style="width:100%;">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="attending in extendedAttending" v-if="attending.super_pass_id === superPass.id">
                        <td>
                            <a :href="attending.url" target="_blank">{{ attending.name.text }}</a>
                        </td>
                        <td>{{ moment(attending.start.local).format("LLL") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <vue-cal
            :time-from="6 * 60"
            :time-to="22 * 60"
            :time-step="30"
            :events="events"
            :onEventClick="handleEventClick"
            time-format="h:mm"
            defaultView="day"
            :selectedDate="startDate"
        >
        </vue-cal>
        <div class="modal micromodal-slide" id="esp-modal" aria-hidden="true">
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="esp-modal-title">
                    <header class="modal__header">
                        <h2 class="modal__title" id="esp-modal-title">
                            {{modal.title}}
                        </h2>
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                    </header>
                    <main class="modal__content" id="esp-modal-content">
                        <div v-if="modal.image" style="width:100%;">
                            <img :src="modal.image" style="width:100%;height:auto;"/>
                        </div>
                        <div v-html="modal.content"></div>
                        <div style="width:100%;text-align:right;">
                            <a :href="modal.url" target="_blank">View full details >></a>
                        </div>
                        <div v-if="modal.message" style="width:100%;" class="modal-message">
                            {{modal.message}}
                        </div>
                    </main>
                    <footer class="modal__footer">
                        <button v-on:click="attendEvent" class="modal__btn modal__btn-primary">
                            <span v-if="!updating">Attend This Event</span>
                            <spinner v-if="updating"></spinner>
                        </button>
                        <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Close</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VueCal from 'vue-cal';
import Spinner from "../src/components/Spinner.vue";
import MicroModal from 'micromodal';
import moment from 'moment';

export default {
    components: {
        VueCal,
        Spinner,
    },
    data: () => ({
        extendedAttending: esp_data.attending_events,
        events: [],
        superPass: { id: null },
        startDate: '',
        customerData: esp_data.customer_data,
        currentEvent: {},
        modal: {

        },
        updating: false,
    }),
    mounted() {
        MicroModal.init({
            onShow: modal => console.info(`${modal.id} is shown`), // [1]
            onClose: modal => console.info(`${modal.id} is hidden`), // [2]
            openTrigger: 'data-custom-open', // [3]
            closeTrigger: 'data-custom-close', // [4]
            disableScroll: true, // [5]
            disableFocus: false, // [6]
            awaitOpenAnimation: false, // [7]
            awaitCloseAnimation: false, // [8]
            debugMode: true // [9]
        });
        this.superPass = this.customerData.super_passes[0];
        this.updateEvents();
    },
    methods: {
        updateEvents: function(){
            var events = this.superPass.events.map( function(event) {
                return {
                    "id": event.id,
                    "title": event.name.text,
                    "allDay": false,
                    "start": moment(event.start.local).format("YYYY-MM-DD HH:mm"),
                    "end": moment(event.end.local).format("YYYY-MM-DD HH:mm"),
                    "description": event.description.html,
                    "image": event.logo ? event.logo.original.url : false,
                    "url": event.url,
                }
            });

            this.startDate = events[0].start;

            this.events = events;
        },
        moment: function() {
          return moment();
        },
        handleEventClick: function(event) {
            this.modal = {
                title: event.title,
                content: event.description,
                image: event.image,
                url: event.url
            }
            this.currentEvent = event;
            MicroModal.show('esp-modal');
        },
        attendEvent: function() {
            if (!this.updating) {
                this.updating = true;
                let data = new FormData();
                data.append("action", "esp_customer_attend_event");
                data.append('event_id', this.currentEvent.id);
                data.append('super_pass_id', this.superPass.id);
                let ajaxurl = ajax_object.ajax_url;
                axios
                    .post(ajaxurl, data)
                    .then(response => {
                        if (response.data.success === true) {
                            window.location.href = esp_data.eb_checkout_url + '?event_id=' + response.data.result.event_id + '&attendance=' + response.data.result.id;
                        } else {
                            this.updating = false;
                            this.modal.message = response.data.message;
                        }
                    })
            }
        },
        changeSuperPass: function(e) {
            let id = e.target.value;
            let superPass = this.customerData.super_passes.find(function(superPass) {
                return superPass.id == id;
            })
            this.superPass = superPass;
            this.updateEvents();
        }
    }
}
</script>

<style lang='scss'>
@import '~vue-cal/dist/vuecal.css';

.vuecal__event {cursor: pointer;}
.modal-message {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}
</style>