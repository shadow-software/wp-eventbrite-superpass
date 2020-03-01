<template>
    <div v-if="ready">
        <vue-cal
                :time-from="6 * 60"
                :time-to="22 * 60"
                :time-step="30"
                :events="calData"
                :onEventClick="handleEventClick"
                time-format="h:mm"
                defaultView="day"
                :selectedDate="startDate"
        >
        </vue-cal>
        <div class="modal micromodal-slide" id="esp-modal" aria-hidden="true">
            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="esp-modal-title" style="max-width: unset; width: 60vw;">
                    <header class="modal__header">
                        <h2 class="modal__title" id="esp-modal-title" style="display:none;">
                            {{modal.title}}
                        </h2>
                        <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                    </header>
                    <main class="modal__content" id="esp-modal-content">
                        <table>
                            <thead>
                                <tr>
                                    <th v-if="modal.image" style="width:30%;" rowspan="2">
                                        <img :src="modal.image" style="width:100%;height:auto;"/>
                                    </th>
                                    <th style="text-align: center; vertical-align: middle;">
                                        {{modal.title}}
                                    </th>
                                </tr>
                                <tr>
                                    <th style="text-align: center; vertical-align: middle;" colspan="2">
                                        <span>{{moment(this.currentWorkshop.start, "dddd MMMM Do")}}</span>
                                        <span>{{moment(this.currentWorkshop.start, "h:mm a")}} - {{moment(this.currentWorkshop.end, "h:mm a")}}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="2">
                                        <div v-html="modal.content"></div>
                                        <div v-if="modal.message" style="width:100%;" class="modal-message" v-html="modal.message"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </main>
                    <footer class="modal__footer" style="text-align: center;">
                        <button v-if="!alreadyAttending()" v-on:click="attendWorkShop" class="modal__btn modal__btn-primary">
                            <span v-if="!updating">Attend This Workshop</span>
                            <spinner v-if="updating"></spinner>
                        </button>
                        <button v-if="alreadyAttending()" v-on:click="attendWorkShop" class="modal__btn modal__btn-primary">
                            <span v-if="!updating">Leave This Workshop</span>
                            <spinner v-if="updating"></spinner>
                        </button>
                        <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Close</button>
                    </footer>
                </div>
            </div>
        </div>
    </div>
    <div v-else>
        <spinner :stroke="'#e73f51'" :width="'50px'" :height="'50px'"></spinner>
    </div>
</template>

<script>
    import VueCal from "vue-cal";
    import Spinner from "../src/components/Spinner.vue";
    import moment from "moment";
    import MicroModal from "micromodal";

    export default {
        name: "WorkShopPicker",
        components: {
            VueCal,
            Spinner,
        },
        data: () => ({
            workshops: [],
            calData: [],
            customerData: {},
            startDate: null,
            updating: false,
            ready: false,
            currentWorkshop: {},
            modal: {

            }
        }),
        mounted() {
            this.workshops = esp_data.workshops;
            if (this.workshops.length > 0) {
                let events = this.workshops.map( function(event) {
                    return {
                        "id": event.id,
                        "title": event.title,
                        "allDay": false,
                        "start": moment(event.date +  " " + event.start_time).format("YYYY-MM-DD HH:mm"),
                        "end": moment(event.date +  " " + event.end_time).format("YYYY-MM-DD HH:mm"),
                        "description": event.description,
                        "image": event.image ? event.image : false,
                    }
                });

                this.startDate = events[0].start;

                this.calData = events;
            }
            this.getData();
        },
        methods: {
            getData: function() {
                let data = new FormData();
                data.append('action', 'esp_get_customer');
                data.append('attendance', true);
                let ajaxurl = ajax_object.ajax_url;
                axios
                    .post(ajaxurl, data)
                    .then(response => {
                        this.customerData = response.data;
                        this.ready = true;
                    })
            },
            attendWorkShop: function() {
                let data = new FormData();
                data.append('action', 'esp_customer_attend_event');
                data.append('workshop', this.currentWorkshop.id );

                let ajaxurl = ajax_object.ajax_url;
                axios
                    .post(ajaxurl, data)
                    .then(response => {
                        console.log(response.data);
                    })
                    .catch(error => {
                        console.log(error);
                    })
            },
            handleEventClick: function(event) {
                this.modal = {
                    title: event.title,
                    content: event.description,
                    image: event.image,
                    id: event.id,
                }
                this.currentWorkshop = event;
                MicroModal.show('esp-modal');
            },
            moment: function(date, format) {
                return moment(date).format(format);
            },
            alreadyAttending: function() {
                if( this.customerData ) {
                    let found = this.customerData.attending.forEach(workshop => {
                        return this.currentWorkshop.id === workshop.id;
                    })
                    return found !== null;
                }
                return false;
            }
        }
    }
</script>

<style lang="scss">
    @import '~vue-cal/dist/vuecal.css';

    .vuecal__event {cursor: pointer;}
    .vuecal__title {background-color:#212944;}
    button.vuecal__arrow {background-color:#212944;}
    button.vuecal__arrow:hover {background-color:#373E56;}
    .modal-message {
        color: #a94442;
        background-color: #f2dede;
        border-color: #ebccd1;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    table {
        color: #333;
        font-family: Helvetica, Arial, sans-serif;
        border-collapse: collapse; border-spacing: 0;
    }

    td, th {
        border: 1px solid transparent; /* No more visible border */
        height: 30px;
        transition: all 0.3s;  /* Simple transition for hover effect */
    }

    th {
        background: #DFDFDF;  /* Darken header a bit */
        font-weight: bold;
    }

    td {
        background: #FAFAFA;
        text-align: center;
        padding: 1.5rem;
    }

    /* Cells in even rows (2,4,6...) are one color */
    tr:nth-child(even) td { background: #F1F1F1; }

    /* Cells in odd rows (1,3,5...) are another (excludes header cells)  */
    tr:nth-child(odd) td { background: #FEFEFE; }
</style>