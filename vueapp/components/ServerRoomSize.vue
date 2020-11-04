<template>
    <div>
        <h3>{{ roomsize_object.display_name | i18n }}</h3>
        <form class="default collapsable" style="position: relative">
            <fieldset v-for="(roomsize, rsk) in roomsize_object.value" :key="rsk" :class="((rsk > 0) ? 'collapsed ' : '') + 'accordion-collapse'">
                <legend @click.prevent="accordion_handle($event)">{{ roomsize.display_name | i18n }}</legend>
                <label v-for="(feature, rsfk) in roomsize.value" :key="rsfk" >
                    <template v-if="(feature['value'] === true || feature['value'] === false)">
                        <input  type="checkbox" true-value="true" false-value="false" v-model="this_server[roomsize_object.name][roomsize.name][feature.name]">
                        {{ feature['display_name'] | i18n }}
                        <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>
                    </template>
                    <template v-else>
                        {{ feature['display_name'] | i18n }}
                        <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>
                        <input v-model="this_server[roomsize_object.name][roomsize.name][feature.name]" :type="(feature['name'] == 'minParticipants') ? 'number' : 'text'"  
                            :min="(
                                (roomsize.name == 'large') ? 
                                 ( (parseInt(this_server[roomsize_object.name]['medium']['minParticipants'])) ? parseInt(this_server[roomsize_object.name]['medium']['minParticipants']) + 1 : 0 )
                                 : ((roomsize.name == 'medium') ? 
                                 ( (parseInt(this_server[roomsize_object.name]['small']['minParticipants']) >= 0) ? parseInt(this_server[roomsize_object.name]['small']['minParticipants']) + 1 : 0 )
                                 : 0)
                            )"
                            :max="(
                                (roomsize.name == 'small') ? 
                                ( (parseInt(this_server[roomsize_object.name]['medium']['minParticipants'])) ? parseInt(this_server[roomsize_object.name]['medium']['minParticipants']) - 1 : '' )
                                 : ((roomsize.name == 'medium') ? 
                                 ( (parseInt(this_server[roomsize_object.name]['large']['minParticipants'])) ? parseInt(this_server[roomsize_object.name]['large']['minParticipants']) - 1 : '' )
                                 : ((this_server['maxParticipants'] && parseInt(this_server['maxParticipants']) > 0) ? parseInt(this_server['maxParticipants']) : ''))
                            )"
                            @change="(feature['name'] == 'minParticipants') ? limitMinMax() : ''"
                            @keyup="(feature['name'] == 'minParticipants') ? limitMinMax() : ''"
                            :placeholder="feature['value'] ? feature['value'] : ''">
                    </template>
                </label>
            </fieldset>
        </form>
    </div>
</template>

<script>
import StudipTooltipIcon from "@/components/StudipTooltipIcon";

export default {
    name: 'ServerRoomSize',
    components: {
        StudipTooltipIcon,
    },
    props: {
        roomsize_object: {
            type: [Array, Object],
        },
        this_server : {
            type: Object
        }
    },
    methods: {
        accordion_handle(e) {
            if ($(e.target).parent().hasClass('collapsed')) {
                $('.accordion-collapse').addClass('collapsed');
                $(this).removeClass('collapsed');
            }
        },
        addPresetsToServer() {
            if (!Object.keys(this.this_server).includes(this.roomsize_object.name) || (this.this_server[this.roomsize_object.name] == "")) {
                this.this_server[this.roomsize_object.name] = new Object();
            } 
            for (const [key, value] of Object.entries(this.roomsize_object.value)) {
                if (!Object.keys(this.this_server[this.roomsize_object.name]).includes(value.name) || (this.this_server[this.roomsize_object.name][value.name] == "")) {
                    this.this_server[this.roomsize_object.name][value.name] = new Object();
                    for (const [feature_key, feature_value] of Object.entries(value.value)) {
                        this.this_server[this.roomsize_object.name][value.name][feature_key] = feature_value.value;
                    }
                }
            }
        },
        limitMinMax() {
            if (parseInt(this.this_server[this.roomsize_object.name][size_name]['minParticipants']) >= 0) {
                for (const [size_key, size_value] of Object.entries(this.this_server[this.roomsize_object.name])) {
                    if (Object.keys(size_value).includes('minParticipants') &&
                        parseInt(this.server[this.driver_name]['roomsize-presets'][size_key]['minParticipants']) > parseInt(this.server[this.driver_name]['maxParticipants'])) {
                        this.server[this.driver_name]['roomsize-presets'][size_key]['minParticipants'] = this.server[this.driver_name]['maxParticipants'];
                    }
                }
            }
        }
    },
    beforeMount () {
        this.addPresetsToServer();
    },
}
</script>
