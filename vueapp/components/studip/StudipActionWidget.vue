<template>
    <ul class="widget-list widget-links meeting-widget-links">
        <li v-for="(item, index) in items" :key="index">
            <button v-on="linkEvents(item)">
                <StudipIcon :title="item.label" :icon="item.icon" role="clickable" />
                {{ item.label }}
            </button>
        </li>
    </ul>
</template>

<script>
export default {
    name: 'studip-action-widget',
    props: {
        items: Array,
    },
    methods: {
        linkAttributes (item) {
            let attributes = item.attributes;
            attributes.class = item.classes;

            if (item.disabled) {
                attributes.disabled = true;
            }

            if (item.url) {
                attributes.href = item.url;
            }

            return attributes;
        },
        linkEvents (item) {
            let events = {};
            if (item.emit) {
                events.click = () => {
                    this.$emit.apply(this, [item.emit].concat(item.emitArguments));
                };
            }
            return events;
        }
    }
}
</script>
