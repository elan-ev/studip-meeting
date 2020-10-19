<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ 'Serverkonfiguration' | i18n }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <MessageBox v-if="dialog_message.text" :type="dialog_message.type" @hide="dialog_message.text = ''">
                            {{ dialog_message.text }}
                        </MessageBox>
                        <div v-for="(value, key) in driver.config" :key="key">
                            <label v-if="value.name != 'enable' && value.name != 'roomsize-presets'" class="large">
                                {{ value.display_name | i18n }}
                                <input class="size-l" :type="(value.name == 'maxParticipants') ? 'number' : 'text'" min="0" @change="(value.name == 'maxParticipants') ? reduceMins() : ''"
                                    v-model="server[driver_name][value.name]"
                                    :placeholder="value.value"> 
                            </label>
                            <ServerRoomSize v-else-if="value.name == 'roomsize-presets'" :roomsize_object="value" :this_server="server[driver_name]"/>
                        </div>
                    </section>

                    <footer class="modal-footer">
                        <slot name="footer">
                            <StudipButton
                                icon="accept"
                                @click="edit">
                                {{ 'Übernehmen' | i18n }}
                            </StudipButton>

                            <StudipButton
                                icon="cancel"
                                @click="close">
                                {{ "Abbrechen" | i18n }}
                            </StudipButton>
                        </slot>
                    </footer>
                </div>
            </div>
        </transition>
    </div>
</template>
<script>
import axios from 'axios';
import StudipButton from "@/components/StudipButton";
import ServerRoomSize from "@/components/ServerRoomSize";
import MessageBox from "@/components/MessageBox";


export default {
    name: 'ServerDialog',

    props: {
        DialogVisible: Boolean,
        server_object: Object,
        driver_name: String,
        driver: Object
    },

    components: {
        StudipButton,
        ServerRoomSize,
        MessageBox
    },

    data() {
        return {
            visible: this.DialogVisible,
            server: this.server_object,
            dialog_message: {}
        };
    },

    mounted() {},

    methods: {
        close() {
            this.$emit('close');
        },
        edit() {
            if (this.validateForm()) {
                this.$emit('edit', {
                    driver_name: this.driver_name,
                    server     : this.server
                });
            } else {
                this.dialog_message = {
                    type: 'error',
                    text: `Bei der Eingabe ist ein Fehler aufgetreten.`.toLocaleString()
                }
            }
        },
        validateForm() {
            var isValid = true;
            if (!this.validateRoomSizeNumberInputs()) {
                isValid = false;
            }
            return isValid;
        },
        validateRoomSizeNumberInputs() {
            var cmp = this.$children.find(child => { return child.$options.name === "ServerRoomSize"; });
            let validity = true;
            if (cmp) {
                for (const [index, element] of  Object.entries(cmp.$el.children)) {
                    var inputs = $(element).find('input[type="number"]');
                    if (inputs.length) {
                       for (const [indx, input_element] of  Object.entries(inputs)) {
                           if (input_element.tagName == "INPUT" && !input_element.checkValidity()) {
                               validity = false;
                           }
                       }
                    }
                }
            }
            return validity;
        },
        reduceMins() {
            if (this.server[this.driver_name]['maxParticipants'] && 
                 parseInt(this.server[this.driver_name]['maxParticipants']) > 0 &&
                 this.server[this.driver_name]['roomsize-presets']) {
                if (parseInt(this.server[this.driver_name]['maxParticipants']) > 0 && parseInt(this.server[this.driver_name]['maxParticipants']) < 3) {
                    this.$set(this.server_object[this.driver_name], "maxParticipants" , "3");

                }
                for (const [size_key, size_value] of Object.entries(this.server[this.driver_name]['roomsize-presets'])) {
                    if (Object.keys(size_value).includes('minParticipants') && parseInt(this.server[this.driver_name]['maxParticipants']) > 0 &&
                        parseInt(this.server[this.driver_name]['roomsize-presets'][size_key]['minParticipants']) > parseInt(this.server[this.driver_name]['maxParticipants'])) {
                        this.server[this.driver_name]['roomsize-presets'][size_key]['minParticipants'] = this.server[this.driver_name]['maxParticipants'];
                    }
                }
            }
        }
    },

    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            if (this.visible) {
                this.reduceMins();
            } else {
                this.dialog_message = {};
            }
        }
    }
};
</script>
