<template>
    <div>
        <MeetingDialog :title="$gettext(dialog.title ? dialog.title : 'Bitte bestätigen Sie die Aktion')" @close="cancel($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <span v-text="$gettext(dialog.text ? dialog.text : 'Sind Sie sicher, dass Sie das tun möchten?')"></span>
            </template>
            <template v-slot:buttons>
                <StudipButton v-if="dialog.isConfirm" icon="accept" type="button" v-on:click="accept($event)">
                    <translate>Ja</translate>
                </StudipButton>
                <StudipButton icon="cancel" type="button" v-on:click="cancel($event)">
                    <span v-if="dialog.isConfirm" v-text="$gettext('Nein')"></span>
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

    props: ['dialog'],

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
            this.$emit('accept', this.dialog.callback);
        }
    }
}
</script>
