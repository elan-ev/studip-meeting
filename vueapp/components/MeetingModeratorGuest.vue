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
                            <span class="required" v-translate>Standard-Moderatorsname</span>
                            <input type="text" v-model.trim="moderator_name" id="moderatorname" @change="generateModeratorGuestJoin($event)">
                        </label>
                        <label>
                            <span class="required" v-translate>Zugangscode</span>
                            <input type="text" v-model.trim="moderator_password" id="moderatorpassword" @change="generateModeratorGuestJoin($event)">
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
    </div>
</template>

<script>

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
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
        MessageBox
    },

    data() {
        return {
            modal_message: {},
            message: '',
            moderator_access_link: '',
            moderator_name: '',
            moderator_password: ''
        }
    },

    mounted() {
        this.$store.commit(ROOM_CLEAR);
        this.$store.dispatch(ROOM_MODERATOR_INVITATION_LINK, this.room)
          .then(({ data }) => {
            if (data.default_name != '') {
              this.moderator_name = data.default_name;
            }
            if (data.password != '') {
              this.moderator_password = data.password;
            }
        }).catch (({error}) => {});
    },

    methods: {
        generateModeratorGuestJoin(event) {
            if (event) {
                event.preventDefault();
            }

            if (this.room && this.moderator_name && this.moderator_password) {
                this.room.moderator_name = this.moderator_name;
                this.room.moderator_password = this.moderator_password;

                this.$store.dispatch(ROOM_JOIN_MODERATOR, this.room)
                .then(({ data }) => {
                    if (data.join_url != '') {
                        this.moderator_access_link = data.join_url;
                    }
                    if (data.message) {
                        this.modal_message = data.message;
                    }
                }).catch (({error}) => {
                    this.$emit('cancel');
                });
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
        moderator_name() {
            this.moderator_access_link = '';
        },
        moderator_password() {
            this.moderator_access_link = '';
        },
    },
}
</script>
