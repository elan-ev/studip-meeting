<template>
    <div>
        <MeetingDialog :title="$gettext('QR Code für Raum') + ' '  + room.name" @close="close($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default">
                    <fieldset>
                        <label>
                            <span v-translate>QR Code nur für persönlichen Gebrauch</span>
                            <StudipTooltipIcon :text="$gettext('Verwenden Sie diesen QR-Code nur für Ihren privaten Gebrauch und teilen Sie ihn nicht mit anderen!')"></StudipTooltipIcon>
                        </label>
                        <span class="required" v-translate>QR-Code scannen</span>
                        <StudipTooltipIcon :text="$gettext('Verwenden Sie Ihren QR-Code-Leser (z. B. Gerätekamera) und verwenden Sie die URL-Adresse, um auf dieses Meeting zuzugreifen')"></StudipTooltipIcon>
                        <label v-if="url">
                            <div style="text-align: center; margin: 20px 0;">
                                <QrcodeVue :value="url" level="H" :size="200"></QrcodeVue>
                            </div>
                        </label>
                        <label v-if="token">
                            <span class="required" v-translate>Zugangscode</span>
                            <StudipTooltipIcon :text="$gettext('Verwenden Sie den Code, um sich anzumelden')"></StudipTooltipIcon>
                            <p><strong>{{token}}</strong></p>
                        </label>
                    </fieldset>
                </form>
            </template>
            <template v-slot:buttons>
                <StudipButton type="button" v-on:click="generateQRCode">
                   <translate>QR-Code neu generieren</translate>
                </StudipButton>
                <StudipButton icon="cancel" type="button" v-on:click="close($event)">
                    <translate>Dialog schließen</translate>
                </StudipButton>
            </template>
        </MeetingDialog>
    </div>
</template>

<script>

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import { dialog } from '@/common/dialog.mixins';

import QrcodeVue from 'qrcode.vue';

import {
    ROOM_GENERATE_QR_CODE
} from "@/store/actions.type";

export default {
    name: "MeetingQRCodeDialog",

    props: ['room'],

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
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

        close(event) {
            if (event) {
                event.preventDefault();
            }
            this.$emit('cancel');
        },

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
