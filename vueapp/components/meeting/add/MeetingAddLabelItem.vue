<template>
    <label @click.stop="labelIsClicked" :class="{disabled: checkDisabled()}">
        <template v-if="feature['name'] == 'autoStartRecording'">
            {{ feature['display_name'] }}
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"
                :badge="(badge && badge.show == true) ? true : false">
                {{ (badge && badge.text) ? badge.text : ''}}
            </StudipTooltipIcon>
            <br>
            <label class="radio-label">
                <input type="radio"
                    :disabled="checkDisabled()"
                    value="true"
                    v-model="room['features'][feature['name']]">
                {{ $gettext('Aufzeichnung automatisch starten und beenden') }}
            </label>
            <label class="radio-label">
                <input type="radio"
                    :disabled="checkDisabled()"
                    value="false"
                    v-model="room['features'][feature['name']]">
                {{ $gettext('Aufzeichnung manuell starten und beenden') }}
            </label>
            <span class="inline-feature-warning-icon" v-if="inlineFeatureWarningIcon && inlineFeatureWarningIcon.messagebox_id">
                <a href="#" @click.prevent="toggleInlineFeatureWarning(inlineFeatureWarningIcon['messagebox_id'])">
                    <StudipIcon shape="exclaim-circle-full"
                        role="status-yellow" size="16"></StudipIcon>
                </a>
            </span>
        </template>
        <template v-else-if="feature['name'] == 'welcome'">
            {{ feature['display_name'] }}
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"
                :badge="(badge && badge.show == true) ? true : false">
                {{ (badge && badge.text) ? badge.text : ''}}
            </StudipTooltipIcon>
            <br>
            <textarea v-model.trim="room['features'][feature['name']]" :placeholder="feature.value"></textarea>
        </template>
        <template v-else-if="(feature['value'] === true || feature['value'] === false)">
            <input type="checkbox"
                true-value="true"
                false-value="false"
                :disabled="checkDisabled()"
                v-model="room['features'][feature['name']]">
            {{ feature['display_name'] }}
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"
                :badge="(badge && badge.show == true) ? true : false">
                {{ (badge && badge.text) ? badge.text : ''}}
            </StudipTooltipIcon>
            <span class="inline-feature-warning-icon" v-if="inlineFeatureWarningIcon && inlineFeatureWarningIcon.messagebox_id">
                <a href="#" @click.prevent="toggleInlineFeatureWarning(inlineFeatureWarningIcon['messagebox_id'])">
                    <StudipIcon shape="exclaim-circle-full"
                        role="status-yellow" size="16"></StudipIcon>
                </a>
            </span>
        </template>
        <template v-else-if="feature['value'] && typeof feature['value'] === 'object'">
            {{ feature['display_name'] }}
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>

            <select :id="feature['name']" v-model.trim="room['features'][feature['name']]" :disabled="checkDisabled()">
                <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                        :value="findex">
                        {{ fvalue }}
                </option>
            </select>
        </template>
        <template v-else>
            {{ feature['display_name'] }}
            <span v-if="feature['name'] == 'maxParticipants' && maxAllowedParticipants != 0">
                {{ $gettext('(Max. Limit: %{ count })') | gettextinterpolate({count: maxAllowedParticipants}) }}
            </span>
            <span v-if="feature['name'] == 'duration' && maxDuration">
                {{ $gettext('(Max. Limit: %{ maxDuration } Minuten)') | gettextinterpolate({maxDuration: maxDuration}) }}
            </span>
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')"
                :text="feature['info']">
            </StudipTooltipIcon>

            <div>
                <input :disabled="checkDisabled()" :class="{'inline-block' : feature['name'] == 'maxParticipants'}" :type="(feature['name'] == 'duration' || feature['name'] == 'maxParticipants') ? 'number' : 'text'"
                    :max="(
                        (feature['name'] == 'maxParticipants') ?
                        (maxAllowedParticipants != 0) ? maxAllowedParticipants : ''
                        :  (feature['name'] == 'duration') ? maxDuration : ''
                    )"
                    :min="(feature['name'] == 'maxParticipants') ? minParticipants : ((feature['name'] == 'duration') ? 1 : '')"
                    @change="(feature['name'] == 'maxParticipants') ? checkPresets() : ''"
                    v-model.trim="room['features'][feature['name']]"
                    :placeholder="feature['value'] ? feature['value'] : ''"
                    :id="feature['name']">
                <span v-if="feature['name'] == 'maxParticipants'">
                    {{ $gettext('(0 = unbegrenzt)') }}
                </span>
            </div>
        </template>
    </label>
</template>

<script>
export default {
    name: "MeetingAddLabelItem",

    computed: {
        itemValue() {
            return this.room.features[this.feature.name];
        }
    },

    props: {
        room: {
            type: Object,
            required: true
        },
        feature: {
            type: Object,
            required: true
        },
        maxDuration: {
            type: Number,
            required: false,
        },
        minParticipants: {
            type: Number,
            required: false,
        },
        maxAllowedParticipants: {
            type: Number,
            required: false,
        },
        inlineFeatureWarningIcon: {
            type: Object,
            required: false
        },
        badge: {
            type: Object,
            required: false
        },
        isPreset: {
            type: Boolean,
            default: false
        }
    },

    methods: {
        checkPresets() {
            this.$emit('checkPresets');
        },
        toggleInlineFeatureWarning(messagebox_id) {
            this.$emit('toggleInlineFeatureWarning', messagebox_id);
        },
        labelIsClicked() {
            if (this.feature['name'] == 'record' || this.feature['name'] == 'autoStartRecording') {
                this.$emit('labelClicked', this.feature['name']);
            }
        },
        checkDisabled() {
            return this.getDisabledRules(this.feature['name']);
        },
        getDisabledRules(feature_name) {
            switch (feature_name) {
                case 'opencast_webcam_record':
                    var disabled = false;
                    if (this.feature['name'] == 'opencast_webcam_record') {
                        if (!this.room?.features?.record || (this.room.features.record && JSON.parse(this.room.features.record) == false)) {
                            this.$set(this.room.features, 'opencast_webcam_record', 'false');
                            disabled = true;
                        }
                    }
                    return disabled;
                case 'autoStartRecording':
                    var disabled = false;
                    if (this.feature['name'] == 'autoStartRecording') {
                        if (!this.room?.features?.record || (this.room.features.record && JSON.parse(this.room.features.record) == false)) {
                            // If the room is in edit mode (NOT NEW), we change the value,
                            // otherwise we don't in order to make the config value work!
                            if (this.room?.mkdate) {
                                this.$set(this.room.features, 'autoStartRecording', 'false');
                            }
                            disabled = true;
                        }
                    }
                    return disabled;
                case 'duration':
                    var disabled = false;
                    if (this.feature['name'] == 'duration') {
                        if (!this.room?.features?.record || (this.room.features.record && JSON.parse(this.room.features.record) == false)) {
                            this.$set(this.room.features, 'duration', this.feature['value']);
                            disabled = true;
                        }
                    }
                    return disabled;
                case 'giveAccessToRecordings':
                    var disabled = false;
                    if (this.feature['name'] == 'giveAccessToRecordings') {
                        if (!this.room?.features?.record || (this.room.features.record && JSON.parse(this.room.features.record) == false)) {
                            this.$set(this.room.features, 'giveAccessToRecordings', 'false');
                            disabled = true;
                        }
                    }
                    return disabled;
                default:
                    return false;
            }
        }
    },

    watch: {
        itemValue(newValue, oldValue) {
            if (this.isPreset) {
                this.$emit('adjustPresets');
            }
        }
    },
}
</script>
