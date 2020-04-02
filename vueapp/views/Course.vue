<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

export default {
    name: "Course",
    components: {
        StudipButton, StudipIcon,
        MessageBox,
    },
    data() {
        return {
            message: {
                type: 'info',
                text: 'Willkommen zum Meetings Plugin!'
            }
        }
    },
    computed: {
    },
    methods: {
    },
    mounted() {
    }
};
</script>
