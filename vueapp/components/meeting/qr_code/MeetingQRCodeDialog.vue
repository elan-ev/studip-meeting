<template>
    <div>
        <studip-dialog
            :title="$gettext('Persönlicher QR Code für') + ' '  + room.name"
            :confirmText="$gettext('QR-Code neu generieren')"
            confirmClass="accept"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="570"
            height="640"
            @close="$emit('cancel')"
            @confirm="generateQRCode"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default">
                    <fieldset>
                        <h5 v-translate>QR Code nur für persönlichen Gebrauch</h5>
                        <p v-translate>1Verwenden Sie diesen QR-Code nur für Ihren privaten Gebrauch und teilen Sie ihn nicht mit anderen!</p>
                    </fieldset>
                    <fieldset>
                        <label v-if="url">
                            <span class="required" v-translate>QR-Code scannen</span>
                            <StudipTooltipIcon :text="$gettext('Scannen Sie den QR-Code und verwenden Sie den Zugangscode, um mit einem anderen Gerät an dem Meeting teilzunehmen')"></StudipTooltipIcon>
                            <div style="text-align: center; margin: 20px 0;">
                                <QrcodeVue :value="url" level="H" :size="200"></QrcodeVue>
                            </div>
                        </label>
                        <label v-if="token">
                            <span class="required" v-translate>Zugangscode</span>
                            <StudipTooltipIcon :text="$gettext('Dieser Code wird benötigt, um sich in diesem Raum anzumelden')"></StudipTooltipIcon>
                            <div style="text-align: center; margin: 20px 0;">
                                <h2>{{token}}</h2>
                            </div>
                        </label>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>
    </div>
</template>

<script>

import QrcodeVue from 'qrcode.vue';

import {
    ROOM_GENERATE_QR_CODE
} from "@/store/actions.type";

export default {
    name: "MeetingQRCodeDialog",

    props: ['room'],

    components: {
        QrcodeVue
    },

    data() {
        return {
            modal_message: {},
            url: '',
            token: '',
        }
    },

    mounted() {
        this.generateQRCode();
    },

    methods: {
        generateQRCode() {
            if (this.room) {
                this.$store.dispatch(ROOM_GENERATE_QR_CODE, this.room)
                .then(({ data }) => {
                    if (data.qr_code) {
                        this.url = data.qr_code.url;
                        this.token = data.qr_code.token;
                    }
                    if (data.message) {
                        this.modal_message = data.message;
                    }
                }).catch (({error}) => {
                    console.log(error);
                    this.url = '';
                    this.token = '';
                });
            }
        }
    }
}
</script>
