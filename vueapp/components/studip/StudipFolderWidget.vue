<template>
    <ul class="widget-list widget-links sidebar-navigation">
        <li v-for="(item, index) in items" :key="index">
            <a href="#" @click="emitClick(item)">
                <StudipIcon :title="item.label" :shape="item.icon" role="clickable" />
                {{ item.label }}
            </a>
        </li>
    </ul>
</template>

<script>
export default {
    name: 'studip-folder-widget',
    props: {
        items: Array,
    },
    methods: {
        linkAttributes(item) {
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
        emitClick(item) {
            if (item.emit) {
                this.$emit(item.emit, ...[item.emitArguments ?? []]);
            }
        },
    },
};
</script>
