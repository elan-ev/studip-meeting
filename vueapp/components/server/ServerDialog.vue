<template>
    <div v-if="visible">
        <studip-dialog
            :title="$gettext('Serverkonfiguration')"
            :confirmText="$gettext('Übernehmen')"
            confirmClass="accept"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="500"
            :height="dialog_height"
            @close="close"
            @confirm="edit"
        >
            <template v-slot:dialogContent>
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
                                :checked="server[driver_name][value.name]"
                                v-model="server[driver_name][value.name]">
                            <textarea v-else-if="value.name == 'description'" v-model="server[driver_name][value.name]"></textarea>
                            <template v-else-if="value.attr && value.attr == 'password'">
                                <div class="form-password-input" @click.prevent="togglePasswordText($event, value.name)">
                                    <input type="password" :ref="value.name" v-model="server[driver_name][value.name]">
                                    <StudipIcon class="overlay-input-icon" icon="visibility-visible"
                                        role="clickable" size="16"></StudipIcon>
                                </div>
                            </template>
                            <input v-else class="size-l" :type="(value.name == 'maxParticipants') ? 'number' : 'text'" min="0" @change="(value.name == 'maxParticipants') ? reduceMins() : ''"
                                v-model="server[driver_name][value.name]"
                                :placeholder="value.value">
                        </label>
                        <ServerRoomSize v-else-if="value.name == 'roomsize-presets'" :roomsize_object="value" :this_server="server[driver_name]"/>
                    </div>
                </form>
            </template>
        </studip-dialog>
    </div>
</template>
<script>
import ServerRoomSize from "@/components/server/ServerRoomSize";

export default {
    name: 'ServerDialog',

    props: {
        DialogVisible: Boolean,
        server_object: Object,
        driver_name: String,
        driver: Object
    },

    components: {
        ServerRoomSize,
    },

    computed: {
        dialog_height() {
            return (window.innerHeight * 0.8).toString();
        }
    },

    data() {
        return {
            visible: this.DialogVisible,
            server: this.server_object,
            dialog_message: {}
        };
    },

    mounted() {
        if (this.visible) {
            this.initDefaultValues();
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        edit() {
            if (this.validateForm()) {
                this.$nextTick(() => {
                    let params = {
                        driver_name: JSON.parse(JSON.stringify(this.driver_name)),
                        server : JSON.parse(JSON.stringify(this.server))
                    }
                    this.$emit('edit', params);
                });
            } else {
                this.dialog_message = {
                    type: 'error',
                    text: this.$gettext('Bei der Eingabe ist ein Fehler aufgetreten.')
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
            if (this.server[this.driver_name] && this.driver.config) {
                for (var i = 0; i < this.driver.config.length; i++) {
                    let option = this.driver.config[i];

                    if (this.server[this.driver_name][option.name] === undefined && option.value) {
                        this.server[this.driver_name][option.name] = option.value;
                    }
                }
            }

            if (this.server[this.driver_name] && !Object.keys(this.server[this.driver_name]).includes('course_types')) {
                this.$set(this.server[this.driver_name], 'course_types', '');
            }
        },

        togglePasswordText(event, ref) {
            if (!event.target) {
                return;
            }
            if ($(event.target).prop('tagName') == 'IMG') {
                if (this.$refs && this.$refs[ref]) {
                    this.$refs[ref][0].type = (this.$refs[ref][0].type == 'password') ? 'text' : 'password';
                }
            } else if ($(event.target).prop('tagName') == 'INPUT') {
                $(event.target).focus();
            }
        }
    },

    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            if (this.visible) {
                this.initDefaultValues();
                this.reduceMins();
            } else {
                this.dialog_message = {};
            }
        }
    }
};
</script>
