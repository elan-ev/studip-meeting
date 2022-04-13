<template>
    <div>
        <MeetingDialog :title="$gettext('Moderator einladen')" @close="cancelModeratorGuest($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default" @submit.prevent="generateModeratorGuestJoin">
                    <fieldset>
                        <label>
                            <span class="required" v-translate>Zugangscode</span>
                            <span
                                v-translate="{
                                    length: password_length
                                }"
                            >(%{ length } Zeichen)</span>
                            <input type="text" :maxlength="password_length" :minlength="password_length"
                                :value="moderator_password" id="moderatorpassword" readonly
                                @keyup="passwordInputHandler($event)"
                                @change.once="generateModeratorGuestJoin($event)">
                            <StudipButton id="generate_code_btn" type="button" v-on:click="generateRandomCode($event)">
                                <translate>Neues Zugangscode generieren</translate>
                            </StudipButton>
                        </label>
                        <label id="guest_link_label" v-if="moderator_access_link">
                            <span v-translate>Link</span>
                            <StudipTooltipIcon :text="$gettext('Bitte geben sie diesen Link und Zugangscode dem Gast-Moderator.')"
                                :important="true"></StudipTooltipIcon>
                            <textarea ref="guestModeratorLinkArea" v-model="moderator_access_link" cols="30" rows="5"></textarea>
                        </label>
                    </fieldset>
                </form>
            </template>
            <template v-slot:buttons>
                <StudipButton id="copy_link_btn" type="button" v-on:click="copyModeratorLinkClipboard($event)" style="display: none;">
                    <translate>In Zwischenablage kopieren</translate>
                </StudipButton>
                <StudipButton id="generate_link_btn" icon="accept" type="button" v-on:click="generateModeratorGuestJoin($event)">
                    <translate>Einladungslink erstellen</translate>
                </StudipButton>

                <StudipButton icon="cancel" type="button" v-on:click="cancelModeratorGuest($event)">
                    <translate>Abbrechen</translate>
                </StudipButton>
            </template>
        </MeetingDialog>

        <!-- dialogs -->
        <MeetingMessageDialog v-if="showConfirmDialog"
            :message="showConfirmDialog"
            @accept="handleConfirmCallbacks"
            @abort="handleConfirmCallbacks"
        />
    </div>
</template>

<script>

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingMessageDialog from "@/components/MeetingMessageDialog";
import { dialog } from '@/common/dialog.mixins'

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

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingMessageDialog
    },

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

    mounted() {
        this.$store.commit(ROOM_CLEAR);
        this.getModeratorGuestLink();
    },

    methods: {
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
                    text: 'Durch das Generieren eines neuen Zugangscodes wären die vorherigen Links nicht mehr zugänglich. Möchten Sie wirklich einen neuen Zugangscode generieren?'.toLocaleString(),
                    type: 'question', //info, warning, question
                    isConfirm: true,
                    callback: 'performGenerateModeratorGuestJoinLink',
                    cancel_callback: 'getModeratorGuestLink',
                }
                
            } else {
                var err_message = '';
                if (!this.moderator_password) {
                    err_message = `Ein Zugangscode mit ${this.password_length} Zeichen ist erforderlich`.toLocaleString();
                } else if (this.moderator_password && this.moderator_password.length != this.password_length) {
                    err_message = `Der Zugangscode darf nur aus ${this.password_length} Zeichen bestehen`.toLocaleString();
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

        handleConfirmCallbacks(callback) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback]();
            }
        },

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
                        text: 'Der Link wurde in die Zwischenablage kopiert.'.toLocaleString()
                    }
                } catch(e) {
                    console.log(e);
                }
            }
        },

        showModeratorLinks(event) {
            if (event) {
                event.preventDefault();
            }
            alert('Here');
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
        moderator_access_link(newValue, oldValue) {
            if (newValue != '') {
                $('#copy_link_btn').show();
                $('#generate_link_btn').hide();
            } else {
                $('#copy_link_btn').hide();
                $('#generate_link_btn').show();
            }
        },
        moderator_password() {
            this.moderator_access_link = '';
        },
    },
}
</script>
