<template>
    <div>
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">

                    <header class="modal-header">
                        <slot name="header">
                            {{ `Feedback für Raum ${room.name}` | i18n }}
                            <span class="modal-close-button" @click="$emit('cancel')"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                            {{ modal_message.text }}
                        </MessageBox>

                        <form class="default" @submit.prevent="feedbackFormSubmit"
                            style="max-width: 50em;"
                        >
                            <fieldset>
                                <legend>
                                    {{ 'Beschreibung' | i18n }}
                                </legend>
                                 <label class="col-6">
                                    <textarea ref="feedbackDescription" v-model="feedback['description']" cols="30" rows="5"></textarea>
                                </label>
                            </fieldset>
                            <fieldset>
                                <legend>
                                    {{ 'Feedback Informationen' | i18n }}
                                </legend>
                                <label class="col-3">
                                    <span >{{ "Browser-Name" | i18n }}</span>
                                    <input type="text" v-model.trim="feedback['browser_name']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Browser-Version" | i18n }}</span>
                                    <input type="text" v-model.trim="feedback['browser_version']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Download-Geschw. (Mbps)" | i18n }}</span>
                                    <input type="number" min="1" v-model.trim="feedback['download_speed']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Upload-Geschw. (Mbps)" | i18n }}</span>
                                    <input type="number" min="1" v-model.trim="feedback['upload_speed']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Netzwerk-Typ" | i18n }}</span>
                                    <select id="network-type" v-model="feedback['network_type']">
                                        <option v-for="(nt_value, nt_name) in network_types" :key="nt_name"
                                                :value="nt_name">
                                                {{ nt_value }}
                                        </option>
                                    </select>
                                </label>
                                <label class="col-3">
                                    <span >{{ "Betriebssystem (OS)" | i18n }}</span>
                                    <input type="text" v-model.trim="feedback['os_name']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Prozessortyp" | i18n }}</span>
                                    <input type="text" v-model.trim="feedback['cpu_type']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Alter des Rechners" | i18n }}</span>
                                    <input type="text" v-model.number="feedback['cpu_old']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "Anzahl der CPU-Kerne" | i18n }}</span>
                                    <input type="number" min="1" max="1000" v-model.number="feedback['cpu_num']">
                                </label>
                                <label class="col-3">
                                    <span >{{ "RAM (Hauptspeicher) GB" | i18n }}</span>
                                    <input type="number"  min="1" max="1000" v-model.number="feedback['ram']">
                                </label>
                            </fieldset>


                            <footer class="modal-footer">
                                <StudipButton icon="accept" type="button" v-on:click="sumbitFeedback($event)" class="ui-button ui-corner-all ui-widget">
                                    {{ "Einsenden" | i18n}}
                                </StudipButton>
                                <StudipButton icon="cancel" type="button" v-on:click="cancelFeedback($event)" class="ui-button ui-corner-all ui-widget">
                                    {{ "Abbrechen" | i18n}}
                                </StudipButton>
                            </footer>
                        </form>
                    </section>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";

import {
    FEEDBACK_SUBMIT
} from "@/store/actions.type";

import {
    FEEDBACK_CLEAR, FEEDBACK_INIT
} from "@/store/mutations.type";

export default {
    name: "MeetingFeedback",

    props: ['room'],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox
    },

    data() {
        return {
            modal_message: {},
            message: ''
        }
    },

    computed: {
        ...mapGetters([
            'feedback', 'network_types'
        ])
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
                    this.$store.commit(FEEDBACK_CLEAR);
                    if (this.message.type == 'error') {
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        this.$emit('done', { message: this.message });
                    }
                }).catch (({error}) => {
                    this.$emit('cancel');
                });
            } else {
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , `Beschreibung darf nicht leer sein`.toLocaleString());
            }

        },
        cancelFeedback() {
            this.$store.commit(FEEDBACK_CLEAR);
            this.$emit('cancel');
        },
    }
}
</script>
