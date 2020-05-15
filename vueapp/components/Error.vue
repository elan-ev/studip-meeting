<template>
    <div v-if="error" class="messagebox messagebox_error ">
        <div class="messagebox_buttons">
            <a class="close" href="#" title="Nachrichtenbox schliessen">
                <span>Nachrichtenbox schliessen</span>
            </a>
        </div>
        <div v-if="error.data.errors" v-for="err in error.data.errors">
            {{ err.code }}: {{ err.title }}
        </div>

        <div v-if="error.data.error" v-for="err in error.data.error">
            {{ err.message }}<br>
            Line {{ err.line }} in file {{ err.file }}
        </div>
    </div>
</template>

<script>
import store from "@/store";
import { mapGetters } from "vuex";

import { ERROR_CLEAR } from "@/store/actions.type";

export default {
    name: "Error",

    computed: {
        ...mapGetters(["error"])
    },
    methods: {
        clearErrors() {
            this.$store.dispatch(ERROR_CLEAR);
        }
    }
};
</script>
