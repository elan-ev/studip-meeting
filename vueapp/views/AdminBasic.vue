<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>
        <form class="default" v-if="config">
            <fieldset v-for="(driver, name) in config" :key="name">
                <legend>
                    {{ driver.display_name | i18n }}
                </legend>
                <div v-for="(value, key) in driver" :key="key">
                    <label v-if="key == 'enable'">
                        <input type="checkbox" 
                        true-value="1" 
                        false-value="0" 
                        v-model="config[name][key]">
                        {{ "Verwenden dieses Treibers zulassen" | i18n }}
                    </label>
                    <label v-if="key != 'enable' && key != 'display_name'">
                        {{ key.charAt(0).toUpperCase() + key.slice(1) | i18n }}
                        <input type="text"
                            v-model="config[name][key]">
                    </label>
                </div>
            </fieldset>
            <footer>
                <StudipButton icon="accept" @click="storeConfig">
                    Einstellungen speichern
                </StudipButton>
            </footer>
        </form>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import MessageBox from "@/components/MessageBox";

import {
    CONFIG_LIST_READ, CONFIG_READ, CONFIG_UPDATE,
    CONFIG_CREATE, CONFIG_DELETE
} from "@/store/actions.type";

import {
    CONFIG_SET
} from "@/store/mutations.type";

export default {
    name: "AdminBasic",
    components: {
        StudipButton,
        MessageBox,
    },
    data() {
        return {
            message: null,
        }
    },
    computed: {
        ...mapGetters(['config'])
    },
    methods: {
        storeConfig() {
            this.$store.dispatch(CONFIG_CREATE, this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.$store.commit(CONFIG_SET, data.config);
                });
        }
    },
    mounted() {
        store.dispatch(CONFIG_LIST_READ);
    }
};
</script>
