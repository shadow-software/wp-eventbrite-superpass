<template>
    <div>
        <section v-if="showSuperPassPrompt" class="boldSection topSemiSpaced bottomSemiSpaced btDarkSkin gutter inherit"
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
                                        <a href="/my-account/superpass"
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
                <li v-for="date in dates"><span>{{date}}</span></li>
            </ul>
            <div class="tabPanes accordionPanes">
                <div class="tabPane" v-for="events in eventsByDate">
                    <div class="tabAccordionTitle on"><span>{{moment(events[0].start.utc, "dddd MMMM Do")}}</span></div>
                    <div class="tabAccordionContent" style="display: block;">
                        <div v-for="event in events">
                            <div>
                                {{event.name.text}}
                                <hr/>
                                <div v-html="event.description.html"></div>
                                <div>
                                    <div v-if="event.status === 'live'" v-on:click="showModal(event.id)"
                                         class="btBtn btBtn btnFilledStyle btnAccentColor btnSmall btnNormalWidth btnRightPosition btnNoIcon">
                                        <span class="btnInnerText">Buy Tickets</span>
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
</template>

<script>
    import MicroModal from 'micromodal';
    import moment from 'moment';

    export default {
        name: "EventList",
        data: () => ({
            events: esp_data.events,
            dates: [],
            eventsByDate: {},
            currentEventID: null,
            superPasses: esp_data.super_passes,
            customerData: esp_data.customer,
            showSuperPassPrompt: false,
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
            this.events.forEach(event => {
                let eDate = moment(event.start.utc).format("dddd MMMM Do");
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

            if (this.customerData.super_passes.length > 0) {
                let e = document.querySelector('#super-pass-purchase-prompt');
                if (e) {
                    e.style.display = "none";
                    this.showSuperPassPrompt = true;
                }
            }
        },
        methods: {
            moment: function (date, format) {
                return moment(date).format(format);
            },
            showModal: function (eventID) {
                if (esp_data.is_logged_in) {
                    this.currentEventID = eventID;
                    this.initEBWidget();
                    MicroModal.show('esp-modal');
                } else {
                    window.location.href = esp_data.redirect;
                }
            },
            initEBWidget: function () {
                // First clear all existing widgets.
                let e = document.querySelector("#eventbrite-widget-container");
                let child = e.lastElementChild;
                while (child) {
                    e.removeChild(child);
                    child = e.lastElementChild;
                }
                this.EBWidget = window.EBWidgets.createWidget({
                    // Required
                    widgetType: 'checkout',
                    eventId: this.currentEventID,
                    iframeContainerId: 'eventbrite-widget-container',

                    // Optional
                    iframeContainerHeight: 425,  // Widget height in pixels. Defaults to a minimum of 425px if not provided
                    onOrderComplete: function (e) {
                        console.log(e)
                    }  // Method called when an order has successfully completed
                });
            }
        }
    }

</script>

<style scoped>

</style>