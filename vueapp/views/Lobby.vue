<template>
  <div>
    <h1 v-translate>Das Meeting hat noch nicht begonnen </h1>

    <p v-translate><img :src="loadingURL">
      Sie treten dem Meeting automatisch bei, sobald es begonnen hat.
    </p>

  </div>
</template>

<script>

import ServerDialog from "@/components/server/ServerDialog";

export default {
  name: "Lobby",

  components: {
    ServerDialog
  },

  data() {
    return {
      message: null,
      server_object: {},
      serverDialogVisible: false,
      changes_made: false
    }
  },
  computed: {
    loadingURL() {
      return LOADING_ICON_URL;
    }
  },
  async created() {
    this.interval = setInterval(function () {
      this.checkMeeting();
    }.bind(this), 5000);
  },
  methods: {
    checkMeeting: function () {
      window.location.reload();
    },
  }
};
</script>
