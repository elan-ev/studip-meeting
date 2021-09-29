<template>
    <div>
        <MeetingDialog :parentId="parentDialogId" :minWidth="750" :title="$gettext('Vorlagenvorschau')" @close="close">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <pdf v-if="preview" :src="preview" :page="1" @error="errorCaptured" @progress="loadedRatio = $event">
                    <template slot="loading">
                    loading content here...
                    </template>
                </pdf>
            </template>
            <template v-slot:buttons>
                <StudipButton icon="cancel" type="button"
                    v-on:click="close"
                    class="ui-button ui-corner-all ui-widget"
                    v-translate
                >
                    Abbrechen
                </StudipButton>
            </template>
        </MeetingDialog>
    </div>
</template>

<script>
import pdf from 'vue-pdf'

import StudipButton from "@/components/StudipButton";
import MessageBox from "@/components/MessageBox";

import { dialog } from '@/common/dialog.mixins'

export default {
    name: "DefaultSlidePreviewDialog",

    mixins: [dialog],

    components: {
        StudipButton,
        MessageBox,
        pdf
    },

    props: {
        preview: {
            type: String,
            required: true
        },
    },

    data() {
        return {
            modal_message: {},
            message: '',
        }
    },

    methods: {
        close() {
            this.$emit('cancel');
        },
        errorCaptured(err) {
            console.log(err);
            return false
        },
    },
}
</script>