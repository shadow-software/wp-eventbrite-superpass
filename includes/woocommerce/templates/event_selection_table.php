<div id="esp-front-end">
    <div>
        <label for="superPassSelection">Pass:</label>
        <select id="superPassSelection">
            <option v-for="superPass in customerData.super_passes">{{ superPass.name }}</option>
        </select>
    </div>
    <div id="full-calendar"></div>
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
                </main>
                <footer class="modal__footer">
                    <button v-on:click="attendEvent" class="modal__btn modal__btn-primary">Attend This Event</button>
                    <button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Close</button>
                </footer>
            </div>
        </div>
    </div>
</div>