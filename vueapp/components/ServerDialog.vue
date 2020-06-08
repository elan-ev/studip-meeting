<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ 'Serverkonfiguration' | i18n }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <div v-for="(value, key) in driver.config" :key="key">
                            <label v-if="value.name != 'enable'" class="large">
                                {{ value.display_name | i18n }}
                                <input type="text" class="size-l"
                                    v-model="server[driver_name][value.name]"
                                    :placeholder="value.value">
                            </label>
                        </div>
                    </section>

                    <footer class="modal-footer">
                        <slot name="footer">
                            <StudipButton
                                icon="accept"
                                @click="edit">
                                {{ 'Übernehmen' | i18n }}
                            </StudipButton>

                            <StudipButton
                                icon="cancel"
                                @click="close">
                                {{ "Abbrechen" | i18n }}
                            </StudipButton>
                        </slot>
                    </footer>
                </div>
            </div>
        </transition>
    </div>
</template>
<script>
import axios from 'axios';
import StudipButton from "@/components/StudipButton";

export default {
    name: 'ServerDialog',

    props: {
        DialogVisible: Boolean,
        server_object: Object,
        driver_name: String,
        driver: Object
    },

    components: {
        StudipButton
    },

    data() {
        return {
            visible: this.DialogVisible,
            server: this.server_object
        };
    },

    mounted() {},

    methods: {
        close() {
            this.$emit('close');
        },
        edit() {
            this.$emit('edit', {
                driver_name: this.driver_name,
                server     : this.server
            });
        }
    },

    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
        }
    }
};
</script>
