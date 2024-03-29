<template>
    <div>
        <studip-dialog
            :title="$gettext('Moderierende einladen')"
            :confirmText="dialog_confirm_text"
            :confirmClass="dialog_confirm_class"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="480"
            height="530"
            @close="cancelModeratorGuest"
            @confirm="callbackHub"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default" @submit.prevent="generateModeratorGuestJoin">
                    <fieldset>
                        <label>
                            <span class="required">{{ $gettext('Zugangscode') }}</span>
                            <span>{{ $gettext('(%{ length } Zeichen)') | gettextinterpolate({length: password_length}) }}
                            </span>
                            <input type="text" :maxlength="password_length" :minlength="password_length"
                                :value="moderator_password" id="moderatorpassword" readonly
                                @keyup="passwordInputHandler($event)"
                                @change.once="generateModeratorGuestJoin($event)">
                            <StudipButton id="generate_code_btn" type="button" v-on:click="generateRandomCode($event)">
                                {{ $gettext('Neues Zugangscode generieren') }}
                            </StudipButton>
                        </label>
                        <label id="guest_link_label" v-if="moderator_access_link">
                            <span>{{ $gettext('Link') }}</span>
                            <StudipTooltipIcon :text="$gettext('Bitte geben sie diesen Link und Zugangscode dem Gast-Moderator.')"/>
                            <textarea ref="guestModeratorLinkArea" v-model="moderator_access_link" cols="30" rows="5"></textarea>
                        </label>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>

        <!-- Dialogs -->
        <studip-dialog
            v-if="showConfirmDialog"
            :title="showConfirmDialog.title"
            :question="showConfirmDialog.question"
            :alert="showConfirmDialog.alert"
            :message="showConfirmDialog.message"
            confirmClass="accept"
            closeClass="cancel"
            :height="showConfirmDialog.height !== undefined ? showConfirmDialog.height.toString() :  '180'"
            @confirm="performDialogConfirm(showConfirmDialog.confirm_callback, showConfirmDialog.confirm_callback_data)"
            @close="performDialogClose(showConfirmDialog.close_callback, showConfirmDialog.close_callback_data)"
        >
        </studip-dialog>
    </div>
</template>

<script>
import { confirm_dialog } from '@/common/confirm_dialog.mixins'
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;

import {
    ROOM_JOIN_MODERATOR,
    ROOM_MODERATOR_INVITATION_LINK
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "MeetingModeratorGuest",

    props: ['room'],

    mixins: [confirm_dialog],

    data() {
        return {
            modal_message: {},
            message: '',
            moderator_access_link: '',
            moderator_password: '',
            password_length: 5,
            password_changed: false,
            new_password: true,
            showConfirmDialog: false
        }
    },

    computed: {
        is_link_generated() {
            return this.moderator_access_link != '' ? true : false;
        },
        dialog_confirm_text() {
            let text = this.$gettext('Einladungslink erstellen');
            if (this.is_link_generated) {
                text = this.$gettext('In Zwischenablage kopieren');
                const html = document.querySelector('html');
                if (html.classList.contains('responsive-display') && html.classList.contains('size-tiny')) {
                    text = this.$gettext('Kopieren');
                }
            }
            return text;
        },
        dialog_confirm_class() {
            return !this.is_link_generated ? 'accept' : '';
        }
    },

    mounted() {
        this.$store.commit(ROOM_CLEAR);
        this.getModeratorGuestLink();
    },

    methods: {
        callbackHub() {
            if (this.is_link_generated) {
                this.copyModeratorLinkClipboard();
            } else {
                this.generateModeratorGuestJoin();
            }
        },

        getModeratorGuestLink() {
            this.$store.dispatch(ROOM_MODERATOR_INVITATION_LINK, this.room)
            .then(({ data }) => {
                if (data.password && (data.password != '' || data.password != null)) {
                    this.moderator_password = data.password;
                    this.new_password = false;
                    this.password_changed = false;
                }
            }).catch (({error}) => {});
        },

        generateModeratorGuestJoin(event) {
            if (event) {
                event.preventDefault();
            }

            this.modal_message = {};

            if (this.room && this.moderator_password && this.moderator_password.length == this.password_length) {
                this.room.moderator_password = this.moderator_password;

                if (this.new_password || !this.password_changed) {
                    this.performGenerateModeratorGuestJoinLink();
                    return;
                }

                this.showConfirmDialog = false;
                this.showConfirmDialog = {
                    title: 'Information',
                    question: this.$gettext('Durch das Generieren eines neuen Zugangscodes wären die vorherigen Links nicht mehr zugänglich. Möchten Sie wirklich einen neuen Zugangscode generieren?'),
                    confirm_callback: 'performGenerateModeratorGuestJoinLink',
                    close_callback: 'getModeratorGuestLink',
                    height: '220'
                }

            } else {
                var err_message = '';
                if (!this.moderator_password) {
                    err_message = gettextInterpolate($gettext('Ein Zugangscode mit %{ password_length } Zeichen ist erforderlich'), {password_length: this.password_length});
                } else if (this.moderator_password && this.moderator_password.length != this.password_length) {
                    err_message = gettextInterpolate($gettext('Der Zugangscode darf nur aus %{ password_length } Zeichen bestehen'), {password_length: this.password_length});
                }

                this.modal_message.type = 'error';
                this.modal_message.text = err_message;
            }
        },

        performGenerateModeratorGuestJoinLink() {
            this.$store.dispatch(ROOM_JOIN_MODERATOR, this.room)
            .then(({ data }) => {
                if (data.join_url != '') {
                    this.moderator_access_link = data.join_url;
                    this.password_changed = false;
                    this.new_password = false;
                }
                if (data.message) {
                    this.modal_message = data.message;
                }
            }).catch (({error}) => {
                this.$emit('cancel');
            });
        },

        generateRandomCode(event) {
            if (event) {
                event.preventDefault();
            }
            var random_code = Math.floor(10000 + Math.random() * 90000);
            this.moderator_password = random_code.toString();
            this.password_changed = true;
        },

        passwordInputHandler: _.debounce(function (e) {
            if (this.moderator_password.length == this.password_length) {
                this.password_changed = true;
            }
        }, 500),

        cancelModeratorGuest(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.commit(ROOM_CLEAR);
            this.moderator_access_link = '';
            this.$emit('cancel');
        },

        copyModeratorLinkClipboard(event) {
            if (event) {
                event.preventDefault();
            }

            let guest_moderator_link_element = this.$refs.guestModeratorLinkArea;

            if (this.moderator_access_link.trim()) {
                try {
                    guest_moderator_link_element.select();
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
        moderator_password() {
            this.moderator_access_link = '';
        },
    },
}
</script>
