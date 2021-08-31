<template>
    <label @click.stop="labelIsClicked">
        <template v-if="(feature['value'] === true || feature['value'] === false)">
            <input type="checkbox"
                true-value="true"
                false-value="false"
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

            <select :id="feature['name']" v-model.trim="room['features'][feature['name']]">
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
                &nbsp; (Max. Limit: %{ count })
            </span>
            <span v-if="feature['name'] == 'duration' && maxDuration" 
                v-translate="{
                    maxDuration
                }"
            >
                    &nbsp; (Max. Limit: %{ maxDuration } Minuten)
            </span>
            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')"
                :text="feature['info']">
            </StudipTooltipIcon>

            <input :type="(feature['name'] == 'duration' || feature['name'] == 'maxParticipants') ? 'number' : 'text'"
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
        }
    },    
}
</script>