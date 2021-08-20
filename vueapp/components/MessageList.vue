<template>
    <div v-if="messages.length">
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
import MessageBox from "@/components/MessageBox";

export default {
    name: "MessageList",

    components: {
        MessageBox
    },

    props: {
        messages: {
            type: Array,
        }
    }
};
</script>
