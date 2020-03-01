<template>
    <div v-if="ready">
        <section v-if="purchasedPass && is_logged_in" class="boldSection topSemiSpaced bottomSemiSpaced btDarkSkin gutter inherit"
                 style="background-color:#212944;">
            <div class="port">
                <div class="boldCell">
                    <div class="boldCellInner">
                        <div class="boldRow ">
                            <div class="boldRowInner">
                                <div class="rowItem col-md-12 col-ms-12  btTextCenter inherit" data-width="12">
                                    <div class="rowItemContent">
                                        <div class="btText"><h4 style="text-align: center;">Ready to plan your
                                            conference agenda?</h4>
                                        </div>
                                        <a href="/my-account/manage-agenda"
                                           class="btBtn btBtn btnOutlineStyle btnAccentColor btnSmall btnNormalWidth btnRightPosition btnIco"><span
                                                class="btnInnerText">Plan your agenda</span><span class="btIco "><span
                                                data-ico-fa="" class="btIcoHolder"></span></span></a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="btTabs tabsVertical" data-open-first="yes" data-open-one="no">
            <ul class="tabsHeader">
                <li>
                    <span v-if="moment(superPass.event.start.utc, 'DD/MM/YYYY') === moment(superPass.event.start.utc, 'DD/MM/YYYY')">{{moment(superPass.event.start.utc, "dddd MMMM Do YYYY")}}</span>
                    <span v-else >{{moment(superPass.event.start.utc, "dddd MMMM Do")}} - {{moment(superPass.event.end.utc, "dddd MMMM Do YYYY")}}</span>
                </li>
            </ul>
            <div class="tabPanes accordionPanes">
                <div class="tabPane">
                    <div class="tabAccordionTitle on">
                        <span v-if="moment(superPass.event.start.utc, 'DD/MM/YYYY') === moment(superPass.event.start.utc, 'DD/MM/YYYY')">{{moment(superPass.event.start.utc, "dddd MMMM Do YYYY")}}  <b>{{superPass.event.name.text}}</b></span>
                        <span v-else >{{moment(superPass.event.start.utc, "dddd MMMM Do")}} - {{moment(superPass.event.end.utc, "dddd MMMM Do YYYY")}}  <b>{{superPass.event.name.text}}</b></span>
                    </div>
                    <div class="tabAccordionContent" style="display: block;">
                        <div>
                            <div>
                                {{superPass.event.name.text}}
                                <hr/>
                                <div v-html="superPass.event.description.html"></div>
                                <div>
                                    <div v-if="!purchasedPass" v-on:click="showModal(superPass.event.id)"
                                         class="btBtn btBtn btnFilledStyle btnAccentColor btnSmall btnNormalWidth btnRightPosition btnNoIcon">
                                        <span class="btnInnerText">Buy Tickets</span>
                                    </div>
                                    <div v-else>
                                        <span class="btnInnerText">Ticket Purchased</span>
                                    </div>
                                </div>
                                <br/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <section v-if="!purchasedPass" class="boldSection topSemiSpaced bottomSemiSpaced btDarkSkin gutter inherit"
                 style="background-color:#212944;">
            <div class="port">
                <div class="boldCell">
                    <div class="boldCellInner">
                        <div class="boldRow ">
                            <div class="boldRowInner">
                                <div class="rowItem col-md-12 col-ms-12  btTextCenter inherit" data-width="12">
                                    <div class="rowItemContent">
                                        <div class="btText">
                                            <h4 style="text-align: center;">
                                                Purchase your Conference ticket to gain access to the following add-on events!
                                            </h4>
                                        </div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="btTabs tabsVertical" data-open-first="yes" data-open-one="no">
            <ul class="tabsHeader">
                <li v-for="date in dates"><span>{{date}}</span></li>
            </ul>
            <div class="tabPanes accordionPanes">
                <div class="tabPane" v-for="events in eventsByDate">
                    <div class="tabAccordionTitle on"><span>{{moment(events[0].start.utc, "dddd MMMM Do YYYY")}}</span></div>
                    <div class="tabAccordionContent" style="display: block;">
                        <div v-for="event in events">
                            <div>
                                {{event.name.text}}
                                <hr/>
                                <div v-html="event.description.html"></div>
                                <div>
                                    <div v-if="event.status === 'live' && canPurchase(event.id) && purchasedPass && is_logged_in" v-on:click="showModal(event.id)"
                                         class="btBtn btBtn btnFilledStyle btnAccentColor btnSmall btnNormalWidth btnRightPosition btnNoIcon">
                                        <span class="btnInnerText">Buy Tickets</span>
                                    </div>
                                    <div v-else-if="!canPurchase(event.id) && purchasedPass && is_logged_in">
                                        <span class="btnInnerText">Ticket Purchased</span>
                                    </div>
                                </div>
                                <br/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                        <div id="eventbrite-widget-container"></div>
                        <div id="esp-overlay" class="esp-overlay"></div>
                    </main>
                    <footer class="modal__footer">
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
    import Spinner from "../src/components/Spinner.vue";
    import MicroModal from 'micromodal';
    import moment from 'moment';

    export default {
        name: "EventList",
        components: {
            Spinner
        },
        data: () => ({
            callsComplete: 0,
            events: [],
            dates: [],
            eventsByDate: {},
            currentEventID: null,
            lastCompleted: null,
            is_logged_in: esp_data.is_logged_in,
            superPass: [],
            customerData: [],
            showSuperPassPrompt: false,
            purchasedPass: false,
            ready: false,
            modal: {
                title: "Eventbrite Checkout"
            },
            EBWidget: {},
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
            this.getData();
        },
        methods: {
            getData: function() {
                let ajaxurl = esp_data.ajax_url;

                let data = new FormData();
                data.append('action', 'esp_get_customer');
                axios
                    .post(ajaxurl, data)
                    .then(response => {
                        this.callsComplete ++;
                        this.customerData = response.data;
                        if ( this.callsComplete > 1 ) {
                            this.setup();
                        }
                    })

                data = new FormData();
                data.append('action', 'esp_get_main_pass_data');

                axios
                    .post(ajaxurl, data)
                    .then(response => {
                        this.callsComplete ++;
                        this.superPass = response.data;
                        this.events = this.superPass.add_on_events;
                        if (this.callsComplete > 1) {
                            this.setup();
                        }
                    })
            },
            setup: function() {
                this.events.forEach(event => {
                    let eDate = moment(event.start.utc).format("dddd MMMM Do YYYY");
                    if (!this.eventsByDate[eDate]) {
                        this.eventsByDate[eDate] = [];
                    }
                    this.eventsByDate[eDate].push(event);

                    let found = this.dates.find(date => {
                        return eDate === date;
                    });

                    if (!found) {
                        let date = moment(event.start.utc).format("dddd MMMM Do");
                        this.dates.push(date);
                    }
                });
                this.checkPurchasedPass();
                this.ready = true;
            },
            moment: function (date, format) {
                return moment(date).format(format);
            },
            showModal: function (eventID) {
                if (esp_data.is_logged_in) {
                    this.currentEventID = eventID;
                    this.initEBWidget();
                    MicroModal.show('esp-modal');
                } else {
                    window.location.href = esp_data.redirect + '?register';
                }
            },
            registerOrder: function ( order_id ) {
                let data = new FormData();
                data.append('action', 'esp_register_eb_order');
                data.append('event_id', this.lastCompleted);
                data.append('order_id', order_id);

                let ajaxurl = esp_data.ajax_url;

                axios
                    .post(ajaxurl, data)
                    .then( response => {
                       this.customerData = response.data.customer;
                       this.checkPurchasedPass();
                    });
            },
            checkPurchasedPass: function(){
                if( ! this.is_logged_in ) {
                    return false;
                }
                let purchasedPass = this.customerData.eventbrite_orders.find( obj => {
                    return obj.event_id[0] === this.superPass.event.id;
                });

                this.purchasedPass = purchasedPass !== undefined;
            },
            canPurchase: function(event_id) {
                if ( ! this.is_logged_in ) {
                    return false;
                }
                let alreadyPurchased = this.customerData.eventbrite_orders.find( obj => {
                    return obj.event_id[0] === event_id;
                })

                return alreadyPurchased === undefined;
            },
            initEBWidget: function () {
                // First clear all existing widgets.
                let e = document.querySelector("#eventbrite-widget-container");
                let child = e.lastElementChild;
                while (child) {
                    e.removeChild(child);
                    child = e.lastElementChild;
                }
                this.lastCompleted = this.currentEventID;
                this.EBWidget = window.EBWidgets.createWidget({
                    // Required
                    widgetType: 'checkout',
                    eventId: this.currentEventID,
                    iframeContainerId: 'eventbrite-widget-container',

                    // Optional
                    iframeContainerHeight: 425,  // Widget height in pixels. Defaults to a minimum of 425px if not provided
                    onOrderComplete: function (obj) {
                        this.registerOrder(obj.orderId);
                    }.bind(this)  // Method called when an order has successfully completed
                });
            }
        }
    }

</script>

<style scoped>

</style>