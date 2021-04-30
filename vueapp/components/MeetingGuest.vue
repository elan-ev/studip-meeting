<template>
    <div>
        <MeetingDialog :title="$gettext('Gast einladen')" @close="cancelGuest($event)">
            <template v-slot:content>
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
            <template v-slot:buttons>
                <StudipButton id="copy_link_btn" type="button" v-on:click="copyGuestLinkClipboard($event)" style="display: none;">
                    <translate>In Zwischenablage kopieren</translate>
                </StudipButton>
                <StudipButton id="generate_link_btn" icon="accept" type="button" v-on:click="generateGuestJoin($event)">
                    <translate>Einladungslink erstellen</translate>
                </StudipButton>

                <StudipButton icon="cancel" type="button" v-on:click="cancelGuest($event)">
                    <translate>Dialog schließen</translate>
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
    ROOM_JOIN_GUEST,
    ROOM_INVITATION_LINK
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "MeetingGuest",

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
            guest_link: '',
            guest_name: ''
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
                        text: 'Der Link wurde in die Zwischenablage kopiert.'.toLocaleString()
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
        guest_link(newValue, oldValue) {
            if (newValue != '') {
                $('#copy_link_btn').show();
                $('#generate_link_btn').hide();
            } else {
                $('#copy_link_btn').hide();
                $('#generate_link_btn').show();
            }
        },
        guest_name() {
            this.guest_link = '';
        }
    },
}
</script>
