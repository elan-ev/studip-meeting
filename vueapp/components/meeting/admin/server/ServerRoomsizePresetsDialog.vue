<template>
    <div v-if="visible">
        <studip-dialog
            :title="$gettext('Servervoreinstellungen')"
            :confirmText="$gettext('Speichern')"
            confirmClass="accept"
            :closeText="this.$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="750"
            :height="dialog_height"
            @close="close"
            @confirm="savePresets"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="dialog_message.text" :type="dialog_message.type" @hide="dialog_message.text = ''">
                    {{ dialog_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent>
                    <fieldset v-for="(pdata, pindex) in server_presets" :key="pindex" class="collapsable collapsed">
                        <legend
                            tabindex="0"
                            role="button"
                            aria-expanded="false"
                            ref="fieldsets"
                            v-on="fieldsetHandlers">
                            {{ pdata.presetName ? pdata.presetName : $gettext('Voreinstellung') }}
                        </legend>
                        <label v-for="(option, dindex) in driver_presets" :key="dindex">
                            <template v-if="(option.value === true || option.value === false)">
                                <input type="checkbox" :true-value="true" :false-value="false" v-model="server_presets[pindex][option.name]">
                                {{ option.display_name }}
                                <StudipTooltipIcon v-if="Object.keys(option).includes('info')" :text="option.info" />
                            </template>
                            <template v-else>
                                <span :class="{required: option.name === 'presetName'}">
                                    {{ option.display_name }}
                                </span>
                                <StudipTooltipIcon v-if="Object.keys(option).includes('info')" :text="option.info" />
                                <template v-if="option.name == 'minParticipants'">
                                    <input type="number" v-model.number="server_presets[pindex][option.name]"
                                        ref="min_participants"
                                        min="0"
                                        :max="Object.keys(server).includes('maxParticipants') ? parseInt(server.maxParticipants, 10) : ''"
                                    >
                                    <small class="invalid_message" ref="min_participants_invalid_messages">{{ $gettext('Die Zahl wurde in anderen sensiblen Voreinstellungen verwendet.') }}</small>
                                </template>
                                <template v-else-if="option.name == 'presetName'">
                                    <input type="text" v-model.trim="server_presets[pindex][option.name]" ref="preset_names" :placeholder="option.value ? option.value : ''" />
                                    <small class="invalid_message" ref="preset_name_invalid_messages">{{ $gettext('Name konnte nicht leer sein.') }}</small>
                                </template>
                                <input v-else type="text" v-model.trim="server_presets[pindex][option.name]" :placeholder="option.value ? option.value : ''">
                            </template>
                        </label>
                        <StudipButton icon="trash" type="button"
                            @click.prevent="confirmDelete(pindex)">
                            {{ $gettext('Voreinstellung löschen') }}
                        </StudipButton>
                    </fieldset>
                    <MessageBox v-if="server_presets.length == 0" type="info">
                        {{ $gettext('Keine Voreinstellung vorhanden') }}
                    </MessageBox>
                </form>
            </template>
            <template v-slot:dialogButtons>
                <StudipButton
                    icon="add"
                    @click="addNew">
                    {{ $gettext('Voreinstellung hinzufügen') }}
                </StudipButton>
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
import { a11y } from '@/common/a11y.mixins'

export default {
    name: 'ServerRoomsizePresetsDialog',

    mixins: [confirm_dialog, a11y],

    props: {
        DialogVisible: Boolean,
        server_object: Object,
        driver_name: String,
        driver: Object
    },

    computed: {
        dialog_height() {
            return (window.innerHeight * 0.7).toString();
        }
    },

    data() {
        return {
            visible: this.DialogVisible,
            server: this.server_object,
            dialog_message: {},
            showConfirmDialog: false,
            server_presets: {},
            new_preset: {},
            driver_presets: []
        };
    },

    methods: {
        close() {
            this.$emit('close');
        },

        savePresets() {
            if (this.validateMins() && this.validateNames()) {
                let params = {
                    driver_name: this.driver_name,
                    server_presets: this.server_presets,
                    server_index: this.server_object[this.driver_name].index
                };
                this.$emit('done', params);
            }
            return;
        },

        confirmDelete(index) {
            this.showConfirmDialog = false;
            this.showConfirmDialog = {
                title: this.$gettext('Voreinstellung löschen'),
                question: this.$gettext('Sind Sie sicher, dass Sie diese Voreinstellung löschen möchten?'),
                confirm_callback: 'performDelete',
                confirm_callback_data: {index},
                height: '210'
            }
        },

        performDelete({index}) {
            this.dialog_message = {};
            this.$delete(this.server_presets, index);
            this.dialog_message.type = 'success';
            this.dialog_message.text = this.$gettext('Voreinstellung wurde gelöscht.');
        },

        init() {
            this.dialog_message = {};
            let server_presets_reactive = this.server_object?.[this.driver_name]?.['roomsize-presets'] ?? [];
            this.server_presets = JSON.parse(JSON.stringify(server_presets_reactive));
            this.driver_presets = this.driver?.['roomsize-presets'] ?? [];
            this.driver_presets.forEach(item => {
                this.$set(this.new_preset, item.name, item.value);
            });
        },

        addNew() {
            this.dialog_message = {};
            this.server_presets.push(this.new_preset);
            this.dialog_message.type = 'success';
            this.dialog_message.text = this.$gettext('Die Voreinstellung wurde hinzugefügt.');
        },

        validateMins() {
            let sensitives = this.server_presets.map(preset => preset.roomsizeSensitive);
            let minParticipants = this.server_presets.map(preset => preset.minParticipants);
            let invalids = [];
            for (let i = 0; i < minParticipants.length - 1; i++) {
                let j = i + 1;
                for (j; j < minParticipants.length; j++) {
                    if (minParticipants[i] == minParticipants[j] && (sensitives[i] || sensitives[j])) {
                        if (!invalids.includes(i)) {
                            invalids.push(i);
                        }
                        if (!invalids.includes(j)) {
                            invalids.push(j);
                        }
                    }
                }
            }
            let all = this.server_presets.keys();
            for (const spindex of all) {
                this.toggleInvalidMinParticipants(false, spindex);
            }
            invalids.forEach(index => {
                this.toggleInvalidMinParticipants(true, index);
            });
            return invalids.length <= 0;
        },

        toggleInvalidMinParticipants(show, index) {
            if (show) {
                $(this.$refs.min_participants_invalid_messages[index]).show();
                $(this.$refs.min_participants[index]).addClass('invalid');
                this.openInvalidFieldset(index);
            } else {
                $(this.$refs.min_participants_invalid_messages[index]).hide();
                $(this.$refs.min_participants[index]).removeClass('invalid');
            }
        },

        validateNames() {
            let names = this.server_presets.map(preset => preset.presetName.trim());
            let invalids = [];
            for (let i = 0; i < names.length; i++) {
                if (names[i] == '' && !invalids.includes(i)) {
                    invalids.push(i);
                }
            }
            let all = this.server_presets.keys();
            for (const spindex of all) {
                this.toggleInvalidPresetName(false, spindex);
            }
            invalids.forEach(index => {
                this.toggleInvalidPresetName(true, index);
            });
            return invalids.length <= 0;
        },

        toggleInvalidPresetName(show, index) {
            if (show) {
                $(this.$refs.preset_name_invalid_messages[index]).show();
                $(this.$refs.preset_names[index]).addClass('invalid');
                this.openInvalidFieldset(index);
            } else {
                $(this.$refs.preset_name_invalid_messages[index]).hide();
                $(this.$refs.preset_names[index]).removeClass('invalid');
            }
        },

        openInvalidFieldset(index) {
            let expanded = this.$refs.fieldsets[index].getAttribute('aria-expanded');
            if (expanded == 'false') {
                this.$refs.fieldsets[index].click();
            }
        }
    },

    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            if (this.visible) {
                this.init();
            } else {
                this.dialog_message = {};
            }
        },
        server_presets: {
            handler: function() {
                if (this.visible) {
                    setTimeout(() => {
                        this.validateMins();
                        this.validateNames();
                    }, 500);
                }
            },
            deep: true
        }
    }
};
</script>
