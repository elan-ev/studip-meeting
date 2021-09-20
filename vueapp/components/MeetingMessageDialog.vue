<template>
    <div>
        <MeetingDialog :minHeight="20" :confirmation="!message.type || message.type == 'question'" :title="$gettext(message.title ? message.title : 'Bitte bestätigen Sie die Aktion')" @close="cancel($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <div class="meeting-confirmation">
                    <template v-if="message.type">
                        <StudipIcon v-if="message.type == 'info'" icon="info-circle-full" role="clickable" size="32"></StudipIcon>
                        <StudipIcon v-else-if="message.type == 'warning'" icon="exclaim-circle-full" role="status-red" size="32"></StudipIcon>
                    </template>
                    <span v-text="$gettext(message.text ? message.text : 'Sind Sie sicher, dass Sie das tun möchten?')"></span>
                </div>
            </template>
            <template v-slot:buttons>
                <StudipButton v-if="message.isConfirm" id="accept-button" icon="accept" type="button" v-on:click="accept($event)">
                    <translate>Ja</translate>
                </StudipButton>
                <StudipButton id="cancel-button" icon="cancel" type="button" v-on:click="cancel($event)">
                    <span v-if="message.isConfirm" v-text="$gettext('Nein')"></span>
                    <span v-else v-text="$gettext('Dialog schließen')"></span>
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
import { dialog } from '@/common/dialog.mixins'

export default {
    name: "MeetingMessageDialog",

    props: {
        message: {
            type: Object,
            required: true,
            /* default: { // Possible values
                title: 'Bitte bestätigen Sie die Aktion'.toLocaleString(),
                text: 'Sind Sie sicher, dass Sie das tun möchten?'.toLocaleString(),
                type: 'info', //info, warning, question
                isConfirm: false,//optional: true/false
                callback: null, //optional: null, name of the method to call if accepted
            } */
        }
    },

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox
    },

    data() {
        return {
            modal_message: {},
        }
    },

    mounted() {
        $('.ui-dialog-titlebar-close').blur();
    },

    methods: {
        cancel(event) {
            if (event) {
                event.preventDefault();
            }
            this.$emit('cancel');
        },
        accept(event) {
            if (event) {
                event.preventDefault();
            }
            this.dialogClose();
            this.$emit('accept', this.message.callback);
        }
    }
}
</script>