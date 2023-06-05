<template>
    <div>
        <studip-dialog
            :title="$gettext('Teilnehmende einladen')"
            :confirmText="dialog_confirm_text"
            :confirmClass="dialog_confirm_class"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            @close="cancelGuest"
            @confirm="callbackHub"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default" @submit.prevent="generateGuestJoin">
                    <fieldset>
                        <label>
                            <span class="required" v-translate>Standard-Gästename</span>
                            <StudipTooltipIcon :text="$gettext('Sofern der Gast keinen Namen eingibt, wird dieser standardmäßig verwendet.')">
                            </StudipTooltipIcon>
                            <input type="text" v-model.trim="guest_name" id="guestname" @change="generateGuestJoin($event)">
                        </label>

                        <label id="guest_link_label" v-if="guest_link">
                            <span v-translate>Link</span>
                            <StudipTooltipIcon :text="$gettext('Bitte geben sie diesen Link dem Gast.')"
                                :important="true"></StudipTooltipIcon>
                            <textarea ref="guestLinkArea" v-model="guest_link" cols="30" rows="5"></textarea>
                        </label>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>
    </div>
</template>

<script>

import {
    ROOM_JOIN_GUEST,
    ROOM_INVITATION_LINK
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "MeetingGuest",

    props: ['room'],

    data() {
        return {
            modal_message: {},
            message: '',
            guest_link: '',
            guest_name: ''
        }
    },

    computed: {
        is_link_generated() {
            return this.guest_link != '' ? true : false;
        },
        dialog_confirm_text() {
            return this.is_link_generated ? this.$gettext('In Zwischenablage kopieren') : this.$gettext('Einladungslink erstellen');
        },
        dialog_confirm_class() {
            return !this.is_link_generated ? 'accept' : '';
        }
    },

    mounted() {
        this.$store.commit(ROOM_CLEAR);
        this.$store.dispatch(ROOM_INVITATION_LINK, this.room)
            .then(({ data }) => {
                if (data.default_name != '') {
                    this.guest_name = data.default_name;
                }
            }).catch (({error}) => {});
    },

    methods: {
        callbackHub() {
            if (this.is_link_generated) {
                this.copyGuestLinkClipboard();
            } else {
                this.generateGuestJoin();
            }
        },

        generateGuestJoin(event) {
            if (event) {
                event.preventDefault();
            }

            if (this.room && this.guest_name) {
                this.room.guest_name = this.guest_name;
                this.$store.dispatch(ROOM_JOIN_GUEST, this.room)
                .then(({ data }) => {
                    if (data.join_url != '') {
                        this.guest_link = data.join_url;
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
                        text: this.$gettext('Der Link wurde in die Zwischenablage kopiert.')
                    }
                } catch(e) {
                    console.log(e);
                }
            }
        },

        sonderzeichen(string) {
            const umlautMap = {
                '\u00dc': 'UE',
                '\u00c4': 'AE',
                '\u00d6': 'OE',
                '\u00fc': 'ue',
                '\u00e4': 'ae',
                '\u00f6': 'oe',
                '\u00df': 'ss',
            }
            return string.replace(/[\u00dc|\u00c4|\u00d6][a-z]/g, (a) => {
                    const big = umlautMap[a.slice(0, 1)];
                    return big.charAt(0) + big.charAt(1).toLowerCase() + a.slice(1);
                })
                .replace(new RegExp('['+Object.keys(umlautMap).join('|')+']',"g"),
                    (a) => umlautMap[a]
                );
        }
    },
    watch: {
        guest_name() {
            this.guest_link = '';
        }
    },
}
</script>
