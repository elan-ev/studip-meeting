<template>
    <div>
        <studip-dialog
            :title="$gettext('Problemmeldung für Raum') + ' ' +  room.name"
            :confirmText="$gettext('Einsenden')"
            confirmClass="accept"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="500"
            :height="dialog_height"
            @close="cancelFeedback"
            @confirm="sumbitFeedback"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent="feedbackFormSubmit"
                    style="max-width: 50em;"
                >
                    <fieldset>
                        <legend>
                            {{ $gettext('Beschreibung') }}
                        </legend>
                            <label class="col-6">
                            <span>{{ $gettext('Bitte beschreiben Sie das aufgetretene Problem') }}</span>
                            <textarea ref="feedbackDescription" v-model="feedback['description']" cols="30" rows="5"></textarea>
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>
                            {{ $gettext('Technische Informationen') }}
                        </legend>
                        <label class="col-3">
                            <span>{{ $gettext('Browser-Name') }}</span>
                            <input type="text" v-model.trim="feedback['browser_name']"
                                :disabled="feedback['browser_name'] != ''" :readonly="feedback['browser_name'] != ''">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Browser-Version') }}</span>
                            <input type="text" v-model.trim="feedback['browser_version']"
                                :disabled="feedback['browser_version'] != ''" :readonly="feedback['browser_version'] != ''">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Download-Geschw. (Mbps)') }}'</span>
                            <input type="number" min="1" v-model.trim="feedback['download_speed']">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Upload-Geschw. (Mbps)') }}</span>
                            <input type="number" min="1" v-model.trim="feedback['upload_speed']">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Netzwerk-Typ') }}</span>
                            <select id="network-type" v-model="feedback['network_type']">
                                <option v-for="(nt_value, nt_name) in network_types_complied" :key="nt_name"
                                        :value="nt_name">
                                        <span>{{ nt_value }}</span>
                                </option>
                            </select>
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Betriebssystem (OS)') }}</span>
                            <input type="text" v-model.trim="feedback['os_name']"
                                :disabled="feedback['os_name'] != ''" :readonly="feedback['os_name'] != ''">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Prozessortyp') }}</span>
                            <input type="text" v-model.trim="feedback['cpu_type']">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Alter des Rechners') }}</span>
                            <input type="text" v-model.number="feedback['cpu_old']">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('Anzahl der CPU-Kerne') }}</span>
                            <input type="number" min="1" max="1000" v-model.number="feedback['cpu_num']">
                        </label>
                        <label class="col-3">
                            <span>{{ $gettext('RAM (Hauptspeicher) GB') }}</span>
                            <input type="number"  min="1" max="1000" v-model.number="feedback['ram']">
                        </label>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import {
    FEEDBACK_SUBMIT
} from "@/store/actions.type";

import {
    FEEDBACK_CLEAR, FEEDBACK_INIT
} from "@/store/mutations.type";

export default {
    name: "MeetingFeedback",

    props: ['room'],

    data() {
        return {
            modal_message: {},
            message: ''
        }
    },

    computed: {
        ...mapGetters([
            'feedback', 'network_types'
        ]),

        network_types_complied() {
            let network_types_complied = {};
            for (const key in this.network_types) {
                network_types_complied[key] = this.$gettext(this.network_types[key]);
            }
            return network_types_complied;
        },

        dialog_height() {
            return (window.innerHeight * 0.8).toString();
        }
    },

    mounted() {
        this.$store.commit(FEEDBACK_INIT, this.room.id);
    },

    methods: {
        feedbackFormSubmit(event) {
            if (event.key == 'Enter' && $(event.target).is('input')) {
                this.sumbitFeedback(event);
            }
        },

        sumbitFeedback(event) {
            if (event) {
                event.preventDefault();
            }
            if ( this.feedback.description ) {
                this.modal_message = {};
                this.$store.dispatch(FEEDBACK_SUBMIT, this.feedback)
                .then(({ data }) => {
                    this.message = data.message;
                    if (this.message.type == 'error') {
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        this.$emit('done', { message: this.message });
                        this.$store.commit(FEEDBACK_CLEAR);
                    }
                }).catch (({error}) => {
                    this.$emit('cancel');
                });
            } else {
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , this.$gettext(`Beschreibung darf nicht leer sein`));
            }

        },
        cancelFeedback() {
            this.$store.commit(FEEDBACK_CLEAR);
            this.$emit('cancel');
        },
    }
}
</script>
