<template>
    <div v-if="status">
        <StudipIcon icon="video2" role="attention" size=36></StudipIcon>
        <span class="red" v-translate>
            Dieser Raum wird momentan aufgezeichnet!
        </span>
    </div>
</template>

<script>

import {
    ROOM_STATUS,
} from "@/store/actions.type";

export default {
    name: "MeetingStatus",
    props: {
        room_id: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            status: false,
            interval: null
        }
    },
    methods: {
        getStatus() {
            var self = this;
            this.interval = setInterval(function () {
                self.$store.dispatch(ROOM_STATUS, self.room_id)
                .then(({ data }) => {
                    self.status = data.status;
                });
            }, 5000);
        }
    },
    mounted() {
        this.getStatus()
    },
    beforeDestroy () {
        clearInterval(this.interval)
    }
}
</script>
