<template>
    <div>
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">

                    <header class="modal-header">
                        <slot name="header">
                            {{ `Gast einladen` | i18n }}
                            <span class="modal-close-button" @click="$emit('cancel')"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                            {{ modal_message.text }}
                        </MessageBox>

                        <form class="default" @submit.prevent="generateGuestJoin">
                            <fieldset>
                                <label>
                                    <span class="required">{{ "Gastname" | i18n }}</span>
                                    <StudipTooltipIcon :text="'Der Gast bekommt diesen Namen in der Besprechung zugewiesen.' | i18n"></StudipTooltipIcon>
                                    <input type="text" v-model.trim="guest_name" id="guestname" @change="generateGuestJoin($event)">
                                </label>

                                <label id="guest_link_label" v-if="guest_link">
                                    <span>{{ "Link" | i18n }}</span>
                                    <StudipTooltipIcon :text="'Bitte geben sie diesen Link dem Gast.' | i18n" :important="true"></StudipTooltipIcon>
                                    <textarea ref="guestLinkArea" v-model="guest_link" cols="30" rows="5"></textarea>
                                </label>
                            </fieldset>

                            <footer class="modal-footer">
                                <StudipButton type="button" v-on:click="copyGuestLinkClipboard($event)" v-if="guest_link">
                                    {{ "In Zwischenablage kopieren" | i18n}}
                                </StudipButton>
                                <StudipButton id="generate_link_btn" icon="accept" type="button" v-on:click="generateGuestJoin($event)" v-else>
                                    {{ "Einladungslink erstellen" | i18n }}
                                </StudipButton>

                                <StudipButton icon="cancel" type="button" v-on:click="cancelGuest($event)">
                                    {{ "Dialog schließen" | i18n}}
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
    ROOM_JOIN_GUEST
} from "@/store/actions.type";

import {
    ROOM_CLEAR
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
            message: '',
            guest_link: '',
            guest_name: ''
        }
    },

    computed: {
        ...mapGetters([
            'feedback', 'network_types'
        ])
    },

    mounted() {
        this.$store.commit(ROOM_CLEAR);
    },

    methods: {
        showGuestDialog(room) {
            this.$store.commit(ROOM_CLEAR);
            this.guest_link = '';
            this.modal_message.text = '';

            $('#guest-invitation-modal').data('room', room)
            .dialog({
                width: '50%',
                modal: true,
                title: 'Gast einladen'.toLocaleString()
            });
        },

        generateGuestJoin(event) {
            if (event) {
                event.preventDefault();
            }

            let view = this;

            if (this.room && this.guest_name) {
                this.room.guest_name = this.guest_name;
                this.$store.dispatch(ROOM_JOIN_GUEST, this.room)
                .then(({ data }) => {
                    if (data.join_url != '') {
                        view.guest_link = data.join_url;
                    }
                    if (data.message) {
                        this.modal_message = data.message;
                    }
                }).catch (({error}) => {
                    this.$emit('cancel');
                });
            }
        },

        cancelGuest(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.commit(ROOM_CLEAR);
            this.guest_link = '';
            this.$emit('cancel');
        },

        copyGuestLinkClipboard(event) {
            if (event) {
                event.preventDefault();
            }

            let guest_link_element = this.$refs.guestLinkArea;

            if (this.guest_link.trim()) {
                try {
                    guest_link_element.select();
                    document.execCommand("copy");
                    document.getSelection().removeAllRanges();
                    this.modal_message = {
                        type: 'success',
                        text: 'Der Link wurde in die Zwischenablage kopiert.'.toLocaleString()
                    }
                } catch(e) {
                    console.log(e);
                }
            }
        }
    }
}
</script>
