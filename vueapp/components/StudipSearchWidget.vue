<template>
    <form class="sidebar-search meeting-sidebar-search">
        <ul class="needles">
            <li v-for="needle in needles" :key="needle.id">
                <div class="clear-search" v-if="needle.value" style="text-align: right;">
                    <a @click.prevent="clearSearchTerm($event, needle)">
                        <StudipIcon icon="search+decline" role="clickable" size="16"></StudipIcon>
                        <translate> Zurücksetzen </translate>
                    </a>
                </div>
                <label :for="'needle-' + needle.id" v-if="needle.withLabel">
                    {{ needle.label }}
                </label>
                <input type="text" :id="'needle-' + needle.id" name="needle-' + needle.id" :value="needle.value"
                    :placeholder="!needle.withLabel ? needle.label : ''"
                    @keypress.enter="sendSearchTerm($event, needle)"
                >
                <input type="submit" value="Suchen" @click="sendSearchTerm($event, needle)">
            </li>
        </ul>
    </form>
</template>

<script>
import StudipIcon from "@/components/StudipIcon";

export default {
    name: 'studip-search-widget',
    components: {
        StudipIcon,
    },
    props: {
        needles: Array,
    },
    methods: {
        sendSearchTerm(e, needle) {
            e.preventDefault();
            let searchTerm = $(`#needle-${needle.id}`).val();
            if (searchTerm && needle.emit) {
                this.$emit(needle.emit, searchTerm);
            }
        },
        clearSearchTerm(e, needle) {
            e.preventDefault();
            if (needle.emitClear) {
                needle.value = '';
                this.$emit(needle.emitClear);
            }
        }
    }
}
</script>
