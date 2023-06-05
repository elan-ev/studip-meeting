<template>
    <div v-if="messages.length" ref="messages_div" class="meeting-messages">
        <MessageBox v-for="(message, index) in messages" :key="index" :type="message.type" @hide="messages[index] = ''">
            <span v-if="typeof message.text == 'string'">{{ message.text }}</span>
            <ul v-else>
                <li v-for="(text, i) in message.text" :key="i">
                    {{ text }}
                </li>
            </ul>
        </MessageBox>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "MessageList",

    computed: {
        ...mapGetters(['messages'])
    },
};
</script>
