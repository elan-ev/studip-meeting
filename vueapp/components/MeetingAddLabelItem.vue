<template>
    <label @click.stop="labelIsClicked" :class="{disabled: checkDisabled()}">
        <template v-if="(feature['value'] === true || feature['value'] === false)">
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
                    <StudipIcon icon="exclaim-circle-full"
                        role="status-yellow" size="16"></StudipIcon>
                </a>
            </span>
        </template>
        <template v-else-if="feature['value'] && typeof feature['value'] === 'object'">
            {{ feature['display_name'] }}
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>

            <select :id="feature['name']" v-model.trim="room['features'][feature['name']]" :disabled="checkDisabled()">
                <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                        :value="findex" v-translate>
                        {{ fvalue }}
                </option>
            </select>
        </template>
        <template v-else>
            {{ feature['display_name'] }}
            <span v-if="feature['name'] == 'maxParticipants' && maxAllowedParticipants != 0"
                v-translate="{
                    count: maxAllowedParticipants
                }"
            >
                (Max. Limit: %{ count })
            </span>
            <span v-if="feature['name'] == 'duration' && maxDuration"
                v-translate="{
                    maxDuration
                }"
            >
                    (Max. Limit: %{ maxDuration } Minuten)
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
                <span v-if="feature['name'] == 'maxParticipants'" v-translate>
                    (0 = unbegrenzt)
                </span>
            </div>
        </template>
    </label>
</template>

<script>
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import StudipIcon from "@/components/StudipIcon";
export default {
    name: "MeetingAddLabelItem",

    components: {
        StudipTooltipIcon,
        StudipIcon
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
            if (this.feature['name'] == 'record') {
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
                        if (this.room.features && Object.keys(this.room.features).includes('record')
                            && JSON.parse(this.room.features.record) == false) {
                            this.$set(this.room.features, 'opencast_webcam_record', 'false');
                            disabled = true;
                        }
                    }
                    return disabled;
                default: 
                    return false;
            }
        }
    },
}
</script>
