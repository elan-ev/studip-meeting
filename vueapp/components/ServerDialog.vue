<template>
    <div v-if="visible">
        <MeetingDialog :title="$gettext('Serverkonfiguration')" @close="close">
            <template v-slot:content>
                <MessageBox v-if="dialog_message.text" :type="dialog_message.type" @hide="dialog_message.text = ''">
                    {{ dialog_message.text }}
                </MessageBox>

                <form class="default" style="position: relative" @submit="edit">
                    <div v-for="(value, key) in driver.config" :key="key">
                        <label v-if="value.name != 'roomsize-presets'" class="large">
                            {{ value.display_name }}
                            <StudipTooltipIcon v-if="Object.keys(value).includes('info')" :text="value['info']"></StudipTooltipIcon>
                            <select v-if="value.value && typeof value.value == 'object' && value.name == 'course_types'" :id="value.name" v-model.trim="server[driver_name][value.name]">
                                <option value="" v-translate>
                                    Alle
                                </option>
                                <template v-for="(cvalue, cindex) in value.value">
                                    <optgroup style="font-weight:bold;" :label="cvalue.name" :key="cindex">
                                        <option v-for="(svalue, sindex) in cvalue.subs" :key="sindex" :value="sindex">
                                            {{svalue}}
                                        </option>
                                    </optgroup>
                                </template>
                            </select>
                            <input v-else-if="typeof value.value == 'boolean'" type="checkbox" style="cursor: pointer;"
                                :true-value="true"
                                :false-value="false"
                                v-model="server[driver_name][value.name]">
                            <textarea v-else-if="value.name == 'description'" v-model="server[driver_name][value.name]"></textarea>
                            <input v-else class="size-l" :type="(value.name == 'maxParticipants') ? 'number' : 'text'" min="0" @change="(value.name == 'maxParticipants') ? reduceMins() : ''"
                                v-model="server[driver_name][value.name]"
                                :placeholder="value.value">
                        </label>
                        <ServerRoomSize v-else-if="value.name == 'roomsize-presets'" :roomsize_object="value" :this_server="server[driver_name]"/>
                    </div>
                </form>
            </template>


            <template v-slot:buttons>
                <StudipButton
                    icon="accept" @click="edit"
                    v-translate
                >
                    Übernehmen
                </StudipButton>

                <StudipButton
                    icon="cancel" @click="close"
                    v-translate
                >
                    Abbrechen
                </StudipButton>
            </template>
        </MeetingDialog>
    </div>
</template>
<script>
import StudipButton from "@/components/StudipButton";
import ServerRoomSize from "@/components/ServerRoomSize";
import MessageBox from "@/components/MessageBox";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";

import { dialog } from '@/common/dialog.mixins'

export default {
    name: 'ServerDialog',

    props: {
        DialogVisible: Boolean,
        server_object: Object,
        driver_name: String,
        driver: Object
    },

    mixins: [dialog],

    components: {
        StudipButton,
        ServerRoomSize,
        MessageBox,
        StudipTooltipIcon,
    },

    data() {
        return {
            visible: this.DialogVisible,
            server: this.server_object,
            dialog_message: {}
        };
    },

    mounted() {
        this.initDefaultValues();
    },

    methods: {
        close() {
            this.dialogClose();
            this.$emit('close');
        },

        edit() {
            if (this.validateForm()) {
                this.dialogClose();
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
        },

        initDefaultValues() {
            if (this.server[this.driver_name] && !Object.keys(this.server[this.driver_name]).includes('course_types')) {
                this.$set(this.server[this.driver_name], 'course_types', '');
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
