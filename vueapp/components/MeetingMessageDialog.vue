<template>
    <div>
        <MeetingDialog :parentId="parentDialogId" :minHeight="20" :confirmation="!message.type || message.type == 'question'" :title="$gettext(message.title ? message.title : 'Bitte bestätigen Sie die Aktion')" @close="abort($event)">
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
                <StudipButton id="cancel-button" icon="cancel" type="button" v-on:click="abort($event)">
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
                callback: null, //optional: null, name of the method to call if accepted,
                callback_data: {},
                cancel_callback: null, //optional: null, name of the method to call if canceled,
                cancel_callback_data:{},
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
        abort(event) {
            if (event) {
                event.preventDefault();
            }
            if (this.message.cancel_callback) {
                this.$emit('abort', this.message.cancel_callback, this.message.cancel_callback_data);
                // In order to prevent call to the cancel_callback, because it will be called on close event of the dialog anyways!
                this.clearCallbacks();
            }
            this.$emit('cancel');
        },
        accept(event) {
            if (event) {
                event.preventDefault();
            }
            this.$emit('accept', this.message.callback, this.message.callback_data);
            // In order to prevent call to the cancel_callback, because it will be called on close event of the dialog anyways!
            this.clearCallbacks();
            this.dialogClose();
        },
        clearCallbacks() {
            if (this.message.cancel_callback) {
                this.message.cancel_callback = null;
                if (this.message.cancel_callback_data) {
                    this.message.cancel_callback_data = {};
                }
            }
            if (this.message.callback) {
                this.message.callback = null;
                if (this.message.callback_data) {
                    this.message.callback_data = {};
                }
            }
        }
    }
}
</script>